<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use ArrayAccess;
use Closure;
use SouthPointe\Collections\Utils\Arr;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Enumerable<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class Map extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable<TKey, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct($items, false);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function containsKey(int|string $key): bool
    {
        return Arr::containsKey($this, $key);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diffKeys(iterable $items): static
    {
        return $this->newInstance(Arr::diffKeys($this, $items));
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function doesNotContainKey(int|string $key): bool
    {
        return Arr::doesNotContainKey($this, $key);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey
     */
    public function firstKey(?Closure $condition = null): int|string|null
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function firstKeyOrNull(?Closure $condition = null): int|string|null
    {
        return Arr::firstKeyOrNull($this, $condition);
    }

    /**
     * @param int|string $key
     * @return TValue
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this, $key);
    }

    /**
     * @template TDefault
     * @param int|string $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function getOr(int|string $key, mixed $default): mixed
    {
        return Arr::getOr($this, $key, $default);
    }

    /**
     * @param int|string $index
     * @return TValue|null
     */
    public function getOrNull(int|string $index): mixed
    {
        return Arr::getOrNull($this, $index);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersectKeys(iterable $items): static
    {
        return $this->newInstance(Arr::intersectKeys($this, $items));
    }

    /**
     * @return Vec<TKey>
     */
    public function keys(): Vec
    {
        return $this->newVec(Arr::keys($this));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return mixed
     */
    public function lastKey(?Closure $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return Map<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newMap(Arr::map($this, $callback));
    }

    /**
     * @param TKey $key
     * @return bool
     */
    public function removeKey(int|string $key): bool
    {
        return Arr::removeKey($this->items, $key);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function set(int|string $key, mixed $value): static
    {
        Arr::set($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value): static
    {
        Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value): static
    {
        Arr::setIfNotExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param string|null $namespace
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        return Arr::toUrlQuery($this, $namespace);
    }

    /**
     * @return Vec<TValue>
     */
    public function toVec(): Vec
    {
        return $this->newVec(Arr::values($this));
    }
}
