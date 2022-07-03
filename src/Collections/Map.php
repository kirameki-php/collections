<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use Kirameki\Utils\Arr;

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
        $array = Arr::from($items ?? []);
        parent::__construct($array, false);
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
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function firstKey(?Closure $condition = null): int|string|null
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @param int|string $index
     * @return TValue|null
     */
    public function get(int|string $index): mixed
    {
        return Arr::get($this, $index);
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
     * @param int|string $key
     * @return TValue
     */
    public function getOrFail(int|string $key): mixed
    {
        return Arr::getOrFail($this, $key);
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
     * @return static<TKey>
     */
    public function keys(): static
    {
        return $this->newInstance(Arr::keys($this));
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
     * @param int|string $key
     * @return bool
     */
    public function notContainsKey(int|string $key): bool
    {
        return Arr::notContainsKey($this, $key);
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
     * @param bool|null $result
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value, ?bool &$result = null): static
    {
        $result !== null
            ? Arr::setIfExists($this->items, $key, $value, $result)
            : Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @param bool|null $result
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, ?bool &$result = null): static
    {
        $result !== null
            ? Arr::setIfNotExists($this->items, $key, $value, $result)
            : Arr::setIfNotExists($this->items, $key, $value);
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
    public function values(): Vec
    {
        return new Vec(Arr::values($this));
    }
}
