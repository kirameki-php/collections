<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use Countable;
use JsonSerializable;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\NotSupportedException;
use Random\Randomizer;
use function assert;
use function is_array;
use const SORT_REGULAR;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Enumerator<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 * @phpstan-consistent-constructor
 */
class Map extends Enumerator implements ArrayAccess, Countable, JsonSerializable
{
    /**
     * @template TNewValue
     * @param TNewValue ...$values
     * @return self<string, TNewValue>
     */
    public static function of(mixed ...$values): self
    {
        /** @var array<string, TNewValue> $values */
        return new self($values);
    }

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        assert(is_array($this->items));

        return $this->items[$offset];
    }

    /**
     * @param TKey $offset
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
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) Arr::from($this);
    }

    /**
     * @param iterable<int, TKey> $keys
     * @return bool
     */
    public function containsAllKeys(iterable $keys): bool
    {
        return Arr::containsAllKeys($this, $keys);
    }

    /**
     * @param iterable<int, TKey> $keys
     * @return bool
     */
    public function containsAnyKeys(iterable $keys): bool
    {
        return Arr::containsAnyKeys($this, $keys);
    }

    /**
     * @param TKey $key
     * @return bool
     */
    public function containsKey(mixed $key): bool
    {
        return Arr::containsKey($this, $key);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diffKeys(iterable $items): static
    {
        return $this->instantiate(Arr::diffKeys($this, $items));
    }

    /**
     * @param TKey $key
     * @return bool
     */
    public function doesNotContainKey(mixed $key): bool
    {
        return Arr::doesNotContainKey($this, $key);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey
     */
    public function firstKey(?Closure $condition = null): mixed
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function firstKeyOrNull(?Closure $condition = null): mixed
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
        return $this->instantiate(Arr::intersectKeys($this, $items));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey
     */
    public function lastKey(?Closure $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function lastKeyOrNull(?Closure $condition = null): mixed
    {
        return Arr::lastKeyOrNull($this, $condition);
    }

    /**
     * @inheritDoc
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newMap(Arr::map($this, $callback));
    }

    /**
     * @return MapMutable<TKey, TValue>
     */
    public function mutable(): MapMutable
    {
        return new MapMutable($this->items);
    }

    /**
     * @param Randomizer|null $randomizer
     * @return TKey
     */
    public function sampleKey(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleKey($this, $randomizer);
    }

    /**
     * @param Randomizer|null $randomizer
     * @return TKey|null
     */
    public function sampleKeyOrNull(?Randomizer $randomizer = null): mixed
    {
        /** @var TKey|null needed for some reason by phpstan */
        return Arr::sampleKeyOrNull($this, $randomizer);
    }

    /**
     * @param int $amount
     * @param bool $replace
     * @param Randomizer|null $randomizer
     * @return Vec<TKey>
     */
    public function sampleKeys(int $amount, bool $replace = false, ?Randomizer $randomizer = null): Vec
    {
        return $this->newVec(Arr::sampleKeys($this, $amount, $replace, $randomizer));
    }

    /**
     * @param bool $ascending
     * @param int $flag
     * @return static
     */
    public function sortByKey(bool $ascending, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKey($this, $ascending, $flag));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeyAsc(int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKeyAsc($this, $flag));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeyDesc(int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKeyDesc($this, $flag));
    }

    /**
     * @param Closure(TKey, TKey): int $comparison
     * @return static
     */
    public function sortWithKey(Closure $comparison): static
    {
        return $this->instantiate(Arr::sortWithKey($this, $comparison));
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
     * @return bool
     */
    protected function reindex(): bool
    {
        return true;
    }
}
