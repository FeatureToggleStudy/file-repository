<?php declare(strict_types=1);

namespace App\Domain\Storage\Manager;

use App\Domain\Storage\Entity\StagedFile;
use App\Domain\Storage\Entity\StoredFile;
use App\Domain\Storage\Exception\DuplicatedContentException;
use App\Domain\Storage\Exception\StorageException;
use App\Domain\Storage\Exception\ValidationException;
use App\Domain\Storage\Factory\FileInfoFactory;
use App\Domain\Storage\Factory\StoredFileFactory;
use App\Domain\Storage\Form\UploadForm;
use App\Domain\Storage\Repository\FileRepository;
use App\Domain\Storage\Repository\StagingAreaRepository;
use App\Domain\Storage\Security\UploadSecurityContext;
use App\Domain\Storage\Validation\SubmittedFileValidator;
use App\Domain\Storage\ValueObject\Filename;
use App\Domain\Storage\ValueObject\InputEncoding;
use App\Domain\Storage\ValueObject\Path;
use App\Domain\Storage\ValueObject\Stream;

/**
 * Responsible for handling file submission, then delegating task to repository and filesystem
 */
class WriteManager
{
    /**
     * @var FilesystemManager
     */
    private $fs;

    /**
     * @var FileRepository
     */
    private $repository;

    /**
     * @var FileInfoFactory
     */
    private $fileInfoFactory;

    /**
     * @var SubmittedFileValidator
     */
    private $validator;

    /**
     * @var StoredFileFactory
     */
    private $storedFileFactory;

    /**
     * @var StagingAreaRepository
     */
    private $staging;

    public function __construct(
        FilesystemManager      $fs,
        FileRepository         $repository,
        FileInfoFactory        $fileInfoFactory,
        SubmittedFileValidator $validator,
        StoredFileFactory      $storedFileFactory,
        StagingAreaRepository  $staging
    ) {
        $this->fs                = $fs;
        $this->repository        = $repository;
        $this->fileInfoFactory   = $fileInfoFactory;
        $this->validator         = $validator;
        $this->storedFileFactory = $storedFileFactory;
        $this->staging           = $staging;
    }

    /**
     * Case: There EXISTS file in STORAGE and in REGISTRY
     *       BUT we want to OVERWRITE it.
     *
     *       The flow is the same as when adding a new file.
     *       Full validation is performed.
     *
     * @see submitFileToBothRepositoryAndStorage
     *
     * @param StoredFile $existingFromRepository
     * @param Stream $stream
     * @param UploadSecurityContext $securityContext
     * @param InputEncoding $encoding
     *
     * @return StoredFile
     *
     * @throws DuplicatedContentException
     * @throws ValidationException
     */
    public function overwriteFile(
        StoredFile $existingFromRepository,
        Stream $stream,
        UploadSecurityContext $securityContext,
        InputEncoding $encoding
    ): StoredFile {

        return $this->submitFileToBothRepositoryAndStorage(
            $stream,
            $existingFromRepository->getFilename(),
            $securityContext,
            $existingFromRepository,
            '',
            $encoding
        );
    }

    /**
     * CASE: The file EXISTS physically in the STORAGE
     *       But DOES NOT have ENTRY IN REGISTRY
     *
     *      In this case we just add an entry, skipping the validation, as the file is already present,
     *      we do not plan to delete it in case the validation rules could change in time
     *
     * @param Filename      $filename
     * @param UploadForm    $form
     * @param InputEncoding $encoding
     *
     * @return StoredFile
     *
     * @throws StorageException
     */
    public function submitFileLostInRepositoryButExistingInStorage(
        Filename $filename,
        UploadForm $form,
        InputEncoding $encoding
    ): StoredFile {

        return $this->commitToRegistry(
            $this->staging->keepStreamAsTemporaryFile($this->fs->read($filename), $encoding),
            $this->storedFileFactory->createFromForm($form, $filename),
            $form->contentIdent
        );
    }

    /**
     * CASE: It's a totally NEW FILE
     *       - Deduplicate it
     *       - Get all metadata
     *       - Write to REGISTRY and to STORAGE
     *
     * @param Stream                $stream
     * @param Filename              $filename
     * @param UploadSecurityContext $securityContext
     * @param UploadForm            $form
     * @param InputEncoding         $encoding
     *
     * @return StoredFile
     *
     * @throws DuplicatedContentException
     * @throws StorageException
     * @throws ValidationException
     */
    public function submitNewFile(
        Stream $stream,
        Filename $filename,
        UploadSecurityContext $securityContext,
        UploadForm $form,
        InputEncoding $encoding
    ): StoredFile {
        return $this->submitFileToBothRepositoryAndStorage(
            $stream,
            $filename,
            $securityContext,
            $this->storedFileFactory->createFromForm($form, $filename),
            $form->contentIdent,
            $encoding
        );
    }

    /**
     * CASE: Repository HAS ENTRY
     *       Storage does NOT HAVE FILE
     *
     * @param Stream                $stream
     * @param Filename              $filename
     * @param StoredFile            $existingFromRepository
     * @param UploadSecurityContext $securityContext
     * @param InputEncoding         $encoding
     *
     * @return StoredFile
     *
     * @throws StorageException
     */
    public function submitFileThatExistsInRepositoryButNotOnStorage(
        Stream $stream,
        Filename $filename,
        StoredFile $existingFromRepository,
        UploadSecurityContext $securityContext,
        InputEncoding $encoding
    ): StoredFile {

        $staged = $this->staging->keepStreamAsTemporaryFile($stream, $encoding);

        // each file added to filesystem should be validated
        $this->validator->validateAfterUpload($staged, $securityContext);

        return $this->writeToBothRegistryAndStorage(
            $staged,
            $filename,
            $existingFromRepository,
            ''
        );
    }

    /**
     * @param Stream $stream
     * @param Filename $filename
     * @param UploadSecurityContext $securityContext
     * @param StoredFile $storedFile
     * @param string $contentIdent
     *
     * @param InputEncoding $encoding
     * @return StoredFile
     *
     * @throws DuplicatedContentException
     * @throws ValidationException
     */
    private function submitFileToBothRepositoryAndStorage(
        Stream $stream,
        Filename $filename,
        UploadSecurityContext $securityContext,
        StoredFile $storedFile,
        string $contentIdent,
        InputEncoding $encoding
    ): StoredFile {

        // 1. Keep file in temporary dir
        $staged = $this->staging->keepStreamAsTemporaryFile($stream, $encoding);

        // 2. Get all info about the file
        $info = $this->fileInfoFactory->generateForStagedFile($staged, $contentIdent);

        // 3. Avoid content duplications
        $this->validator->assertThereIsNoFileByChecksum($storedFile, $info->getChecksum());

        // each new file needs to be validated
        $this->validator->validateAfterUpload($staged, $securityContext);

        // 4. Write in case of a valid NEW file
        return $this->writeToBothRegistryAndStorage(
            $staged,
            $filename,
            $storedFile,
            $contentIdent
        );
    }

    /**
     * @param StagedFile $stagedFile
     * @param Filename $filename
     * @param StoredFile $storedFile
     * @param string $contentIdent
     *
     * @return StoredFile
     *
     * @throws DuplicatedContentException
     * @throws ValidationException
     */
    private function writeToBothRegistryAndStorage(
        StagedFile $stagedFile,
        Filename $filename,
        StoredFile $storedFile,
        string $contentIdent
    ): StoredFile {

        $this->fs->write($filename, $stagedFile->openAsStream());

        return $this->commitToRegistry($stagedFile, $storedFile, $contentIdent);
    }

    /**
     * @param StagedFile|Path $stagedFile
     * @param StoredFile $file
     * @param string $contentIdent
     *
     * @return StoredFile
     * @throws DuplicatedContentException
     * @throws ValidationException
     */
    private function commitToRegistry($stagedFile, StoredFile $file, string $contentIdent): StoredFile
    {
        // fill up the metadata
        if (!$file->wasAlreadyStored()) {
            $info = $this->fileInfoFactory->generateForStagedFile($stagedFile, $contentIdent);

            $file->setContentHash($info->getChecksum());
            $file->setMimeType($info->getMime());

            $this->validator->assertThereIsNoFileByChecksum($file, $info->getChecksum());

            $this->validator->assertThereIsNoFileByFilename($file);
        }

        $this->repository->persist($file);
        $this->repository->flush();

        return $file;
    }
}
