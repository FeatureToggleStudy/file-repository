<?php declare(strict_types=1);

namespace App\Domain\Backup\ActionHandler\Version;

use App\Domain\Backup\Entity\BackupCollection;
use App\Domain\Backup\Entity\StoredVersion;
use App\Domain\Backup\Exception\AuthenticationException;
use App\Domain\Backup\Form\Version\VersionsListingForm;
use App\Domain\Backup\Repository\VersionRepository;
use App\Domain\Backup\Response\Version\VersionListingResponse;
use App\Domain\Backup\Factory\PublicUrlFactory;
use App\Domain\Backup\Security\VersioningContext;
use App\Domain\Common\ValueObject\BaseUrl;

class VersionsListingHandler
{
    /**
     * @var VersionRepository
     */
    private $versionsRepository;

    public function __construct(VersionRepository $versionRepository, PublicUrlFactory $factory)
    {
        $this->versionsRepository = $versionRepository;
        $this->factory            = $factory;
    }

    /**
     * @param VersionsListingForm $form
     * @param VersioningContext $securityContext
     * @param BaseUrl $baseUrl
     *
     * @return VersionListingResponse
     *
     * @throws AuthenticationException
     */
    public function handle(
        VersionsListingForm $form,
        VersioningContext $securityContext,
        BaseUrl $baseUrl
    ): VersionListingResponse {

        if (!$form->collection) {
            return VersionListingResponse::createWithNotFoundError();
        }

        $this->assertHasRights($securityContext, $form->collection);

        return VersionListingResponse::fromCollection(
            $this->versionsRepository->findCollectionVersions($form->collection),
            function (StoredVersion $version) use ($baseUrl) { return $this->factory->getUrlForVersion($version, $baseUrl); }
        );
    }

    /**
     * @param VersioningContext $securityContext
     * @param BackupCollection  $collection
     *
     * @throws AuthenticationException
     */
    private function assertHasRights(VersioningContext $securityContext, BackupCollection $collection): void
    {
        if (!$securityContext->canListCollectionVersions($collection)) {
            throw new AuthenticationException(
                'Current token does not allow to list versions in this collection',
                AuthenticationException::CODES['not_authenticated']
            );
        }
    }
}
