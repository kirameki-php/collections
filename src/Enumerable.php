<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use Closure;
use Countable;
use JsonSerializable;
use SouthPointe\Core\Json;
use Webmozart\Assert\Assert;
use function is_iterable;

/**
 * @phpstan-consistent-constructor
 * @template TKey of array-key
 * @template TValue
 * @extends Iterator<TKey, TValue>
 */
abstract class Enumerable extends Iterator implements Countable, JsonSerializable
{
    protected bool $isList;

    /**
     * @param iterable<TKey, TValue>|null $items
     * @param bool|null $isList
     */
    public function __construct(iterable|null $items = null, ?bool $isList = null)
    {
        $array = Arr::from($items ?? []);
        parent::__construct($array);
        $this->isList = $isList ?? array_is_list($array);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function newInstance(mixed $items): static
    {
        return new static($items);
    }

    /**
     * @return array<TKey, mixed>
     */
    public function jsonSerialize(): array
    {
        $values = [];
        foreach ($this as $key => $item) {
            $values[$key] = ($item instanceof JsonSerializable)
                ? $item->jsonSerialize()
                : $item;
        }
        return $values;
    }

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset(((array) $this->items)[$offset]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return ((array) $this->items)[$offset];
    }

    /**
     * @param TKey|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_array($this->items));

        if ($offset === null) {
            $this->items[] = $value;
        } else {
            Assert::validArrayKey($offset);
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        assert(is_array($this->items));

        unset($this->items[$offset]);
    }

    /**
     * @param int $index
     * @return TValue
     */
    public function at(int $index)
    {
        return Arr::at($this, $index);
    }

    /**
     * @template TDefault
     * @param int $index
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function atOr(int $index, mixed $default)
    {
        return Arr::atOr($this, $index, $default);
    }

    /**
     * @param int $index
     * @return TValue|null
     */
    public function atOrNull(int $index)
    {
        return Arr::atOrNull($this, $index);
    }

    /**
     * @return TValue
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this);
    }

    /**
     * @return TValue|null
     */
    public function coalesceOrNull(): mixed
    {
        return Arr::coalesceOrNull($this);
    }

    /**
     * @param int<1, max> $size
     * @return Vec<static>
     */
    public function chunk(int $size): self
    {
        $chunks = [];
        foreach (Iter::chunk($this, $size, $this->isList) as $chunk) {
            $converted = $this->newInstance($chunk);
            $chunks[] = $converted;
        }
        return $this->newVec($chunks);
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * @param int<1, max> $depth
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->newInstance(Arr::compact($this, $depth, $this->isList));
    }

    /**
     * @param mixed|Closure(TValue, TKey): bool $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return int
     */
    public function count(?Closure $condition = null): int
    {
        return Arr::count($this, $condition);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diff(iterable $items): static
    {
        return $this->newInstance(Arr::diff($this, $items, null, $this->isList));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function doesNotContain(mixed $value): bool
    {
        return Arr::doesNotContain($this, $value);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return $this->newInstance(Iter::dropFirst($this, $amount, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::dropUntil($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::dropWhile($this, $condition, $this->isList));
    }

    /**
     * @return static
     */
    public function duplicates(): static
    {
        return $this->newInstance(Arr::duplicates($this));
    }

    /**
     * @param Closure(TValue, TKey): void $callback
     * @return $this
     */
    public function each(Closure $callback): static
    {
        Arr::each($this, $callback);
        return $this;
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function equals(mixed $items): bool
    {
        if (is_iterable($items)) {
            /** @var iterable<array-key, mixed> $items */
            return $this->toArray() === Arr::from($items);
        }
        return false;
    }

    /**
     * @param array<TKey> $keys
     * @return static
     */
    public function except(iterable $keys): static
    {
        return $this->newInstance(Arr::except($this, $keys, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->newInstance(Arr::filter($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function first(?Closure $condition = null): mixed
    {
        return Arr::first($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey):bool $condition
     * @return int|null
     */
    public function firstIndex(Closure $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function firstOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::firstOr($this, $default, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function firstOrNull(?Closure $condition = null): mixed
    {
        return Arr::firstOrNull($this, $condition);
    }

    /**
     * @template U
     * @param U $initial
     * @param Closure(U, TValue, TKey): U $callback
     * @return U
     */
    public function fold(mixed $initial, Closure $callback): mixed
    {
        return Arr::fold($this, $initial, $callback);
    }

    /**
     * @template TGroupKey of array-key
     * @param Closure(TValue, TKey): TGroupKey $callback
     * @return Map<TGroupKey, static>
     */
    public function groupBy(Closure $callback): Map
    {
        $grouped = Arr::groupBy($this, $callback, $this->isList);
        return $this->newMap($grouped)->map(fn($group) => $this->newInstance($group));
    }

    /**
     * @param int $at
     * @param mixed $value
     * @return $this
     */
    public function insert(int $at, mixed $value): static
    {
        Arr::insert($this->items, $at, $value, $this->isList);
        return $this;
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->newInstance(Arr::intersect($this, $items, $this->isList));
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return Arr::isEmpty($this);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return Arr::isNotEmpty($this);
    }

    /**
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public function join(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return Arr::join($this, $glue, $prefix, $suffix);
    }

    /**
     * @template TNewKey of string
     * @param Closure(TValue, TKey): TNewKey $key
     * @param bool $overwrite
     * @return Map<TNewKey, TValue>
     */
    public function keyBy(Closure $key, bool $overwrite = false): Map
    {
        return $this->newMap(Arr::keyBy($this, $key, $overwrite));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function last(?Closure $condition = null): mixed
    {
        return Arr::last($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return int|null
     */
    public function lastIndex(Closure $condition): ?int
    {
        return Arr::lastIndex($this, $condition);
    }

    /**
     * @template TDefault
     * @param Closure(TValue, TKey): bool|null $condition
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function lastOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::lastOr($this, $default, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function lastOrNull(?Closure $condition = null): mixed
    {
        return Arr::lastOrNull($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return TValue
     */
    public function max(?Closure $callback = null): mixed
    {
        return Arr::max($this, $callback);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->newInstance(Arr::merge($this, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::mergeRecursive($this, $iterable, $depth));
    }

    /**
     * Returns the minimum element in the sequence.
     *
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return TValue|null
     */
    public function min(?Closure $callback = null): mixed
    {
        return Arr::min($this, $callback);
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return array<TValue>
     */
    public function minMax(?Closure $callback = null): array
    {
        return Arr::minMax($this, $callback);
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function notEquals(mixed $items): bool
    {
        return !$this->equals($items);
    }

    /**
     * @param iterable<TKey> $keys
     * @return static
     */
    public function only(iterable $keys): static
    {
        return $this->newInstance(Arr::only($this, $keys, $this->isList));
    }

    /**
     * Passes $this to the given callback and returns the result.
     *
     * @template TPipe
     * @param  Closure($this): TPipe  $callback
     * @return TPipe
     */
    public function pipe(Closure $callback)
    {
        return $callback($this);
    }

    /**
     * @return TValue|null
     */
    public function pop(): mixed
    {
        assert(is_array($this->items));
        return Arr::pop($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function popMany(int $amount): static
    {
        assert(is_array($this->items));
        return $this->newInstance(Arr::popMany($this->items, $amount));
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(Closure $condition): static
    {
        return $this->newInstance(Arr::prioritize($this, $condition, $this->isList));
    }

    /**
     * @param TKey $key
     * @return TValue
     */
    public function pull(int|string $key): mixed
    {
        assert(is_array($this->items));
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
        assert(is_array($this->items));
        return Arr::pullOr($this->items, $key, $default, $this->isList);
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function pullOrNull(int|string $key): mixed
    {
        assert(is_array($this->items));
        return Arr::pullOrNull($this->items, $key, $this->isList);
    }

    /**
     * @param iterable<TKey> $keys
     * @return static
     */
    public function pullMany(iterable $keys): static
    {
        assert(is_array($this->items));
        return $this->newInstance(Arr::pullMany($this->items, $keys, $this->isList));
    }

    /**
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * @return TValue
     */
    public function reduce(Closure $callback): mixed
    {
        return Arr::reduce($this, $callback);
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        assert(is_array($this->items));
        return Arr::remove($this->items, $value, $limit, $this->isList);
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->newInstance(Arr::reverse($this, $this->isList));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->newInstance(Arr::rotate($this, $count, $this->isList));
    }

    /**
     * @return TValue|null
     */
    public function sample(): mixed
    {
        return Arr::sample($this);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function sampleMany(int $amount): static
    {
        return $this->newInstance(Arr::sampleMany($this, $amount, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAll(Closure $condition): bool
    {
        return Arr::satisfyAll($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAny(Closure $condition): bool
    {
        return Arr::satisfyAny($this, $condition);
    }

    /**
     * @return TValue|null
     */
    public function shift(): mixed
    {
        assert(is_array($this->items));
        return Arr::shift($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function shiftMany(int $amount): static
    {
        assert(is_array($this->items));
        return $this->newInstance(Arr::shiftMany($this->items, $amount));
    }

    /**
     * @return static
     */
    public function shuffle(): static
    {
        return $this->newInstance(Arr::shuffle($this, $this->isList));
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->newInstance(Iter::slice($this, $offset, $length, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function sole(?Closure $condition = null): mixed
    {
        return Arr::sole($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @param int $flag
     * @return static
     */
    public function sortAsc(?Closure $callback = null, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortAsc($this, $callback, $flag, $this->isList));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKey(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByKeyAsc($this, $flag));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeyDesc(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByKeyDesc($this, $flag));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @param int $flag
     * @return static
     */
    public function sortDesc(?Closure $callback = null, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortDesc($this, $callback, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->newInstance(Arr::sortWith($this, $comparison, $this->isList));
    }

    /**
     * @param Closure(TKey, TKey): int $comparison
     * @return static
     */
    public function sortWithKey(Closure $comparison): static
    {
        return $this->newInstance(Arr::sortWithKey($this, $comparison));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * @return static
     */
    public function symDiff(iterable $items, Closure $by = null): static
    {
        return $this->newInstance(Arr::symDiff($this, $items, $by, $this->isList));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function takeFirst(int $amount): static
    {
        return $this->newInstance(Iter::takeFirst($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::takeUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::takeWhile($this, $condition));
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
     * @param int<1, max>|null $depth
     * @return array<TKey, TValue>
     */
    public function toArrayRecursive(?int $depth = null): array
    {
        return $this->asArrayRecursive($this, $depth ?? PHP_INT_MAX, true);
    }

    /**
     * @param bool $formatted
     * @return string
     */
    public function toJson(bool $formatted = false): string
    {
        return Json::encode($this->jsonSerialize(), $formatted);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $callback
     * @return static
     */
    public function unique(?Closure $callback = null): static
    {
        return $this->newInstance(Arr::unique($this, $callback, $this->isList));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param int $depth
     * @param bool $validate
     * @return array<TKey, TValue>
     */
    protected function asArrayRecursive(iterable $items, int $depth, bool $validate = false): array
    {
        if ($validate) {
            Assert::positiveInteger($depth);
        }

        return Arr::map($items, function($item) use ($depth) {
            if (is_iterable($item) && $depth > 1) {
                return $this->asArrayRecursive($item, $depth - 1);
            }
            return $item;
        });
    }

    /**
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $iterable
     * @return Map<TNewKey, TNewValue>
     */
    protected function newMap(iterable $iterable): Map
    {
        return new Map($iterable);
    }

    /**
     * @template TNewValue
     * @param iterable<int, TNewValue> $iterable
     * @return Vec<TNewValue>
     */
    protected function newVec(iterable $iterable): Vec
    {
        return new Vec($iterable);
    }
}
