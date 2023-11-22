<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Resource;

/**
 * A collection of resources.
 *
 * The main use case for this interface is the anonymous implementation generated in
 * {@see AbstractResource::__construct()}, however, you can also roll your own implementation in case you need special
 * features.
 *
 * @template T of ResourceInterface
 */
interface ResourceCollection
{
    /**
     * @return list<array<string, mixed>> TODO this type should be defined by the resourceClass
     */
    public function toArray();
}
