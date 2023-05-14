<?php declare(strict_types=1);

namespace Kirameki\Collections;

use IteratorAggregate;
use Traversable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class LazyIterator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        protected iterable $items,
    )
    {
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
