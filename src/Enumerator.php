<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use IteratorAggregate;
use Kirameki\Collections\Utils\Arr;
use Traversable;
use const PHP_INT_MAX;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @phpstan-consistent-constructor
 */
abstract class Enumerator implements IteratorAggregate
{
    /** @use Enumerable<TKey, TValue> */
    use Enumerable;

    /**
     * @var iterable<TKey, TValue> $items
     */
    protected iterable $items;

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        iterable $items = [],
    )
    {
        if (!$items instanceof LazyIterator) {
            $items = Arr::from($items);
        }

        $this->items = $items;
    }

    /**
     * @return static
     */
    public static function empty(): static
    {
        return new static();
    }

    /**
     * @param int $times
     * @return static
     */
    public static function loop(int $times = PHP_INT_MAX): static
    {
        $generator = (function(int $times) {
            $counter = 0;
            while($counter < $times) {
                yield;
            }
        })($times);
        return new static(new LazyIterator($generator));
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function instantiate(mixed $items): static
    {
        if ($this->isLazy()) {
            $items = new LazyIterator($items);
        }
        return new static($items);
    }

    /**
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->items instanceof LazyIterator;
    }

    /**
     * @return bool
     */
    public function isEager(): bool
    {
        return ! $this->isLazy();
    }

    /**
     * @return static
     */
    public function lazy(): static
    {
        return $this->instantiate(new LazyIterator($this->items));
    }

    /**
     * @param Closure(static): mixed $callback
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this);
        return $this;
    }
}
