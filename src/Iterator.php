<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use IteratorAggregate;
use SouthPointe\Collections\Utils\Arr;
use Traversable;
use function iterator_to_array;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Iterator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        protected iterable $items = [],
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

    /**
     * @param int|null $steps
     * @return void
     */
    public function iterate(int $steps = null): void
    {
        if ($steps === null) {
            iterator_to_array($this);
            return;
        }

        if ($steps === 0) {
            return;
        }

        $count = 0;
        foreach ($this as $_) {
            $count++;
            if ($steps === $count) {
                break;
            }
        }
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return Arr::from($this);
    }
}
