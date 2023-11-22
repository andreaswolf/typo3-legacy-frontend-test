<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Http;

final class UndefinedApiParameterException extends \UnexpectedValueException
{
    private function __construct(...$arguments)
    {
        parent::__construct(...$arguments);
    }

    public static function forParameter(string $name)
    {
        return new self(sprintf('Missing "%s" API parameter', $name), 1571224725);
    }
}
