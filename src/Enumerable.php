<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Dumper\Config as DumperConfig;
use Random\Randomizer;
use function dump;
use function is_iterable;
use const PHP_INT_MAX;
use const SORT_REGULAR;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 *
 * TODO keyAt, keyAtOr, keyAtOrNull
 */
trait Enumerable
{
    /**
     * On certain operations, there are options to reindex the array or not.
     * This is a helper method to determine whether the array should be re-indexed.
     * For example, when you remove an item from the array, the array should be
     * re-indexed if it is a vector (Vec), but not when it is a hash-table (Map).
     *
     * @return bool
     */
    abstract protected function reindex(): bool;

    /**
     * Returns the value at the given index.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue
     */
    public function at(int $index)
    {
        return Arr::at($this, $index);
    }

    /**
     * Returns the value at the given index.
     * Returns `$default` if the given index does not exist.
     *
     * @template TDefault
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @param TDefault $default
     * Value that is used when the given index did not exist.
     * @return TValue|TDefault
     */
    public function atOr(int $index, mixed $default)
    {
        return Arr::atOr($this, $index, $default);
    }

    /**
     * Returns the value at the given index.
     * Returns **null** if the given index does not exist.
     *
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue|null
     */
    public function atOrNull(int $index)
    {
        return Arr::atOrNull($this, $index);
    }

    /**
     * Returns the first non-null value.
     * Throws `InvalidArgumentException` if empty or all elements are **null**.
     *
     * @return TValue
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this);
    }

    /**
     * Returns the first non-null value.
     * Returns **null** if `$iterable` is empty or if all elements are **null**.
     *
     * @return TValue|null
     */
    public function coalesceOrNull(): mixed
    {
        return Arr::coalesceOrNull($this);
    }

    /**
     * Returns a new instance with all null elements removed.
     *
     * @param int<1, max> $depth
     * [Optional] Must be >= 1. Default is 1.
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->instantiate(Arr::compact($this, $depth, $this->reindex()));
    }

    /**
     * Returns **true** if value exists, **false** otherwise.
     *
     * @param mixed $value
     * Value to be searched.
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
    }

    /**
     * @param iterable<int, TValue> $values
     * @return bool
     */
    public function containsAll(iterable $values): bool
    {
        return Arr::containsAll($this, $values);
    }

    /**
     * @param iterable<int, TValue> $values
     * @return bool
     */
    public function containsAny(iterable $values): bool
    {
        return Arr::containsAny($this, $values);
    }

    /**
     * @param iterable<int, TValue> $values
     * @return bool
     */
    public function containsNone(iterable $values): bool
    {
        return Arr::containsNone($this, $values);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] Condition to determine if given item should be counted.
     * Defaults to **null**.
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
        return $this->instantiate(Arr::diff($this, $items, null, $this->reindex()));
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
        return $this->instantiate(Iter::dropFirst($this, $amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropLast(int $amount): static
    {
        return $this->instantiate(Arr::dropLast($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition));
    }

    /**
     * @param DumperConfig|null $config
     * @return $this
     */
    public function dump(?DumperConfig $config = null): static
    {
        dump($this, $config);
        return $this;
    }

    /**
     * @return static
     */
    public function duplicates(): static
    {
        return $this->instantiate(Arr::duplicates($this));
    }

    /**
     * @param Closure(TValue, TKey): mixed $callback
     * @return static
     */
    public function each(Closure $callback): static
    {
        return $this->instantiate(Iter::each($this, $callback));
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
        return $this->instantiate(Arr::except($this, $keys, $safe, $this->reindex()));
    }

    /**
     * Creates a Generator that will send the key/value to the generator if the condition is **true**.
     *
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->instantiate(Iter::filter($this, $condition, $this->reindex()));
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
        $grouped = Arr::groupBy($this, $callback, $this->reindex());
        return $this->newMap($grouped)->map(fn($group) => $this->instantiate($group));
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
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->instantiate(Arr::intersect($this, $items, $this->reindex()));
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
     * @return Vec<TKey>
     */
    public function keys(): Vec
    {
        return $this->newVec(Iter::keys($this));
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
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return new static(Iter::map($this, $callback));
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
        return $this->instantiate(Arr::only($this, $keys, $safe, $this->reindex()));
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
        return $this->instantiate(Arr::prioritize($this, $condition, $this->reindex()));
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
     * @template TDefault
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * @param TDefault $default
     * @return TValue
     */
    public function reduceOr(Closure $callback, mixed $default): mixed
    {
        return Arr::reduceOr($this, $callback, $default);
    }

    /**
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * @return TValue|null
     */
    public function reduceOrNull(Closure $callback): mixed
    {
        return Arr::reduceOrNull($this, $callback);
    }

    /**
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return static
     */
    public function replace(mixed $search, mixed $replacement, int &$count = 0): static
    {
        return $this->instantiate(Iter::replace($this, $search, $replacement, $count));
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->instantiate(Arr::reverse($this, $this->reindex()));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->instantiate(Arr::rotate($this, $count, $this->reindex()));
    }

    /**
     * @param Randomizer|null $randomizer
     * @return TValue
     */
    public function sample(?Randomizer $randomizer = null): mixed
    {
        return Arr::sample($this, $randomizer);
    }

    /**
     * @param int $amount
     * @param bool $replace
     * @param Randomizer|null $randomizer
     * @return Vec<TValue>
     */
    public function sampleMany(int $amount, bool $replace = false, ?Randomizer $randomizer = null): Vec
    {
        return $this->newVec(Arr::sampleMany($this, $amount, $replace, $randomizer));
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @param Randomizer|null $randomizer
     * @return TValue|TDefault
     */
    public function sampleOr(mixed $default, ?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleOr($this, $default, $randomizer);
    }

    /**
     * @param Randomizer|null $randomizer
     * @return TValue|null
     */
    public function sampleOrNull(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleOrNull($this, $randomizer);
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
     * @param Randomizer|null $randomizer
     * @return static
     */
    public function shuffle(?Randomizer $randomizer = null): static
    {
        return $this->instantiate(Arr::shuffle($this, $this->reindex(), $randomizer));
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->instantiate(Iter::slice($this, $offset, $length, $this->reindex()));
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
        return $this->instantiate(Arr::sort($this, $ascending, $by, $flag, $this->reindex()));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $by
     * @param int $flag
     * @return static
     */
    public function sortAsc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortAsc($this, $by, $flag, $this->reindex()));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $by
     * @param int $flag
     * @return static
     */
    public function sortDesc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortDesc($this, $by, $flag, $this->reindex()));
    }

    /**
     * @param Closure(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->instantiate(Arr::sortWith($this, $comparison, $this->reindex()));
    }

    /**
     * @param int<1, max> $size
     * @return Vec<static>
     */
    public function split(int $size): Vec
    {
        $chunks = [];
        foreach (Iter::chunk($this, $size, $this->reindex()) as $chunk) {
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
        return $this->instantiate(Arr::symDiff($this, $items, $by, $this->reindex()));
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
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return Arr::from($this);
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
     * @param Closure(TValue, TKey): bool|null $by
     * @return static
     */
    public function unique(?Closure $by = null): static
    {
        return $this->instantiate(Arr::unique($this, $by, $this->reindex()));
    }

    /**
     * @return Vec<TValue>
     */
    public function values(): Vec
    {
        return $this->newVec(Iter::values($this));
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
        if ($bool instanceof Closure) {
            $bool = $bool($this);
        }

        $fallback ??= static fn($self): static => $self;

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
        return $this->when($this->isEmpty(), $callback, $fallback);
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
        return $this->when($this->isNotEmpty(), $callback, $fallback);
    }

    /**
     * @param int $size
     * @return Vec<static>
     */
    public function windows(int $size): Vec
    {
        $generator = (function() use ($size) {
            foreach (Iter::windows($this, $size, $this->reindex()) as $window) {
                yield $this->instantiate($window);
            }
        })();
        return $this->newVec($generator);
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
            throw new InvalidArgumentException("Expected: \$depth >= 1. Got: {$depth}", [
                'this' => $this,
                'items' => $items,
                'depth' => $depth,
                'validate' => $validate,
            ]);
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
