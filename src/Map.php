<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use JsonSerializable;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Core\Exceptions\NotSupportedException;
use Random\Randomizer;
use const SORT_REGULAR;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Enumerator<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 * @phpstan-consistent-constructor
 */
class Map extends Enumerator implements ArrayAccess, JsonSerializable
{
    /**
     * @return array<TKey, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        $items = &$this->items;

        if (is_array($items)) {
            return $items;
        }

        $innerType = get_debug_type($items);
        throw new NotSupportedException("Map's inner item must be of type array|ArrayAccess, {$innerType} given.", [
            'this' => $this,
            'items' => $items,
        ]);
    }

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        $ref = $this->getItemsAsRef();
        return isset($ref[$offset]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        $ref = $this->getItemsAsRef();
        return $ref[$offset];
    }

    /**
     * @param int|string|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) Arr::from($this);
    }

    /**
     * @return MapMutable<TKey, TValue>
     */
    public function mutable(): MapMutable
    {
        return new MapMutable($this->items);
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
     * @param int $index
     * @return int|string
     */
    public function keyAt(int $index): int|string
    {
        return Arr::keyAt($this, $index);
    }

    /**
     * @param int $index
     * @return string|int|null
     */
    public function keyAtOrNull(int $index): string|int|null
    {
        return Arr::keyAtOrNull($this, $index);
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
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newMap(Arr::map($this, $callback));
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
     * @param bool $result
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value, bool &$result = false): static
    {
        $result = Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @param bool $result
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, bool &$result = false): static
    {
        $result = Arr::setIfNotExists($this->items, $key, $value);
        return $this;
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
     * @return Vec<TValue>
     */
    public function values(): Vec
    {
        return $this->newVec(Iter::values($this));
    }

    /**
     * @inheritDoc
     */
    protected function reindex(): bool
    {
        return false;
    }
}
