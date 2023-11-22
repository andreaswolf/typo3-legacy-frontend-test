<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Resource;

/**
 * Contract for API resources
 */
interface ResourceInterface
{
    /**
     * Convert this resource to a plain array.
     *
     * This is always a map. If you need a list of items, use {@see ResourceCollection} instead
     *
     * @return array<mixed>
     *   TODO this return type should be refined; when defining this as a map array<string, mixed>, all collections
     *        are not covered anymore, since they return array<int, array<string, mixed>>. This becomes especially
     *        problematic with nested collections.
     */
    public function toArray(): array;
}
