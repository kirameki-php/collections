<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Map<TKey, TValue>
 */
class MapMutable extends Map
{
    /**
     * @use MutatesSelf<TKey, TValue>
     */
    use MutatesSelf;

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct(Arr::from($items));
    }

    /**
     * @return Map<TKey, TValue>
     */
    public function immutable(): Map
    {
        return new Map($this->items);
    }
}
