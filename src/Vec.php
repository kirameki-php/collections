<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use ArrayAccess;
use Closure;
use SouthPointe\Collections\Utils\Arr;

/**
 * @template TValue
 * @extends Enumerable<int, TValue>
 * @implements ArrayAccess<int, TValue>
 */
class Vec extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable<int, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct($items, true);
    }

    /**
     * @param TValue ...$value
     * @return $this
     */
    public function append(mixed ...$value): static
    {
        Arr::append($this->items, ...$value);
        return $this;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function containsIndex(int $index): bool
    {
        return Arr::containsKey($this, $index);
    }

    /**
     * @param int $index
     * @return bool
     */
    public function doesNotContainIndex(int $index): bool
    {
        return Arr::doesNotContainKey($this, $index);
    }

    /**
     * @param int $index
     * @return TValue
     */
    public function get(int $index): mixed
    {
        return Arr::get($this, $index);
    }

    /**
     * @template TDefault
     * @param int $index
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function getOr(int $index, mixed $default): mixed
    {
        return Arr::getOr($this, $index, $default);
    }

    /**
     * @param int $index
     * @return TValue|null
     */
    public function getOrNull(int $index): mixed
    {
        return Arr::getOrNull($this, $index);
    }

    /**
     * @return self<int>
     */
    public function indices(): self
    {
        return $this->newVec(Arr::keys($this));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, int): TMapValue $callback
     * @return Vec<TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newVec(Arr::map($this, $callback));
    }

    /**
     * @param int $size
     * @param TValue $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->newInstance(Arr::pad($this, $size, $value));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Arr::repeat($this, $times));
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function prepend(mixed ...$value): static
    {
        Arr::prepend($this->items, ...$value);
        return $this;
    }
}
