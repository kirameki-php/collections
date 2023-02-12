<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use ArrayAccess;
use Closure;
use SouthPointe\Collections\Utils\Arr;
use SouthPointe\Collections\Utils\Iter;
use SouthPointe\Core\Exceptions\NotSupportedException;
use function assert;
use function is_array;

/**
 * @template TValue
 * @extends Seq<int, TValue>
 * @implements ArrayAccess<int, TValue>
 */
class Vec extends Seq implements ArrayAccess
{
    /**
     * @param iterable<int, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct($items, true);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        assert(is_array($this->items));

        return $this->items[$offset];
    }

    /**
     * @param int|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotSupportedException('Calling offsetSet on non-mutable class: ' . static::class, [
            'this' => $this,
            'offset' => $offset,
        ]);
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new NotSupportedException('Calling offsetUnset on non-mutable class: ' . static::class, [
            'this' => $this,
            'offset' => $offset,
        ]);
    }

    /**
     * @param TValue ...$value
     * @return static
     */
    public function append(mixed ...$value): static
    {
        return $this->instantiate(Arr::append($this->items, ...$value));
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
        return $this->newVec(Iter::keys($this));
    }

    /**
     * @inheritDoc
     * @return self<int>
     */
    public function keys(): self
    {
        return $this->indices();
    }

    /**
     * @inheritDoc
     * @template TMapValue
     * @param Closure(TValue, int): TMapValue $callback
     * @return self<TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newVec(Iter::map($this, $callback));
    }

    /**
     * @return VecMutable<TValue>
     */
    public function mutable(): VecMutable
    {
        return new VecMutable($this->items);
    }

    /**
     * @param int $size
     * @param TValue $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->instantiate(Arr::pad($this, $size, $value));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->instantiate(Arr::repeat($this, $times));
    }

    /**
     * @param mixed ...$value
     * @return static
     */
    public function prepend(mixed ...$value): static
    {
        return $this->instantiate(Arr::prepend($this->items, ...$value));
    }
}
