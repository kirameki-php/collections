<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use SouthPointe\Collections\Utils\Arr;

/**
 * @template TKey of array-key
 * @template TValue
 */
trait MutatesSelf
{
    /**
     * @var bool
     */
    protected bool $isList;

    /**
     * @var array<TKey, TValue>
     */
    protected iterable $items;

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public abstract function newInstance(iterable $iterable): static;

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return TValue
     */
    public function pop(): mixed
    {
        return Arr::pop($this->items);
    }

    /**
     * @return TValue|null
     */
    public function popOrNull(): mixed
    {
        return Arr::popOrNull($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function popMany(int $amount): static
    {
        return $this->newInstance(Arr::popMany($this->items, $amount));
    }

    /**
     * @param TKey $key
     * @return TValue
     */
    public function pull(int|string $key): mixed
    {
        return Arr::pull($this->items, $key, $this->isList);
    }

    /**
     * @template TDefault
     * @param TKey $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function pullOr(int|string $key, mixed $default): mixed
    {
        return Arr::pullOr($this->items, $key, $default, $this->isList);
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function pullOrNull(int|string $key): mixed
    {
        return Arr::pullOrNull($this->items, $key, $this->isList);
    }

    /**
     * @param iterable<TKey> $keys
     * @return static
     */
    public function pullMany(iterable $keys): static
    {
        return $this->newInstance(Arr::pullMany($this->items, $keys, $this->isList));
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        return Arr::remove($this->items, $value, $limit, $this->isList);
    }

    /**
     * @return TValue
     */
    public function shift(): mixed
    {
        return Arr::shift($this->items);
    }

    /**
     * @return TValue|null
     */
    public function shiftOrNull(): mixed
    {
        return Arr::shiftOrNull($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function shiftMany(int $amount): static
    {
        return $this->newInstance(Arr::shiftMany($this->items, $amount));
    }
}
