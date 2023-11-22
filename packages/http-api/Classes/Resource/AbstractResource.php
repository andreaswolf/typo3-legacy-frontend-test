<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Resource;

/**
 * Base for API resources
 */
abstract class AbstractResource implements ResourceInterface
{
    /**
     * Create a list of instances of this resource and convert them into an array automatically.
     *
     * TODO annotate this method additionally with @return ResourceCollection<T> once PhpStan supports this;
     *      see https://github.com/phpstan/phpstan/issues/8770 for more info and
     *      https://phpstan.org/r/7c276bd1-a059-4edc-9e2e-5954b3b66f0d for an example.
     *
     * TODO refine the return type to reflect that we get items of type T and then return a collection of resources for
     *      that type: T => C<I<T>> with C being the collection, I the item and T the wrapped inner type
     *
     * @template T
     * @param iterable<T> $items
     * @return ResourceInterface
     */
    public static function collection(iterable $items): ResourceInterface
    {
        return new class(static::class, $items) extends AbstractCollection {
        };
    }
}
