<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Middleware;

use FGTCLB\HttpApi\Api;
use FGTCLB\HttpApi\Controller\InvalidApiRouteController;
use FGTCLB\HttpApi\Http\ApiParameters;
use FGTCLB\HttpApi\Http\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\SiteRouteResult;

/**
 * Router for API requests
 */
final class ApiRequestRouter implements MiddlewareInterface
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
        $siteBasePath = '';
        $requestedPath = $request->getUri()->getPath();
        /** @var SiteRouteResult|null $siteRouting */
        $siteRouting = $request->getAttribute('routing');
        if ($siteRouting instanceof SiteRouteResult) {
            // this does not run in Functional Tests, since the routing attribute of $request is not set there
            $requestedPath = $siteRouting->getTail();
            $siteBasePath = rtrim($siteRouting->getSite()->getBase()->getPath(), '/');
        }
        // Simple static check for speed and to avoid side effects for unrelated requests
        if (!Environment::getContext()->isTesting() && strpos($requestedPath, 'api/') !== 0) {
            return $handler->handle($request);
        }

        $closure = function () use ($siteBasePath): RouteCollection {
            $routes = Api::allRoutesWithPrefies();

            // TODO: Bundle this with API route retrieval
            $routes->add('default', new Route(
                '/{path}',
                ['_controller' => InvalidApiRouteController::class . '::process'],
                ['path' => '.+']
            ));

            $routes->addPrefix($siteBasePath . '/api');

            return $routes;
        };

        //Remove files before creating the symfony http request (since symfony file handling collides with the previously applied TYPO3 core processing)
        $symfonyRequest = (new HttpFoundationFactory())->createRequest($request->withUploadedFiles([]));

        $requestContext = (new RequestContext())->fromRequest($symfonyRequest);
        $router = new Router(new ClosureLoader(), $closure, [], $requestContext);

        try {
            $parameters = $router->matchRequest($symfonyRequest);
        } catch (ResourceNotFoundException $e) {
            return $handler->handle($request);
        }

        try {
            if ($siteRouting instanceof SiteRouteResult) {
                $pageId = $siteRouting->getSite()->getRootPageId();
                // this is normally done in \TYPO3\CMS\Frontend\Middleware\PageResolver, which is run before the core's
                // TSFE initialization middleware. Since we run our TypoScriptFrontendSetup before these two core middlewares,
                // we must ensure that the PageArguments are available there
                $request = $request->withAttribute('routing', new PageArguments($pageId, '0', []));
            }
            return $handler->handle(
                $request
                    ->withAttribute('api.controller', $parameters['_controller'])
                    ->withAttribute('api.requiredRoles', $parameters['_requiredRoles'])
                    ->withAttribute('api.params', new ApiParameters($parameters))
            );
        } catch (\Throwable $e) {
            return ApiResponse::exception($e);
        }
    }
}
