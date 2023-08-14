<?php declare(strict_types=1);

namespace Kirameki\Collections;

use IteratorAggregate;
use Traversable;

/**
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
final readonly class LazyIterator implements IteratorAggregate
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
