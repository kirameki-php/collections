<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Countable;
use JsonSerializable;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Random\Randomizer;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Json;
use function array_is_list;
use function is_iterable;
use const PHP_INT_MAX;
use const SORT_REGULAR;

/**
 * @phpstan-consistent-constructor
 *
 * @template TKey of array-key
 * @template TValue
 * @extends Enumerator<TKey, TValue>
 */
class Seq extends Enumerator implements Countable, JsonSerializable
{
    protected bool $reindex;

    /**
     * @param iterable<TKey, TValue>|null $items
     * @param bool|null $reindex
     */
    public function __construct(iterable|null $items = null, ?bool $reindex = null)
    {
        $array = Arr::from($items ?? []);
        parent::__construct($array);
        $this->reindex = $reindex ?? array_is_list($array);
    }

    /**
     * @return static
     */
    public static function empty(): static
    {
        return new static();
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
     * @param int<1, max> $depth
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->instantiate(Arr::compact($this, $depth, $this->reindex));
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
        return $this->instantiate(Arr::diff($this, $items, null, $this->reindex));
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
    public function dropLast(int $amount): static
    {
        return $this->instantiate(Arr::dropLast($this, $amount, $this->reindex));
    }

    /**
     * @return static
     */
    public function duplicates(): static
    {
        return $this->instantiate(Arr::duplicates($this));
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
        return $this->instantiate(Arr::except($this, $keys, $safe, $this->reindex));
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
        $grouped = Arr::groupBy($this, $callback, $this->reindex);
        return $this->newMap($grouped)->map(fn($group) => $this->instantiate($group));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->instantiate(Arr::intersect($this, $items, $this->reindex));
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
     * @return Enumerator<TKey, TValue>
     */
    public function lazy(): Enumerator
    {
        return new Enumerator($this);
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
        return $this->instantiate(Arr::only($this, $keys, $safe, $this->reindex));
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
     * Move items that match condition to the top of the array.
     *
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(Closure $condition): static
    {
        return $this->instantiate(Arr::prioritize($this, $condition, $this->reindex));
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
     * @return static
     */
    public function reverse(): static
    {
        return $this->instantiate(Arr::reverse($this, $this->reindex));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->instantiate(Arr::rotate($this, $count, $this->reindex));
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
        return $this->instantiate(Arr::shuffle($this, $this->reindex, $randomizer));
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->instantiate(Arr::slice($this, $offset, $length, $this->reindex));
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
        return $this->instantiate(Arr::sort($this, $ascending, $by, $flag, $this->reindex));
    }

    /**
     * @param Closure(TValue, TKey): mixed|null $by
     * @param int $flag
     * @return static
     */
    public function sortAsc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortAsc($this, $by, $flag, $this->reindex));
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
        return $this->instantiate(Arr::sortDesc($this, $by, $flag, $this->reindex));
    }

    /**
     * @param Closure(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->instantiate(Arr::sortWith($this, $comparison, $this->reindex));
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
        foreach (Iter::chunk($this, $size, $this->reindex) as $chunk) {
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
        return $this->instantiate(Arr::symDiff($this, $items, $by, $this->reindex));
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
        return $this->instantiate(Arr::unique($this, $by, $this->reindex));
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
