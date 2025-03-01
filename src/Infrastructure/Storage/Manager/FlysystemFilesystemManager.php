<?php declare(strict_types=1);

namespace App\Infrastructure\Storage\Manager;

use App\Domain\Common\Exception\ReadOnlyException;
use App\Domain\Common\Manager\StateManager;
use App\Domain\Storage\Exception\StorageException;
use App\Domain\Storage\Manager\FilesystemManager;
use App\Domain\Storage\ValueObject\Filename;
use App\Domain\Storage\ValueObject\Path;
use App\Domain\Storage\ValueObject\Stream;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class FlysystemFilesystemManager implements FilesystemManager
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var bool
     */
    private $ro;

    public function __construct(Filesystem $fs, StateManager $stateManager, bool $isAppReadOnly)
    {
        $this->ro = $isAppReadOnly;
        $this->fs = $stateManager->generateProxy($fs, 'fs');
    }

    public function fileExist(Filename $name): bool
    {
        return $this->fs->has($name->getValue());
    }

    public function directoryExists(Filename $name): bool
    {
        return $this->fileExist($name);
    }

    public function read(Filename $name): Stream
    {
        try {
            $resource = $this->fs->readStream($name->getValue());

        } catch (FileNotFoundException $exception) {
            throw new StorageException(
                'Read error, file not found',
                StorageException::codes['file_not_found'],
                $exception
            );
        }

        if (!\is_resource($resource)) {
            throw new StorageException(
                'Read error, the file may be not readable due to permission error or i/o error',
                StorageException::codes['io_perm_error']
            );
        }

        return new Stream($resource);
    }

    public function write(Filename $filename, Stream $stream): bool
    {
        $this->assertCanWrite();

        return $this->fs->putStream($filename->getValue(), $stream->attachTo());
    }

    public function mkdir(Path $path): void
    {
        $this->assertCanWrite();

        $this->fs->createDir($path->getValue());
    }

    /**
     * @param Filename $filename
     *
     * @return int|null
     *
     * @throws FileNotFoundException
     */
    public function getFileSize(Filename $filename): ?int
    {
        return $this->fs->getSize($filename->getValue());
    }

    /**
     * @param Filename $filename
     *
     * @throws FileNotFoundException
     * @throws ReadOnlyException
     */
    public function delete(Filename $filename): void
    {
        $this->assertCanWrite();

        $this->fs->delete($filename->getValue());
    }

    public function test(): void
    {
    }

    private function assertCanWrite(): void
    {
        if ($this->ro) {
            throw new ReadOnlyException('Filesystem is read-only');
        }
    }
}
