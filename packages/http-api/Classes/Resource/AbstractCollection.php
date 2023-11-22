<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Resource;

/**
 * TODO refine the return type to reflect that we get items of type T and then return a collection of resources for
 *      that type: T => C<I<T>> with C being the collection, I the item and T the wrapped inner type
 *
 * @template T
 * @template U of ResourceInterface
 * @implements ResourceCollection<U>
 */
abstract class AbstractCollection implements ResourceInterface, ResourceCollection
{
    /** @var class-string<U> */
    private string $resourceClass;

    /** @var iterable<T> */
    private iterable $items;

    /**
     * @param class-string<U> $resourceClass
     * @param list<T> $items
     */
    public function __construct(string $resourceClass, iterable $items)
    {
        $this->resourceClass = $resourceClass;
        $this->items = $items;
    }

    /**
     * @return list<array<string, mixed>> TODO this type should be defined by the resourceClass
     */
    final public function toArray(): array
    {
        $data = [];

        foreach ($this->items as $item) {
            $data[] = $this->createResource($item)->toArray();
        }

        return $data;
    }

    /**
     * @param T $item
     * @return U
     */
    protected function createResource($item)
    {
        return new $this->resourceClass($item);
    }
}
