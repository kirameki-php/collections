<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Countable;
use IteratorAggregate;
use Kirameki\Collections\Utils\Arr;
use Traversable;
use function count;
use function is_countable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @phpstan-consistent-constructor
 */
abstract class Enumerator implements Countable, IteratorAggregate
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
     * @inheritDoc
     *
     * NOTE: Overridden to prevent calling Arr::count() directly since it calls count()
     * internally if the given `$iterable` implements Countable, which will call itself
     * again and cause an infinite loop.
     */
    public function count(?Closure $condition = null): int
    {
        return $condition === null && is_countable($this->items)
            ? count($this->items)
            : Arr::count($this->items, $condition);
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
        return new static($items);
    }

    /**
     * @return static
     */
    public function eager(): static
    {
        return $this->instantiate(Arr::from($this));
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
