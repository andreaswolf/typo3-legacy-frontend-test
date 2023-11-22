<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Controller;

use FGTCLB\HttpApi\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Fallback controller for invalid API routes
 */
final class InvalidApiRouteController
{
    public function process(ServerRequestInterface $request): ApiResponse
    {
        return ApiResponse::error('Invalid API route', 404);
    }
}
