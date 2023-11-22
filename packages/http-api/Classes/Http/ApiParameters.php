<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Http;

final class ApiParameters
{
    /**
     * @var array[]
     */
    protected $parameters;

    public function __construct(array $routeParameters)
    {
        unset(
            $routeParameters['_controller'],
            $routeParameters['_route']
        );

        $this->parameters = $routeParameters;
    }

    /**
     * @return string|int|mixed
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw UndefinedApiParameterException::forParameter($name);
        }

        return $this->parameters[$name];
    }
}
