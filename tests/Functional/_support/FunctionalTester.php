<?php

use Tests\Urls;

require_once __DIR__ . '/../Urls.php';


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;
    use \Codeception\Util\Shared\Asserts;

    public function amAdmin(): void
    {
        $this->amToken('test-token-full-permissions');
    }

    public function amToken(string $token): void
    {
        $this->haveHttpHeader('token', $token);
    }

    public function haveRoles(array $roles, array $params = []): string
    {
        $this->amAdmin();

        $token = $this->createToken(
            array_merge(
                ['roles' => $roles],
                $params
            )
        );

        $this->amToken($token);

        return $token;
    }

    public function postJson(string $url, $params = null, $files = []): void
    {
        $this->haveHttpHeader('Content-Type', 'application/json');
        $this->sendPOST($url, $params, $files);
    }

    public function putJson(string $url, $params = null, $files = []): void
    {
        $this->haveHttpHeader('Content-Type', 'application/json');
        $this->sendPUT($url, $params, $files);
    }

    public function lookupToken(string $tokenId): void
    {
        $this->sendGET(
            $this->fill(
                Urls::URL_TOKEN_LOOKUP,
                ['token' => $tokenId]
            )
        );
    }

    public function createToken(array $data): string
    {
        $this->postJson(Urls::URL_TOKEN_GENERATE,
            \array_merge(
                [
                    'roles' => [],
                    'data' => []
                ],
                $data
            )
        );

        return $this->grabDataFromResponseByJsonPath('.tokenId')[0] ?? '';
    }

    public function deleteToken(string $tokenId): void
    {
        $this->sendDELETE(
            $this->fill(
                Urls::URL_TOKEN_DELETE,
                ['token' => $tokenId]
            )
        );
    }

    public function uploadByUrl(string $url, array $overrideParams = []): void
    {
        $templateParams = [
            'fileUrl' => $url,
            'tags'    => [],
            'public'  => true
        ];

        $params = \array_merge(
            $templateParams,
            ['fileUrl' => $url],
            $overrideParams
        );

        $this->sendPOST(Urls::URL_REPOSITORY_UPLOAD_BY_URL, $params);
    }

    public function uploadByPayload(string $payload, array $params = []): void
    {
        $this->sendPOST(Urls::URL_REPOSITORY_UPLOAD_RAW . '?' . http_build_query($params), $payload);
    }

    public function deleteFile(string $fileName, array $params = []): void
    {
        $this->sendDELETE(
            $this->fill(
                Urls::URL_REPOSITORY_DELETE_FILE,
                ['fileName' => $fileName]
            ),
            $params
        );
    }

    public function listFiles(array $params = []): void
    {
        $this->sendGET(Urls::URL_REPOSITORY_LISTING, $params);
    }

    public function createCollection(array $params): string
    {
        $this->postJson(Urls::URL_COLLECTION_CREATE, $params);

        return $this->grabDataFromResponseByJsonPath('collection.id')[0] ?? '';
    }

    public function updateCollection(string $id, array $params): void
    {
        $params['collection'] = $id;

        $this->putJson(Urls::URL_COLLECTION_UPDATE, $params);
    }

    public function uploadToCollection(string $id, string $contents): void
    {
        $this->sendPOST(
            $this->fill(
                Urls::URL_COLLECTION_UPLOAD,
                ['collectionId' => $id]
            ),
            $contents
        );
    }

    public function browseCollectionVersions(string $id): void
    {
        $this->sendGET(
            $this->fill(
                Urls::URL_COLLECTION_LIST_VERSIONS,
                ['collectionId' => $id]
            )
        );
    }

    public function downloadCollectionVersion(string $id, string $version): void
    {
        $this->sendGET(
            $this->fill(
                Urls::URL_COLLECTION_DOWNLOAD_VERSION,
                ['collectionId' => $id, 'version' => $version]
            )
        );
    }

    public function deleteCollection(string $id): void
    {
        $this->sendDELETE(
            $this->fill(
                Urls::URL_COLLECTION_DELETE,
                ['id' => $id]
            )
        );
    }

    public function fetchCollection(string $id): void
    {
        $this->sendGET(
            $this->fill(
                Urls::URL_COLLECTION_FETCH,
                ['id' => $id]
            )
        );
    }

    public function searchCollectionsFor(array $params): void
    {
        $this->sendGET(Urls::URL_COLLECTION_LISTING, $params);
    }

    public function expectToSeeCollectionsAmountOf(int $expectedAmount): void
    {
        $elements = $this->grabDataFromResponseByJsonPath('.elements')[0] ?? [];

        $this->assertEquals($expectedAmount, \count($elements));
    }

    public function grantTokenAccessToCollection(string $collectionId, string $tokenId): void
    {
        $this->postJson(
            $this->fill(
                Urls::URL_COLLECTION_GRANT_TOKEN,
                ['collectionId' => $collectionId]
            ),
            ['token' => $tokenId]
        );
    }

    public function revokeAccessToCollection(string $collectionId, string $tokenId): void
    {
        $this->sendDELETE(
            $this->fill(
                Urls::URL_COLLECTION_REVOKE_TOKEN,
                [
                    'collectionId' => $collectionId,
                    'tokenId' => $tokenId
                ]
            )
        );
    }
}
