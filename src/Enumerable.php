<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Json;
use Kirameki\Dumper\Config as DumperConfig;
use Random\Randomizer;
use function dump;
use function is_iterable;
use const PHP_INT_MAX;
use const SORT_REGULAR;

/**
 * @template TKey of array-key
 * @template TValue
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
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return Arr::from($this);
    }

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
     * Returns **null** if collection is empty or if all elements are **null**.
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
     * @param TValue $value
     * Value to be searched.
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
    }

    /**
     * Returns **true** if all given values exist in the collection,
     * **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsAll(iterable $values): bool
    {
        return Arr::containsAll($this, $values);
    }

    /**
     * Returns **true** if any given values exist in the collection,
     * **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsAny(iterable $values): bool
    {
        return Arr::containsAny($this, $values);
    }

    /**
     * Returns **true** if none of the given values exist in the
     * collection, **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsNone(iterable $values): bool
    {
        return Arr::containsNone($this, $values);
    }

    /**
     * Counts all the elements in the collection.
     * If a condition is given, it will only increase the count if the condition returns **true**.
     *
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
     * Compares the keys against the values from `$items` and returns the difference.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable to be compared with.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] Callback which can be used for comparison of items.
     * @return static
     */
    public function diff(iterable $items, ?Closure $by = null): static
    {
        return $this->instantiate(Arr::diff($this, $items, $by, $this->reindex()));
    }

    /**
     * Returns **false** if value exists, **true** otherwise.
     *
     * @param TValue $value
     * Value to be searched.
     * @return bool
     */
    public function doesNotContain(mixed $value): bool
    {
        return Arr::doesNotContain($this, $value);
    }

    /**
     * Returns a new instance with the first n elements dropped.
     *
     * @param int $amount
     * Amount of elements to drop from the front. Must be >= 0.
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return $this->instantiate(Iter::dropFirst($this, $amount, $this->reindex()));
    }

    /**
     * Returns a new instance with the last n elements dropped.
     *
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @return static
     */
    public function dropLast(int $amount): static
    {
        return $this->instantiate(Arr::dropLast($this, $amount));
    }

    /**
     * Returns a new instance with the values dropped until the condition returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition, $this->reindex()));
    }

    /**
     * Returns a new instance with the values dropped while the condition returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition, $this->reindex()));
    }

    /**
     * Dump the contents of the collection.
     *
     * @param DumperConfig|null $config
     * Config used for the dumper.
     * @return $this
     */
    public function dump(?DumperConfig $config = null): static
    {
        dump($this, $config);
        return $this;
    }

    /**
     * Returns duplicate values.
     *
     * @return static
     */
    public function duplicates(): static
    {
        return $this->instantiate(Arr::duplicates($this));
    }

    /**
     * Iterates through the collection and invoke `$callback` for each element.
     *
     * @param Closure(TValue, TKey): mixed $callback
     * Callback which is called for every element of the collection.
     * @return static
     */
    public function each(Closure $callback): static
    {
        return $this->instantiate(Iter::each($this, $callback));
    }

    /**
     * Returns a new instance with the given keys removed. Missing keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist.
     *
     * @param array<int, TKey> $keys
     * Keys to be excluded.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in the collection.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
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
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function first(?Closure $condition = null): mixed
    {
        return Arr::first($this, $condition);
    }

    /**
     * Returns the first index of the collection which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     *
     * @param Closure(TValue, TKey):bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int
     */
    public function firstIndex(Closure $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * Returns the first index of the collection which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * @param Closure(TValue, TKey):bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int|null
     */
    public function firstIndexOrNull(Closure $condition): ?int
    {
        return Arr::firstIndexOrNull($this, $condition);
    }

    /**
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * If condition has no matches, value of `$default` is returned.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function firstOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::firstOr($this, $default, $condition);
    }

    /**
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * **null** is returned, if no element matches the `$condition` or is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function firstOrNull(?Closure $condition = null): mixed
    {
        return Arr::firstOrNull($this, $condition);
    }

    /**
     * Take all the values in the collection and fold it into a single value.
     *
     * @template U
     * @param U $initial
     * The initial value passed to the first Closure as result.
     * @param Closure(U, TValue, TKey): U $callback
     * Callback which is called for every key-value pair in the collection.
     * The callback arguments are `(mixed $result, mixed $value, mixed $key)`.
     * The returned value would be used as $result for the subsequent call.
     * @return U
     */
    public function fold(mixed $initial, Closure $callback): mixed
    {
        return Arr::fold($this, $initial, $callback);
    }

    /**
     * Groups the elements of the collection according to the string
     * returned by `$callback`.
     *
     * @template TGroupKey of array-key
     * @param Closure(TValue, TKey): TGroupKey $callback
     * Callback to determine the group of the element.
     * @return Map<TGroupKey, static>
     */
    public function groupBy(Closure $callback): Map
    {
        $grouped = Arr::groupBy($this, $callback, $this->reindex());
        return $this->newMap($grouped)->map(fn($group) => $this->instantiate($group));
    }

    /**
     * Create a new instance of the collection with the given `$items`.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable elements to be used in collection
     * @return static
     */
    public function instantiate(mixed $items): static
    {
        return new static($items);
    }

    /**
     * Returns the intersection of collection's values.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable to be intersected.
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->instantiate(Arr::intersect($this, $items, $this->reindex()));
    }

    /**
     * Returns **true** if empty, **false** otherwise.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return Arr::isEmpty($this);
    }

    /**
     * Returns **true** if not empty, **false** otherwise.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return Arr::isNotEmpty($this);
    }

    /**
     * Concatenates all the elements in the collection into a single
     * string using the provided `$glue`. Optional prefix and suffix can
     * also be added to the result string.
     *
     * @param string $glue
     * String used to join the elements.
     * @param string|null $prefix
     * [Optional] Prefix added to the joined string.
     * @param string|null $suffix
     * [Optional] Suffix added to the joined string.
     * @return string
     */
    public function join(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return Arr::join($this, $glue, $prefix, $suffix);
    }

    /**
     * Returns a map which contains values from the collection with the keys
     * being the results of running `$callback($val, $key)` on each element.
     *
     * Throws `DuplicateKeyException` when the value returned by `$callback`
     * already exist in `$array` as a key. Set `$overwrite` to **true** to
     * suppress this error.
     *
     * @template TNewKey of array-key
     * @param Closure(TValue, TKey): TNewKey $callback
     * Callback which returns the key for the new map.
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten.
     * If **false**, exception will be thrown on duplicate keys.
     * @return Map<TNewKey, TValue>
     */
    public function keyBy(Closure $callback, bool $overwrite = false): Map
    {
        return $this->newMap(Arr::keyBy($this, $callback, $overwrite));
    }

    /**
     * Returns all the keys as `Vec`.
     *
     * @return Vec<TKey>
     */
    public function keys(): Vec
    {
        return $this->newVec(Iter::keys($this));
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function last(?Closure $condition = null): mixed
    {
        return Arr::last($this, $condition);
    }

    /**
     * Returns the last index which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int
     */
    public function lastIndex(?Closure $condition = null): int
    {
        return Arr::lastIndex($this, $condition);
    }

    /**
     * Returns the last index which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int|null
     */
    public function lastIndexOrNull(?Closure $condition = null): ?int
    {
        return Arr::lastIndexOrNull($this, $condition);
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns the value of `$default` if no condition met.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public function lastOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::lastOr($this, $default, $condition);
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns **null** if no element matches the `$condition` or is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function lastOrNull(?Closure $condition = null): mixed
    {
        return Arr::lastOrNull($this, $condition);
    }

    /**
     * Returns the largest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Throws `InvalidElementException`, If collection contains NAN.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the largest number.
     * @return TValue
     */
    public function max(?Closure $by = null): mixed
    {
        return Arr::max($this, $by);
    }

    /**
     * Returns the largest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Returns **null** if the collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue|null
     */
    public function maxOrNull(?Closure $by = null): mixed
    {
        return Arr::maxOrNull($this, $by);
    }

    /**
     * Merges one or more iterables into a single collection.
     *
     * If the given keys are numeric, the keys will be re-numbered with
     * an incremented number from the last number in the new collection.
     *
     * If the two iterables have the same keys, the value inside the
     * iterable that comes later will overwrite the value in the key.
     *
     * This method will only merge the key value pairs of the root depth.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable(s) to be merged.
     * @return static
     */
    public function merge(iterable ...$iterable): static
    {
        return $this->instantiate(Arr::merge($this, ...$iterable));
    }

    /**
     * Merges one or more iterables recursively into a single collection.
     * Will merge recursively up to the given depth.
     *
     * @see merge for details on how keys and values are merged.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be merged.
     * @param int<1, max> $depth
     * [Optional] Depth of recursion. Defaults to **PHP_INT_MAX**.
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->instantiate(Arr::mergeRecursive($this, $iterable, $depth));
    }

    /**
     * Returns the smallest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue
     */
    public function min(?Closure $by = null): mixed
    {
        return Arr::min($this, $by);
    }

    /**
     * Returns the smallest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Returns **null** if the collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue|null
     */
    public function minOrNull(?Closure $by = null): mixed
    {
        return Arr::minOrNull($this, $by);
    }

    /**
     * Returns the smallest and largest element from the collection as array{ min: , max: }.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element.
     * Returned value will be used to determine the highest number.
     * @return array<TValue>
     */
    public function minMax(?Closure $by = null): array
    {
        return Arr::minMax($this, $by);
    }

    /**
     * Returns a new collection which only contains the elements that has matching
     * keys in the collection. Non-existent keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in the collection.
     *
     * @param iterable<TKey> $keys
     * Keys to be included.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in the collection.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @return static
     */
    public function only(iterable $keys, bool $safe = true): static
    {
        return $this->instantiate(Arr::only($this, $keys, $safe, $this->reindex()));
    }

    /**
     * Returns a list with two collection elements.
     * All elements in the collection evaluated to be **true** will be pushed to
     * the first collection. Elements evaluated to be **false** will be pushed to
     * the second collection.
     *
     * @param Closure(TValue, TKey): bool $condition
     * Closure to evaluate each element.
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
     * Passes `$this` to the given callback and returns the result,
     * so it can be used in a chain.
     *
     * @template TPipe
     * @param Closure($this): TPipe $callback
     * Callback which will receive $this as argument.
     * The result of the callback will be returned.
     * @return TPipe
     */
    public function pipe(Closure $callback)
    {
        return $callback($this);
    }

    /**
     * Move items which match the condition to the front of the collection.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param int|null $limit
     * [Optional] Limits the number of items to prioritize.
     * @return static
     */
    public function prioritize(Closure $condition, ?int $limit = null): static
    {
        return $this->instantiate(Arr::prioritize($this, $condition, $limit, $this->reindex()));
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue
     */
    public function reduce(Closure $callback): mixed
    {
        return Arr::reduce($this, $callback);
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns `$default` if the collection is empty.
     *
     * @template TDefault
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @param TDefault $default
     * Value that is used when iterable is empty.
     * @return TValue
     */
    public function reduceOr(Closure $callback, mixed $default): mixed
    {
        return Arr::reduceOr($this, $callback, $default);
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns **null** if the collection is empty.
     *
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue|null
     */
    public function reduceOrNull(Closure $callback): mixed
    {
        return Arr::reduceOrNull($this, $callback);
    }

    /**
     * Returns a new collection which contains keys and values from the collection
     * but with the `$search` value replaced with the `$replacement` value.
     *
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int|null $limit
     * [Optional] Sets a limit to number of times a replacement can take place.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return static
     */
    public function replace(mixed $search, mixed $replacement, ?int $limit = null, int &$count = 0): static
    {
        return $this->instantiate(Iter::replace($this, $search, $replacement, $limit, $count));
    }

    /**
     * Returns a new collection which contain all elements of the collection
     * in reverse order.
     *
     * @return static
     */
    public function reverse(): static
    {
        return $this->instantiate(Arr::reverse($this, $this->reindex()));
    }

    /**
     * Converts the collection to an array and rotate the array to the right
     * by `$steps`. If `$steps` is a negative value, the array will rotate
     * to the left instead.
     *
     * @param int $steps
     * Number of times the key/value will be rotated.
     * @return static
     */
    public function rotate(int $steps): static
    {
        return $this->instantiate(Arr::rotate($this, $steps, $this->reindex()));
    }

    /**
     * Returns a random element from the collection.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue
     */
    public function sample(?Randomizer $randomizer = null): mixed
    {
        return Arr::sample($this, $randomizer);
    }

    /**
     * Returns a list of random elements picked from the collection.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than the collection's size.
     *
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return Vec<TValue>
     */
    public function sampleMany(int $amount, bool $replace = false, ?Randomizer $randomizer = null): Vec
    {
        return $this->newVec(Arr::sampleMany($this, $amount, $replace, $randomizer));
    }

    /**
     * Returns a random element from the collection.
     * Returns `$default` if the collection is empty.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the collection is empty.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public function sampleOr(mixed $default, ?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleOr($this, $default, $randomizer);
    }

    /**
     * Returns a random element from the collection.
     * Returns **null** if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function sampleOrNull(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleOrNull($this, $randomizer);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if all iterations that run through the condition returned **true** or if
     * the collection is empty, **false** otherwise.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyAll(Closure $condition): bool
    {
        return Arr::satisfyAll($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if any iterations that run through the `$condition` returned **true**,
     * **false** otherwise (including empty iterable).
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyAny(Closure $condition): bool
    {
        return Arr::satisfyAny($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if all the iterations that run through the `$condition` returned **false**.
     * **false** otherwise.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyNone(Closure $condition): bool
    {
        return Arr::satisfyNone($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if iterations that run through the `$condition` returned **true** only once,
     * **false** otherwise (including empty iterable).
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyOnce(Closure $condition): bool
    {
        return Arr::satisfyOnce($this, $condition);
    }

    /**
     * Shuffles the elements of the collection.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return static
     */
    public function shuffle(?Randomizer $randomizer = null): static
    {
        return $this->instantiate(Arr::shuffle($this, $this->reindex(), $randomizer));
    }

    /**
     * Returns a shallow copy of a portion of the collection into a new collection.
     *
     * @param int $offset
     * If offset is non-negative, the sequence will start at that offset.
     * If offset is negative, the sequence will start that far from the end.
     * @param int $length
     * If length is given and is positive, then the sequence will have up to that many elements in it.
     * If the iterable is shorter than the length, then only the available array elements will be present.
     * If length is given and is negative then the sequence will stop that many elements from the end.
     * If it is omitted, then the sequence will have everything from offset up until the end.
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->instantiate(Iter::slice($this, $offset, $length, $this->reindex()));
    }

    /**
     * Returns the only element in the collection.
     * If a condition is also given, the sole element of a sequence that satisfies a specified
     * condition is returned instead.
     * Throws `InvalidArgumentException` if there are more than one element in `$iterable`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function sole(?Closure $condition = null): mixed
    {
        return Arr::sole($this, $condition);
    }

    /**
     * Sort the collection by value in the given order.
     *
     * @param int $order
     * Order of the sort. Must be `SORT_ASC` or `SORT_DESC`.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sort(int $order, ?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sort($this, $order, $by, $flag, $this->reindex()));
    }

    /**
     * Sort the `$iterable` by value in ascending order.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortAsc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortAsc($this, $by, $flag, $this->reindex()));
    }

    /**
     * Sort the `$iterable` by value in descending order.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortDesc(?Closure $by = null, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortDesc($this, $by, $flag, $this->reindex()));
    }

    /**
     * Sorts the collection by value using the provided `$comparison` function.
     *
     * @param Closure(TValue, TValue): int $comparison
     * The comparison function to use.
     * Utilize the spaceship operator (`<=>`) to easily compare two values.
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->instantiate(Arr::sortWith($this, $comparison, $this->reindex()));
    }

    /**
     * Splits the collection into chunks of the given size.
     *
     * @param int<1, max> $size
     * Size of each chunk. Must be >= 1.
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
     * Returns the symmetric difference between collection and `$items`.
     * Throws `TypeMismatchException` if comparing a map to a list.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable to be traversed and compared to.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] User defined comparison callback.
     * Return 1 if first argument is greater than the 2nd.
     * Return 0 if first argument is equal to the 2nd.
     * Return -1 if first argument is less than the 2nd.
     * Defaults to **null**.
     * @return static
     */
    public function symDiff(iterable $items, Closure $by = null): static
    {
        return $this->instantiate(Arr::symDiff($this, $items, $by, $this->reindex()));
    }

    /**
     * Take the first n elements from the collection and return a new instance
     * with those elements.
     *
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return static
     */
    public function takeFirst(int $amount): static
    {
        return $this->instantiate(Iter::takeFirst($this, $amount));
    }

    /**
     * Take the last n elements from the collection and return a new instance
     * with those elements.
     *
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @return static
     */
    public function takeLast(int $amount): static
    {
        return $this->instantiate(Arr::takeLast($this, $amount, $this->reindex()));
    }

    /**
     * Takes elements in the collection until `$condition` returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A break condition callback that should return **false** to stop the
     * taking of elements from the collection.
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::takeUntil($this, $condition));
    }

    /**
     * Takes elements in the collection while `$condition` returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A break condition callback that should return **false** to stop the
     * taking of elements from the collection.
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::takeWhile($this, $condition));
    }

    /**
     * Converts the collection to an array recursively up to the given `$depth`.
     *
     * @param int<1, max> $depth
     * [Optional] Defaults to INT_MAX
     * @return array<TKey, TValue>
     */
    public function toArray(int $depth = PHP_INT_MAX): array
    {
        return $this->asArrayRecursive($this, $depth, true);
    }

    /**
     * Converts the collection to a JSON string.
     *
     * @param bool $pretty
     * [Optional] Whether to format the JSON as human-readable format.
     * Defaults to **false**.
     * @return string
     */
    public function toJson(bool $pretty = false): string
    {
        return Json::encode($this, $pretty);
    }

    /**
     * Removes duplicate values from `$iterable` and returns it as an array.
     *
     * This differs from `array_unique` in that, this does not do a
     * string conversion before comparing.
     * For example, `array_unique([1, true])` will result in: `[1]` but
     * doing `Arr::unique([1, true])` will result in: `[1, true]`.
     *
     * @param Closure(TValue, TKey): bool|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to check for duplicates.
     * [Optional] Defaults to **null**.
     * @return static
     */
    public function unique(?Closure $by = null): static
    {
        return $this->instantiate(Arr::unique($this, $by, $this->reindex()));
    }

    /**
     * Calls `$callback` for every element in the collection if `$bool`
     * is **true**, calls `$fallback` otherwise.
     *
     * @param bool|Closure($this): bool $bool
     * Bool or callback to determine whether to execute `$callback` or `$fallback`.
     * @param Closure($this): static $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): static|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
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
     * Calls `$callback` for every element in the collection if collection is empty,
     * calls `$fallback` otherwise.
     *
     * @param Closure($this): static $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): static|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
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
     * Calls `$callback` for every element in the collection if collection is not
     * empty, calls `$fallback` otherwise.
     *
     * @param Closure($this): static $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): static|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
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
     * Converts the collection to an overlapping sub-slices of `$size`.
     *
     * @param int $size
     * Size of the window. Must be >= 1.
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
            throw new InvalidArgumentException("Expected: \$depth >= 1. Got: {$depth}.", [
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
