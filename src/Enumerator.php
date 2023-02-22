<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use IteratorAggregate;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Traversable;

/**
 * @phpstan-consistent-constructor
 *
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Enumerator implements IteratorAggregate
{
    /** @use Enumerable<TKey, TValue> */
    use Enumerable;

    /**
     * @var iterable<TKey, TValue> $items
     */
    protected iterable $items;

    /**
     * @param iterable<TKey, TValue> $items
     * @param bool $reindex
     */
    public function __construct(
        iterable $items = [],
        bool $reindex = false,
    )
    {
        if (!$items instanceof LazyIterator) {
            $items = Arr::from($items);
        }

        $this->items = $items;
        $this->reindex = $reindex;
    }

    /**
     * @return static
     */
    public static function empty(): static
    {
        return new static();
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
     * @return Vec<TKey>
     */
    public function keys(): Vec
    {
        return $this->newVec(Iter::keys($this));
    }

    /**
     * @return static
     */
    public function lazy(): static
    {
        return $this->instantiate(new LazyIterator($this->items));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return new self(Iter::map($this, $callback));
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

    /**
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self(Iter::values($this));
    }
}
