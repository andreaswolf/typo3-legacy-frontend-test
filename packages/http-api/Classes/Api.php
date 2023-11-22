<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi;

use FGTCLB\HttpApi\Http\GlobalApiRole;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Entrypoint for API tasks
 *
 * @phpstan-type THttpMethod 'POST'|'post'|'GET'|'get'|'PUT'|'put'|'DELETE'|'delete'|'OPTIONS'|'options'
 */
final class Api implements SingletonInterface
{
    /**
     * @var Api[]
     */
    protected static $apis = [];

    /**
     * @var int
     */
    protected $version;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @param int $version
     */
    private function __construct(int $version)
    {
        $this->version = $version;
        $this->routes = new RouteCollection();
    }

    /**
     * Retrieve a version of the API
     *
     * @param int $version the API version to retrieve
     * @return Api
     */
    public static function version(int $version): self
    {
        if (!isset(self::$apis[$version])) {
            self::$apis[$version] = new Api($version);
        }

        return self::$apis[$version];
    }

    /**
     * Retrieve the list of registered APIs
     *
     * @return Api[]
     */
    public static function all(): array
    {
        return array_values(self::$apis);
    }

    /** @internal Only to be called in tests, no public API */
    public static function reset(): void
    {
        self::$apis = [];
    }

    public static function allRoutesWithPrefies(): RouteCollection
    {
        $routes = new RouteCollection();

        foreach (self::all() as $api) {
            $routesFromVersion = $api->getRoutes();

            foreach ($routesFromVersion as $name => $route) {
                // adding the route with the version prefixed to the name since other routes might have the same name;
                // a clash would result in the first route being overwritten
                $routes->add(
                    sprintf('v%d/%s', $api->getVersion(), $name),
                    $route
                );
            }
        }

        return $routes;
    }

    /**
     * Get the version of this API
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Add an API route
     *
     * @param string $path API route path
     * @param array<THttpMethod, callable> $endpoints map of HTTP methods and callbacks (e.g. Controller::method)
     * @deprecated use AddRouteWithRoles() instead
     */
    public function addRoute(string $path, array $endpoints, bool $authenticationRequired = true): void
    {
        if ($authenticationRequired === true) {
            $roles = [
                new GlobalApiRole(GlobalApiRole::AUTHENTICATED_USER)
            ];
        } else {
            $roles = [
                new GlobalApiRole(GlobalApiRole::ANY_USER)
            ];
        }
        $this->addRouteWithRoles($path, $endpoints, $roles);
    }

    /**
     * Adds an API route with roles
     *
     * @param string $path API route path
     * @param array<THttpMethod, callable> $endpoints map of HTTP methods and callbacks (e.g. Controller::method)
     * @param array<int, ApiRole> $requiredRoles The names of roles that are required for accessing this
     */
    public function addRouteWithRoles(string $path, array $endpoints, array $requiredRoles): void
    {
        // TODO the authentication requirement could be per-method, so we can e.g. block POST, but allow GET for
        //      unauthenticated users
        foreach ($endpoints as $method => $controller) {
            $roleNames = array_map(
                static fn (ApiRole $role) => $role->getFullRoleName(),
                $requiredRoles
            );
            $route = new Route(
                '/' . ltrim($path, '/'),
                [
                    '_controller' => $controller,
                    '_requiredRoles' => $roleNames
                ]
            );
            $route->setMethods([$method]);

            $this->routes->add(sprintf('%s::%s', $path, $method), $route);
        }
    }

    /**
     * Get the collection of routes
     *
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        $routes = clone $this->routes;
        $routes->addPrefix(sprintf('/v%d', $this->version));

        return $routes;
    }
}
