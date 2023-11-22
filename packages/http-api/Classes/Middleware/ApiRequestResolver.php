<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Middleware;

use FGTCLB\HttpApi\Http\ApiResponse;
use FGTCLB\HttpApi\Http\UndefinedApiParameterException;
use OpenTracing\GlobalTracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tideways\Profiler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolver for API requests
 */
final class ApiRequestResolver implements MiddlewareInterface
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
        $controller = $request->getAttribute('api.controller', null);

        if (!$controller) {
            return $handler->handle($request);
        }

        $span = GlobalTracer::get()->getActiveSpan();
        $span->setTag('endpoint', 'http-api');
        $span->setTag('api.action', $controller);
        if (class_exists('Tideways\\Profiler')) {
            Profiler::setTransactionName($controller);
        }

        /** @var class-string<mixed> $controller */
        [$controller, $method] = explode('::', $controller);

        try {
            return GeneralUtility::makeInstance($controller)->$method($request);
        } catch (UndefinedApiParameterException $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
