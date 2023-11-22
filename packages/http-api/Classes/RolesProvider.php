<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi;

use FGTCLB\HttpApi\Middleware\ApiAccessGuard;

/**
 * A roles provider is registered by extensions to convert (e.g.) a user's FE groups to API roles.
 *
 * This is used in @see ApiAccessGuard to perform an access check
 */
interface RolesProvider
{
    /**
     * @return array<int, ApiRole> The roles the user has assigned
     */
    public function getRolesForCurrentUser(): array;
}
