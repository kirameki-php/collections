<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use Closure;
use Countable;
use JsonSerializable;
use SouthPointe\Collections\Utils\Arr;
use SouthPointe\Collections\Utils\Iter;
use SouthPointe\Core\Exceptions\InvalidArgumentException;
use SouthPointe\Core\Json;
use function array_is_list;
use function is_iterable;
use const PHP_INT_MAX;
use const SORT_REGULAR;

/**
 * @phpstan-consistent-constructor
 *
 * @template TKey of array-key
 * @template TValue
 * @extends Iterator<TKey, TValue>
 */
class Seq extends Iterator implements Countable, JsonSerializable
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
    public function instantiate(mixed $items): static
    {
        return new static($items);
    }

    /**
     * @return array<TKey, mixed>|object
     */
    public function jsonSerialize(): array|object
    {
        $values = Arr::from($this);
        return array_is_list($values)
            ? $values
            : (object) $values;
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
        return $this->instantiate(Arr::compact($this, $depth, $this->isList));
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
     * @param iterable<array-key, TValue> $values
     * @return bool
     */
    public function containsAll(iterable $values): bool
    {
        return Arr::containsAll($this, $values);
    }

    /**
     * @param iterable<array-key, TValue> $keys
     * @return bool
     */
    public function containsAllKeys(iterable $keys): bool
    {
        return Arr::containsAllKeys($this, $keys);
    }

    /**
     * @param iterable<array-key, TValue> $values
     * @return bool
     */
    public function containsAny(iterable $values): bool
    {
        return Arr::containsAny($this, $values);
    }

    /**
     * @param iterable<array-key, TValue> $keys
     * @return bool
     */
    public function containsAnyKeys(iterable $keys): bool
    {
        return Arr::containsAnyKeys($this, $keys);
    }

    /**
     * @param iterable<array-key, TValue> $values
     * @return bool
     */
    public function containsNone(iterable $values): bool
    {
        return Arr::containsNone($this, $values);
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @param Closure(TValue, TKey): bool|null $by
     * @return int
     */
    public function count(?Closure $by = null): int
    {
        return Arr::count($this, $by);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diff(iterable $items): static
    {
        return $this->instantiate(Arr::diff($this, $items, null, $this->isList));
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
     * @param mixed $items
     * @return bool
     */
    public function doesNotEquals(mixed $items): bool
    {
        return !$this->equals($items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return $this->instantiate(Iter::dropFirst($this, $amount, $this->isList));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropLast(int $amount): static
    {
        return $this->instantiate(Arr::dropLast($this, $amount, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition, $this->isList));
    }

    /**
     * @return static
     */
    public function duplicates(): static
    {
        return $this->instantiate(Arr::duplicates($this));
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
     * @param bool $safe
     * @return static
     */
    public function except(iterable $keys, bool $safe = true): static
    {
        return $this->instantiate(Arr::except($this, $keys, $safe, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->instantiate(Arr::filter($this, $condition, $this->isList));
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
     * @return int
     */
    public function firstIndex(Closure $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey):bool $condition
     * @return int|null
     */
    public function firstIndexOrNull(Closure $condition): ?int
    {
        return Arr::firstIndexOrNull($this, $condition);
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
        return $this->newMap($grouped)->map(fn($group) => $this->instantiate($group));
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
        return $this->instantiate(Arr::intersect($this, $items, $this->isList));
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
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self(Iter::keys($this));
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
     * @return SeqLazy<TKey, TValue>
     */
    public function lazy(): SeqLazy
    {
        return new SeqLazy($this->items);
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
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return TValue|null
     */
    public function maxOrNull(?Closure $callback = null): mixed
    {
        return Arr::maxOrNull($this, $callback);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->instantiate(Arr::merge($this, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->instantiate(Arr::mergeRecursive($this, $iterable, $depth));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return TValue
     */
    public function min(?Closure $callback = null): mixed
    {
        return Arr::min($this, $callback);
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $callback
     * @return TValue|null
     */
    public function minOrNull(?Closure $callback = null): mixed
    {
        return Arr::minOrNull($this, $callback);
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
     * @param iterable<TKey> $keys
     * @param bool $safe
     * @return static
     */
    public function only(iterable $keys, bool $safe = true): static
    {
        return $this->instantiate(Arr::only($this, $keys, $safe, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return array{ static, static }
     */
    public function partition(Closure $condition): array
    {
        [$true, $false] = Arr::partition($this, $condition);
        return [
            $this->instantiate($true),
            $this->instantiate($false),
        ];
    }

    /**
     * Passes $this to the given callback and returns the result.
     *
     * @template TPipe
     * @param Closure($this): TPipe $callback
     * @return TPipe
     */
    public function pipe(Closure $callback)
    {
        return $callback($this);
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(Closure $condition): static
    {
        return $this->instantiate(Arr::prioritize($this, $condition, $this->isList));
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
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return array<TKey, TValue>
     */
    public function replace(
        mixed $search,
        mixed $replacement,
        int &$count = 0,
    ): array
    {
        return Arr::replace($this, $search, $replacement, $count);
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->instantiate(Arr::reverse($this, $this->isList));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->instantiate(Arr::rotate($this, $count, $this->isList));
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
        return $this->instantiate(Arr::sampleMany($this, $amount, $this->isList));
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
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyNone(Closure $condition): bool
    {
        return Arr::satisfyNone($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyOnce(Closure $condition): bool
    {
        return Arr::satisfyOnce($this, $condition);
    }

    /**
     * @return static
     */
    public function shuffle(): static
    {
        return $this->instantiate(Arr::shuffle($this, $this->isList));
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->instantiate(Iter::slice($this, $offset, $length, $this->isList));
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
     * @param Closure(TValue, TKey): mixed|null $by
     * @param bool $ascending
     * @param int $flag
     * @return static
     */
    public function sort(bool $ascending, ?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sort($this, $ascending, $by, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $by
     * @param int $flag
     * @return static
     */
    public function sortAsc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortAsc($this, $by, $flag, $this->isList));
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
     * @param Closure(TValue, TKey): mixed|null $by
     * @param int $flag
     * @return static
     */
    public function sortDesc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortDesc($this, $by, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->instantiate(Arr::sortWith($this, $comparison, $this->isList));
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
     * @param int<1, max> $size
     * @return Vec<static>
     */
    public function split(int $size): Vec
    {
        $chunks = [];
        foreach (Iter::chunk($this, $size, $this->isList) as $chunk) {
            $converted = $this->instantiate($chunk);
            $chunks[] = $converted;
        }
        return $this->newVec($chunks);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * @return static
     */
    public function symDiff(iterable $items, Closure $by = null): static
    {
        return $this->instantiate(Arr::symDiff($this, $items, $by, $this->isList));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function takeFirst(int $amount): static
    {
        return $this->instantiate(Iter::takeFirst($this, $amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function takeLast(int $amount): static
    {
        return $this->instantiate(Arr::takeLast($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::takeUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::takeWhile($this, $condition));
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
     * @param Closure(TValue, TKey): bool|null $by
     * @return static
     */
    public function unique(?Closure $by = null): static
    {
        return $this->instantiate(Arr::unique($this, $by, $this->isList));
    }

    /**
     * @param bool|Closure($this): bool $bool
     * @param Closure($this): static $callback
     * @param Closure($this): static|null $fallback
     * @return static
     */
    public function when(
        bool|Closure $bool,
        Closure $callback,
        ?Closure $fallback = null,
    ): static
    {
        $fallback ??= static fn($self) => $self;

        if ($bool instanceof Closure) {
            $bool = $bool($this);
        }

        return $bool
            ? $callback($this)
            : $fallback($this);
    }

    /**
     * @param Closure($this): static $callback
     * @param Closure($this): static|null $fallback
     * @return static
     */
    public function whenEmpty(
        Closure $callback,
        ?Closure $fallback = null,
    ): static
    {
        return static::when($this->isEmpty(), $callback, $fallback);
    }

    /**
     * @param Closure($this): static $callback
     * @param Closure($this): static|null $fallback
     * @return static
     */
    public function whenNotEmpty(
        Closure $callback,
        ?Closure $fallback = null,
    ): static
    {
        return static::when($this->isNotEmpty(), $callback, $fallback);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param int $depth
     * @param bool $validate
     * @return array<TKey, TValue>
     */
    protected function asArrayRecursive(iterable $items, int $depth, bool $validate = false): array
    {
        if ($validate && $depth < 1) {
            throw new InvalidArgumentException("Expected: \$depth >= 1. Got: {$depth}");
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
