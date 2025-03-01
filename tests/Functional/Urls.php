<?php declare(strict_types=1);

namespace Tests;

class Urls
{
    public const URL_TOKEN_GENERATE = '/auth/token/generate';
    public const URL_TOKEN_LOOKUP = '/auth/token/{{ token }}';
    public const URL_TOKEN_DELETE = '/auth/token/{{ token }}';
    public const JOB_CLEAR_EXPIRED_TOKENS = '/jobs/token/expired/clear';
    public const ROLES_LISTING = '/auth/roles';

    public const URL_REPOSITORY_FILE_UPLOAD = '/repository/file/upload';
    public const URL_REPOSITORY_UPLOAD_RAW  = '/repository/file/upload';
    public const URL_REPOSITORY_UPLOAD_BY_URL = '/repository/image/add-by-url';
    public const URL_REPOSITORY_DELETE_FILE   = '/repository/file/{{ fileName }}';

    public const URL_REPOSITORY_LISTING = '/repository';

    public const URL_COLLECTION_CREATE = '/repository/collection';
    public const URL_COLLECTION_UPDATE = '/repository/collection';
    public const URL_COLLECTION_DELETE = '/repository/collection/{{ id }}';
    public const URL_COLLECTION_FETCH  = '/repository/collection/{{ id }}';
    public const URL_COLLECTION_LISTING = '/repository/collection';
    public const URL_COLLECTION_UPLOAD = '/repository/collection/{{ collectionId }}/backup';
    public const URL_COLLECTION_LIST_VERSIONS = '/repository/collection/{{ collectionId }}/version';
    public const URL_COLLECTION_DOWNLOAD_VERSION = '/repository/collection/{{ collectionId }}/version/{{ version }}';

    public const URL_COLLECTION_GRANT_TOKEN = '/repository/collection/{{ collectionId }}/token';
    public const URL_COLLECTION_REVOKE_TOKEN = '/repository/collection/{{ collectionId }}/token/{{ tokenId }}';
}
