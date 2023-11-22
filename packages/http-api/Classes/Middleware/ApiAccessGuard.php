<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Middleware;

use FGTCLB\HttpApi\ApiRole;
use FGTCLB\HttpApi\Http\ApiResponse;
use FGTCLB\HttpApi\RolesProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Access guard for API requests
 *
 * Ensures that there is an authenticated frontend user for the current request.
 */
final class ApiAccessGuard implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string|null $controller */
        $controller = $request->getAttribute('api.controller', null);

        if ($controller === null) {
            return $handler->handle($request);
        }
        $requiredRoles = $request->getAttribute('api.requiredRoles', []);

        // this is required on v9 because we need the groups initialised for getting the roles
        // on v11 this is not required anymore, and also does not work anymore because the TSFE initialization runs
        // much later in the process
        if (isset($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE']->initUserGroups();
        }

        $roles = $this->getRoleNamesForCurrentUser();
        if (array_diff($requiredRoles, $roles) !== []) {
            return ApiResponse::error('Access denied', 403);
        }

        return $handler->handle($request);
    }

    /**
     * @return array<int, ApiRole|string>
     */
    private function getRoleNamesForCurrentUser(): array
    {
        $roles = [];
        /** @var array<int, class-string<RolesProvider>> $rolesProviders */
        $rolesProviders = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['http_api']['rolesProviders'] ?? [];

        foreach ($rolesProviders as $providerClass) {
            /** @var RolesProvider $instance */
            $instance = GeneralUtility::makeInstance($providerClass);

            $roles[] = $instance->getRolesForCurrentUser();
        }
        $flattenedRoles = array_merge(...$roles);
        return array_map(
            static fn (ApiRole $role) => $role->getFullRoleName(),
            $flattenedRoles
        );
    }
}
