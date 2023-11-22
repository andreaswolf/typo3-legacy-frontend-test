<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * Defines the roles for API accesses in a package.
 *
 * Roles are namespaced, to prevent naming clashes. The full role name is "<namespace>:<role>".
 * Each instance of this class defines one namespace with an arbitrary number of roles.
 */
abstract class ApiRole extends Enumeration
{
    /**
     * Returns the namespace (usually the extension name).
     */
    abstract public static function getNamespace(): string;

    /**
     * @return array<int, string>
     */
    public static function getFullRoleNames(): array
    {
        return array_values(array_map(
            fn (string $role) => sprintf('%s:%s', static::getNamespace(), $role),
            static::getConstants()
        ));
    }

    /**
     * @return array<int, static>
     */
    public static function getAll(): array
    {
        return array_values(array_map(
            // @phpstan-ignore-next-line PHPStan will complain about the next line, but using new static() is the only way here
            static fn (string $role) => new static($role),
            static::getConstants()
        ));
    }

    public function getFullRoleName(): string
    {
        return sprintf('%s:%s', static::getNamespace(), $this->value);
    }

    public function __toString(): string
    {
        return $this->getFullRoleName();
    }
}
