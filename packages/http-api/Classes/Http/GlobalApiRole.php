<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Http;

use FGTCLB\HttpApi\ApiRole;

final class GlobalApiRole extends ApiRole
{
    public static function getNamespace(): string
    {
        return 'global';
    }

    /** Authenticated and unauthenticated users */
    public const ANY_USER = 'any-user';

    /** All authenticated users */
    public const AUTHENTICATED_USER = 'authenticated-user';
}
