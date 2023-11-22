# FGTCLB HTTP API

This package provides an HTTP API to access data of the community portal.

## Routing

Routes can be registered for a specific API version via `ext_localconf.php`:

    \FGTCLB\HttpApi\Api::version(1)->addRouteWithRoles('posts', [
        'get' => \Acme\Blog\Controller\Api\PostsApiController::class . '::index',
    ], [
        // Required role(s) to access this route; having any of this roles is sufficient (OR)
        new \FGTCLB\HttpApi\Http\GlobalApiRole(\FGTCLB\HttpApi\Http\GlobalApiRole::AUTHENTICATED_USER),
    ]);

All HTTP methods (e.g. `GET`, `POST`, ...) can be connected to an endpoint which is represented by a class and method name.

## Parameters

Routes can be registered with parameters:

    \FGTCLB\HttpApi\Api::version(1)->addRouteWithRoles('posts/{post<\d+>}', [
        'get' => \Acme\Blog\Controller\Api\PostsApiController::class . '::getPost',
    ], [
        // Required role(s) to access this route; having any of this roles is sufficient (OR)
        new \FES\HttpApi\Http\GlobalApiRole(\FES\HttpApi\Http\GlobalApiRole::AUTHENTICATED_USER),
    ]);

As seen here parameters should always have requirements if possible to prevent conflicts with similarly named routes. See [route parameters in the Symfony Routing component](https://symfony.com/doc/current/routing.html#route-parameters) for details.

Endpoints can then access their parameters:

    $request->getAttribute('api.params')->get('post');

Notice that this method will automatically trigger an API error response if a parameter was not set.

## Access

All registered routes can be accessed via `/api/v<version>/<route>`. In the example above accessing `/api/v1/posts` via `GET` will call the `index` method of the `PostsApiController`.

By default all API routes require a valid and authenticated frontend user.

A successful response will look like this:

    {
        "success": true,
        "data": {

        }
    }

The content of `data` depends on the requested API route.

An error response will look like this:

    {
        "success": false,
        "error": "Short error message"
    }

In addition a suitable HTTP status code will be set on the response.
