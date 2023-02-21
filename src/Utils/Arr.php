<?php declare(strict_types=1);

namespace Kirameki\Collections\Utils;

use Closure;
use JsonException;
use Kirameki\Collections\Exceptions\DuplicateKeyException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidElementException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\MissingKeyException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\Exceptions\TypeMismatchException;
use Random\Randomizer;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\UnreachableException;
use Traversable;
use function abs;
use function array_diff;
use function array_diff_ukey;
use function array_fill;
use function array_intersect;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function array_push;
use function array_reverse;
use function array_shift;
use function array_splice;
use function array_udiff;
use function array_unshift;
use function array_values;
use function arsort;
use function asort;
use function count;
use function current;
use function end;
use function get_resource_id;
use function gettype;
use function http_build_query;
use function in_array;
use function is_array;
use function is_bool;
use function is_countable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_nan;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function key;
use function krsort;
use function ksort;
use function max;
use function prev;
use function range;
use function Kirameki\Core\is_not_array_key;
use function spl_object_id;
use function uasort;
use function uksort;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use const PHP_QUERY_RFC3986;
use const SORT_REGULAR;

/**
 * TODO static range
 * TODO eachCons
 * TODO insertAfter/insertBefore
 * TODO recursive (walk)
 * TODO zip
 */
final class Arr
{
    private const EMPTY = [];

    /**
     * Default randomizer that will be used on `shuffle`, `sample`, `sampleMany`.
     *
     * @var Randomizer|null
     */
    private static ?Randomizer $defaultRandomizer = null;

    /**
     * Append value(s) to the end of the given iterable.
     * The iterable must be convertable to a list.
     * Will throw `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * Arr::append([1, 2], 3); // [1, 2, 3]
     * Arr::append([1, 2], 3, 4); // [1, 2, 3, 4]
     * ```
     *
     * @template T
     * @param iterable<int, T> &$iterable
     * Iterable which the value is getting appended.
     * @param T ...$value
     * Value(s) to be appended to the array.
     * @return array<int, T>
     */
    public static function append(
        iterable $iterable,
        mixed ...$value,
    ): array
    {
        $array = self::from($iterable);
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'iterable' => $iterable,
                'values' => $value,
            ]);
        }
        if (!array_is_list($value)) {
            $value = array_values($value);
        }
        array_push($array, ...$value);
        return $array;
    }

    /**
     * Returns the item at the given index.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * Example:
     * ```php
     * Arr::at([6, 7], 1); // 7
     * Arr::at([6, 7], -1); // 7
     * Arr::at(['a' => 1, 'b' => 2], 0); // 1
     * Arr::at([6], 1); // IndexOutOfBoundsException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue
     */
    public static function at(
        iterable $iterable,
        int $index,
    ): mixed
    {
        $result = self::atOr($iterable, $index, self::miss());

        if ($result instanceof self) {
            $count = self::count($iterable);
            throw new IndexOutOfBoundsException("Size: $count index: $index", [
                'iterable' => $iterable,
                'index' => $index,
                'count' => $count,
            ]);
        }

        return $result;
    }

    /**
     * Returns the item at the given index.
     * Default value is returned if the given index does not exist.
     *
     * Example:
     * ```php
     * Arr::atOr([6, 7], 1); // 7
     * Arr::atOr([6, 7], -1); // 7
     * Arr::atOr(['a' => 1, 'b' => 2], 0); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @param TDefault $default
     * Value that is used when the given index did not exist.
     * @return TValue|TDefault
     *
     * TODO iterable version
     */
    public static function atOr(
        iterable $iterable,
        int $index,
        mixed $default,
    ): mixed
    {
        $array = self::from($iterable);
        $offset = $index >= 0 ? $index : count($array) + $index;
        $count = 0;

        foreach ($array as $val) {
            if ($count === $offset) {
                return $val;
            }
            ++$count;
        }

        return $default;
    }

    /**
     * Returns the item at the given index.
     *
     * Example:
     * ```php
     * Arr::atOrNull([6, 7], 1); // 7
     * Arr::atOrNull([6, 7], -1); // 7
     * Arr::atOrNull(['a' => 1, 'b' => 2], 0); // 1
     * Arr::atOrNull([6], 1); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue|null
     */
    public static function atOrNull(
        iterable $iterable,
        int $index,
    ): mixed
    {
        return self::atOr($iterable, $index, null);
    }

    /**
     * Get the average of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * Throws `InvalidElementException` if the `$iterable` is empty.
     * Throws `EmptyNotAllowedException` if `$iterable` contains NAN.
     * Example:
     * ```php
     * Arr::average([]); // 0
     * Arr::average([1, 2, 3]); // 2
     * Arr::average([0.1, 0.1]); // 0.1
     * ```
     *
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function average(
        iterable $iterable,
    ): float|int
    {
        $average = self::averageOrNull($iterable);

        if ($average === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
            ]);
        }

        return $average;
    }

    /**
     * Get the average of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * If `$iterable` is empty, **null** will be returned.
     * Throws `InvalidElementException` if iterable contains NAN.
     *
     * Example:
     * ```php
     * Arr::averageOrNull([]); // null
     * Arr::averageOrNull([1, 2, 3]); // 2
     * Arr::averageOrNull([0.1, 0.1]); // 0.1
     * ```
     *
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue|null
     */
    public static function averageOrNull(
        iterable $iterable,
    ): float|int|null
    {
        $size = 0;
        $sum = 0;
        foreach ($iterable as $val) {
            $sum += $val;
            ++$size;
        }

        if ($size === 0) {
            return null;
        }

        if (is_float($sum) && is_nan($sum)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return $sum / $size;
    }

    /**
     * Splits the iterable into chunks of new arrays.
     *
     * Example:
     * ```php
     * Arr::chunk([1, 2, 3], 2); // [[1, 2], [3]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of each chunk.
     * @param bool|null $reindex
     * Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<int, array<TKey, TValue>>
     */
    public static function chunk(
        iterable $iterable,
        int $size,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::chunk($array, $size, $reindex));
    }

    /**
     * Removes all elements in the given array (reference).
     *
     * Example:
     * ```php
     * $array = [1, 2]; Arr::clear($array); // []
     * $array = ['a' => 1, 'b' => 2]; Arr::clear($array); // []
     * ```
     *
     * @param array<array-key, mixed> &$array
     * Reference of array to be cleared.
     * @return void
     */
    public static function clear(
        array &$array,
    ): void
    {
        $array = [];
    }

    /**
     * Returns the first non-null value in the array.
     * Throws `InvalidArgumentException` if `$iterable` is empty or if all elements are **null**.
     *
     * Example:
     * ```php
     * Arr::coalesce([null, null, 1]); // 1
     * Arr::coalesce([null, null]); // InvalidArgumentException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function coalesce(
        iterable $iterable,
    ): mixed
    {
        $result = self::coalesceOrNull($iterable);

        if ($result === null) {
            throw new NoMatchFoundException('Non-null value could not be found.', [
                'iterable' => $iterable,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first non-null value in the array.
     * Returns **null** if `$iterable` is empty or if all elements are **null**.
     *
     * Example:
     * ```php
     * Arr::coalesceOrNull([null, null, 1]); // 1
     * Arr::coalesceOrNull([null, null]); // null
     * Arr::coalesceOrNull([]); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue|null
     */
    public static function coalesceOrNull(
        iterable $iterable,
    ): mixed
    {
        foreach ($iterable as $val) {
            if ($val !== null) {
                return $val;
            }
        }
        return null;
    }

    /**
     * Returns an array with all null elements removed from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::compact([null, 0, false]); // [0, false]
     * Arr::compact([[null]]); // [[null]] Doesn't remove inner null since default depth is 1.
     * Arr::compact([[null]], 2); // [[]] Removes inner null since depth is set to 2.
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * [Optional] Must be >= 1. Default is 1.
     * @param bool|null $reindex
     * [Optional] If set to **true**, the result will be re-indexed.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function compact(
        iterable $iterable,
        int $depth = 1,
        ?bool $reindex = null,
    ): array
    {
        if ($reindex === null) {
            $iterable = self::from($iterable);
            $reindex = array_is_list($iterable);
        }

        $result = [];

        foreach (Iter::compact($iterable, $reindex) as $key => $val) {
            if (is_iterable($val) && $depth > 1) {
                /** @var TValue $val */
                $val = self::compact($val, $depth - 1, $reindex); /** @phpstan-ignore-line */
            }
            $result[$key] = $val;
        }

        return $result;
    }

    /**
     * Returns **true** if value exists in `$iterable`, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::contains([1, 2], 2); // true
     * Arr::contains([1, 2], 3); // false
     * Arr::contains(['a' => 1], 1); // true
     * Arr::contains(['a' => 1], 'a'); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param mixed $value
     * Value to be searched.
     * @return bool
     */
    public static function contains(
        iterable $iterable,
        mixed $value,
    ): bool
    {
        // in_array is much faster than iterating
        if (is_array($iterable)) {
            return in_array($value, $iterable, true);
        }

        foreach ($iterable as $val) {
            if ($val === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns **true** if given iterable contains all provided values,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAll([1, 2, 3], [2, 3]); // true
     * Arr::containsAll([1, 2, 3], [1, 4]); // false
     * Arr::containsAll([1], []); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<array-key, TValue> $values
     * Values to be searched.
     * @return bool
     * TODO make generator friendly
     */
    public static function containsAll(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $array1 = self::from($iterable);
        $array2 = self::from($values);
        return array_diff($array2, $array1) === self::EMPTY;
    }

    /**
     * Returns **true** if given iterable contains all the provided keys,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAllKeys(['a' => 1, 'b' => 2], ['a', 'b']); // true
     * Arr::containsAllKeys(['a' => 1, 'b' => 2], ['a', 'c']); // false
     * Arr::containsAllKeys([1], []); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Values to be searched.
     * @return bool
     * TODO make generator friendly
     */
    public static function containsAllKeys(
        iterable $iterable,
        iterable $keys,
    ): bool
    {
        $array = self::from($iterable);
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns **true** if given iterable contains any of the provided values,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAny([1, 2, 3], [2]); // true
     * Arr::containsAny([1, 2, 3], []) // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<array-key, TValue> $values
     * Values to be searched.
     * @return bool
     * TODO make generator friendly
     */
    public static function containsAny(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $array1 = self::from($iterable);
        $array2 = self::from($values);
        return array_intersect($array2, $array1) !== self::EMPTY;
    }

    /**
     * Returns **true** if given iterable contains any of the provided keys,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['a', 'c']); // true
     * Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['c', 'd']); // false
     * Arr::containsAnyKeys(['a' => 1], []); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Values to be searched.
     * @return bool
     */
    public static function containsAnyKeys(
        iterable $iterable,
        iterable $keys,
    ): bool
    {
        $array = self::from($iterable);
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns **true** if a given key exists within iterable, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsKey([1, 2], 0); // true
     * Arr::containsKey([1, 2], 2); // false
     * Arr::containsKey(['a' => 1], 'a'); // true
     * Arr::containsKey(['a' => 1], 1); // false
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to check for in `$iterable`.
     * @return bool
     * TODO make generator friendly
     */
    public static function containsKey(
        iterable $iterable,
        int|string $key,
    ): bool
    {
        return array_key_exists($key, self::from($iterable));
    }

    /**
     * Returns **true** if given iterable contains none of the provided values,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsNone([1, 2], [3]); // true
     * Arr::containsNone([1, 2], [2]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<array-key, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public static function containsNone(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $array1 = self::from($iterable);
        $array2 = self::from($values);

        return array_diff($array2, $array1) === $array2;
    }

    /**
     * Counts all the elements in `$iterable`.
     * If a condition is given, it will only increase the count if the condition returns **true**.
     *
     * Example:
     * ```php
     * Arr::count(['a', 'b']); // 2
     * Arr::count([1, 2], fn(int $n): bool => ($n % 2) === 0); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] Condition to determine if given item should be counted.
     * Defaults to **null**.
     * @return int
     */
    public static function count(
        iterable $iterable,
        ?Closure $condition = null,
    ): int
    {
        if ($condition === null && is_countable($iterable)) {
            return count($iterable);
        }

        $count = 0;
        $condition ??= static fn() => true;
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                ++$count;
            }
        }
        return $count;
    }

    /**
     * Compares the keys from `$iterable1` against the values from `$iterable2` and returns the difference.
     *
     * Example:
     * ```php
     * Arr::diff([], [1]); // []
     * Arr::diff([1], []); // [1]
     * Arr::diff([1, 2], [2, 3]); // [1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be compared with the first iterable.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] Callback which can be used for comparison of items in both iterables.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function diff(
        iterable $iterable1,
        iterable $iterable2,
        Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);
        $by ??= static fn(mixed $a, mixed $b): int => $a <=> $b;
        $reindex ??= array_is_list($array1);

        $result = array_udiff($array1, $array2, $by);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Compares the keys from `$iterable1` against the keys from `$iterable2` and returns the difference.
     *
     * Example:
     * ```php
     * Arr::diffKeys([0 => 1, 1 => 2], [0 => 3]); // [2]
     * Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 2, 'c' => 3]); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be compared with the first iterable.
     * @param Closure(TKey, TKey): int|null $by
     * [Optional] Callback which can be used for comparison of items in both iterables.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function diffKeys(
        iterable $iterable1,
        iterable $iterable2,
        Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);
        $by ??= static fn(mixed $a, mixed $b): int => $a <=> $b;
        $reindex ??= array_is_list($array1);

        $result = array_diff_ukey($array1, $array2, $by);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Returns **false** if value exists in iterable, **true** otherwise.
     *
     * Example:
     * ```php
     * Arr::doesNotContain([1, 2], 2); // false
     * Arr::doesNotContain([1, 2], 3); // true
     * Arr::doesNotContain(['a' => 1], 1); // false
     * Arr::doesNotContain(['a' => 1], 'a'); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param mixed $value
     * Value to be searched.
     * @return bool
     */
    public static function doesNotContain(
        iterable $iterable,
        mixed $value,
    ): bool
    {
        return !self::contains($iterable, $value);
    }

    /**
     * Returns **false** if a given key exists within iterable, **true** otherwise.
     *
     * Example:
     * ```php
     * Arr::notContainsKey([1, 2], 0); // false
     * Arr::notContainsKey([1, 2], 2); // true
     * Arr::notContainsKey(['a' => 1], 'a'); // false
     * Arr::notContainsKey(['a' => 1], 1); // true
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to be searched.
     * @return bool
     */
    public static function doesNotContainKey(
        iterable $iterable,
        int|string $key,
    ): bool
    {
        return !self::containsKey($iterable, $key);
    }

    /**
     * Drop the first n elements from given iterable.
     *
     * Example:
     * ```php
     * Arr::dropFirst([1, 1, 2], 1); // [1, 2]
     * Arr::dropFirst(['a' => 1], 3); // []
     * Arr::dropFirst(['a' => 1, 'b' => 2], 1); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the front. Must be >= 0.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropFirst(
        iterable $iterable,
        int $amount,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropFirst($array, $amount, $reindex));
    }

    /**
     * Drop the last n elements from given iterable.
     *
     * Example:
     * ```php
     * Arr::dropLast([1, 1, 2], 1); // [1, 1]
     * Arr::dropLast(['a' => 1], 3); // []
     * Arr::dropLast(['a' => 1, 'b' => 2], 1); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropLast(
        iterable $iterable,
        int $amount,
        ?bool $reindex = null,
    ): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected \$amount >= 0. Got: {$amount}", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        $array = self::from($iterable);
        $length = count($array);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::slice($array, 0, max(0, $length - $amount), $reindex));
    }

    /**
     * Drops elements in iterable until `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::dropUntil([1, 2, 3, 4], fn($v) => $v >= 3); // [3, 4]
     * Arr::dropUntil(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'c') // ['c' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropUntil(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropUntil($array, $condition, $reindex));
    }

    /**
     * Drops elements in iterable while `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::dropWhile([1, 2, 3, 4], fn($v) => $v < 3); // [3, 4]
     * Arr::dropWhile(['b' => 2, 'c' => 3], fn($v, $k) => $v < 3); // ['c' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropWhile(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropWhile($array, $condition, $reindex));
    }

    /**
     * Returns duplicate values in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::duplicates([1, 1, 2, null, 3, null]); // [1, null]
     * Arr::duplicates(['a' => 1, 'b' => 1, 'c' => 2]); // [1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return array<TKey, TValue>
     * An array containing duplicate values.
     */
    public static function duplicates(
        iterable $iterable,
    ): array
    {
        $array = [];
        $refs = [];
        foreach ($iterable as $key => $val) {
            $ref = self::valueToKeyString($val);
            $refs[$ref][] = $key;
            $array[$key] = $val;
        }

        $duplicates = [];
        foreach ($refs as $keys) {
            if (count($keys) > 1) {
                $duplicates[] = $array[$keys[0]];
            }
        }

        return $duplicates;
    }

    /**
     * Iterates through `$iterable` and invoke `$callback` for each element.
     *
     * Example:
     * ```php
     * Arr::each([1, 2], function(int $i) => {
     *     echo $i;
     * }); // echoes 12
     *
     * Arr::each(['a' => 1, 'b' => 2], function($v, $k) => {
     *     echo "$k$v";
     * }); // echoes a1b2
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): void $callback
     * Callback which is called for every element of `$iterable`.
     * @return void
     */
    public static function each(
        iterable $iterable,
        Closure $callback,
    ): void
    {
        iterator_to_array(Iter::each($iterable, $callback));
    }

    /**
     * Returns a new array with the given keys removed from `$iterable`.
     * Non-existent keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::except(['a' => 1, 'b' => 2], ['a']); // ['b' => 2]
     * Arr::except([1, 2, 3], [0, 2], reindex: true); // [2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array<int, array-key> $keys
     * Keys to be excluded.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in `$iterable`.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function except(
        iterable $iterable,
        iterable $keys,
        bool $safe = true,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        $missingKeys = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $copy)) {
                unset($copy[$key]);
            } elseif ($safe) {
                $missingKeys[] = $key;
            }
        }

        if ($safe && self::isNotEmpty($missingKeys)) {
            throw new MissingKeyException($missingKeys, [
                'iterable' => $iterable,
                'givenKeys' => $keys,
                'missingKeys' => $missingKeys,
            ]);
        }

        return $reindex
            ? array_values($copy)
            : $copy;
    }

    /**
     * Iterates over each element in iterable and passes them to the callback function.
     * If the callback function returns **true** the element is passed on to the new array.
     *
     * Example:
     * ```php
     * Arr::filter([null, '', 1], fn($v) => $v === ''); // [null, 1]
     * Arr::filter([null, '', 0], Str::isNotBlank(...)); // [0]
     * Arr::filter(['a' => true, 'b' => 1], fn($v) => $v === 1); // ['b' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function filter(
        iterable $iterable,
        Closure $condition,
        bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::filter($array, $condition, $reindex));
    }

    /**
     * Returns the first element in iterable.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::first([1, 2], fn($val) => $val > 1); // 2
     * Arr::first([1, 2], fn($val) => $val > 2); // NoMatchFoundException: Failed to find matching condition.
     * Arr::first([], fn($val) => $val > 2); // EmptyNotAllowedException: $iterable must contain at least one element.
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function first(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::firstOr($iterable, self::miss(), $condition);

        if ($result instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first index of iterable which meets the given condition.
     * Throws `NoMatchFoundException` if no condition is met.
     *
     * Example:
     * ```php
     * Arr::firstIndex([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstIndex([1, 2, 3], fn($val) => $val > 3); // null
     * Arr::firstIndex(['a' => 1, 'b' => 2], fn($val, $key) => $key === 'b'); // 1
     * Arr::firstIndex([1], fn($v, $k) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|TValue $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int|null
     */
    public static function firstIndex(
        iterable $iterable,
        mixed $condition,
    ): ?int
    {
        $result = self::firstIndexOrNull($iterable, $condition);

        if ($result === null) {
            throw new NoMatchFoundException('Failed to find matching condition.', [
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first index of iterable which meets the given condition.
     * Returns **null** if there were no matches.
     *
     * Example:
     * ```php
     * Arr::firstIndexOrNull([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstIndexOrNull([1, 2, 3], fn($val) => $val > 3); // null
     * Arr::firstIndexOrNull(['a' => 1, 'b' => 2], fn($val, $key) => $key === 'b'); // 1
     * Arr::firstIndexOrNull([1], fn($v, $k) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|TValue $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int|null
     */
    public static function firstIndexOrNull(
        iterable $iterable,
        mixed $condition,
    ): ?int
    {
        if (!($condition instanceof Closure)) {
            $condition = static fn(mixed $v): bool => $v === $condition;
        }

        $count = 0;
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                return $count;
            }
            ++$count;
        }
        return null;
    }

    /**
     * Returns the first key of the given iterable which meets the given condition.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::firstKey(['a' => 1, 'b' => 2], fn($v, $k) => $k === 'b'); // 'b'
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 3); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public static function firstKey(
        iterable $iterable,
        ?Closure $condition = null,
    ): int|string
    {
        $result = self::firstKeyOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        /** @var TKey */
        return $result;
    }

    /**
     * Returns the first key of the given iterable which meets the given condition.
     * Returns **null** if the given iterable is empty or if there were no
     * matching conditions.
     *
     * Example:
     * ```php
     * Arr::firstKey(['a' => 1, 'b' => 2], fn($v, $k) => $k === 'b'); // 'b'
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 3); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function firstKeyOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): int|string|null
    {
        $condition ??= static fn() => true;
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Returns the first element in iterable.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * If condition has no matches, value of `$default` is returned.
     *
     * Example:
     * ```php
     * Arr::firstOr([1, 2], 0, fn($val) => $val > 1); // 2
     * Arr::firstOr([1, 2], -1, fn($val) => $val > 2); // -1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function firstOr(
        iterable $iterable,
        mixed $default,
        ?Closure $condition = null,
    ): mixed
    {
        $condition ??= static fn() => true;

        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                return $val;
            }
        }

        return $default;
    }

    /**
     * Returns the first element in iterable.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * **null** is returned, if no element matches the `$condition` or is empty.
     *
     * Example:
     * ```php
     * Arr::firstOrNull([1, 2]); // 1
     * Arr::firstOrNull(['a' => 10, 'b' => 20]); // 10
     * Arr::firstOrNull([1, 2, 3], fn($v) => $v > 1); // 2
     * Arr::firstOrNull([1, 2, 3], fn($v) => $v > 3); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function firstOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        return self::firstOr($iterable, null, $condition);
    }

    /**
     * Applies the `$callback` to every element in the array, and flatten the results.
     *
     * Example:
     * ```php
     * Arr::flatMap([1, 2], fn($i) => [$i, -$i]); // [1, -1, 2, -2]
     * Arr::flatMap([['a' => 1], [2], 2], fn($a) => $a); // [1, 2, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * Callback to be used to map the values.
     * @return array<int, mixed>
     */
    public static function flatMap(
        iterable $iterable,
        Closure $callback,
    ): array
    {
        return iterator_to_array(Iter::flatMap($iterable, $callback));
    }

    /**
     * Collapse the given iterable upto the given `$depth` and turn it into a
     * single dimensional array.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * [Optional] Specify how deep a nested iterable should be flattened.
     * Depth must be >= 1. Default: 1.
     * @return array<int, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): array
    {
        $result = [];
        foreach (Iter::flatten($iterable, $depth) as $val) {
            $result[] = $val;
        }
        return $result;
    }

    /**
     * Flip the given iterable so that keys become values and values become keys.
     * Throws `InvalidKeyException` if elements contain types other than int|string.
     * Throws `DuplicateKeyException` if there are two values with the same value.
     * Set `$overwrite` to **true** to suppress this error.
     *
     * Example:
     * ```php
     * Arr::flip(['a' => 'b', 'c' => 'd']); // ['b' => 'a', 'd' => 'c']
     * Arr::flip([1, 2]); // [1 => 0, 2 => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue of array-key
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $overwrite
     * [Optional] Will overwrite existing keys if set to **true**.
     * If set to **false** and a duplicate key is found, a DuplicateKeyException will be thrown.
     * @return array<TValue, TKey>
     * The flipped array.
     */
    public static function flip(
        iterable $iterable,
        bool $overwrite = false,
    ): array
    {
        $flipped = [];
        foreach ($iterable as $key => $val) {
            if (is_not_array_key($val)) {
                throw new InvalidKeyException('Expected: array value of type int|string. Got: ' . gettype($val), [
                    'iterable' => $iterable,
                    'key' => $key,
                    'value' => $val,
                ]);
            }

            if (!$overwrite && array_key_exists($val, $flipped)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$val}", [
                    'iterable' => $iterable,
                    'key' => $val,
                ]);
            }

            $flipped[$val] = $key;
        }
        return $flipped;
    }

    /**
     * Take all the values in given iterable and fold it into a single value.
     *
     * Example:
     * ```php
     * Arr::fold([1, 2], 10, fn(int $fold, int $val, $key) => $fold + $val); // 13
     * Arr::fold([], 10, fn() => 1); // 10
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template U
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param U $initial
     * The initial value passed to the first Closure as result.
     * @param Closure(U, TValue, TKey): U $callback
     * Callback which is called for every key-value pair in iterable.
     * The callback arguments are `(mixed $result, mixed $value, mixed $key)`.
     * The returned value would be used as $result for the subsequent call.
     * @return U
     */
    public static function fold(
        iterable $iterable,
        mixed $initial,
        Closure $callback,
    ): mixed
    {
        $result = $initial;
        foreach ($iterable as $key => $val) {
            $result = $callback($result, $val, $key);
        }
        return $result;
    }

    /**
     * Converts iterable to array.
     *
     * Example:
     * ```php
     * Arr::from([1, 2]); // [1, 2]
     * Arr::from((function () {
     *   yield 1;
     *   yield 2;
     * })()); // 1, 2
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return array<TKey, TValue>
     */
    public static function from(
        iterable $iterable,
    ): array
    {
        return ($iterable instanceof Traversable)
            ? iterator_to_array($iterable)
            : $iterable;
    }

    /**
     * Returns the element of the given key.
     * Throws `InvalidKeyException` if key does not exist.
     *
     * Example:
     * ```php
     * Arr::get([1, 2], key: 1); // 2
     * Arr::get(['a' => 1], key: 'a'); // 1
     * Arr::get(['a' => 1], key: 'c'); // InvalidKeyException: Undefined array key "c"
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @return TValue
     */
    public static function get(
        iterable $iterable,
        int|string $key,
    )
    {
        $result = self::getOr($iterable, $key, self::miss());

        if ($result instanceof self) {
            throw new InvalidKeyException(is_string($key) ? "\"$key\"" : "$key", [
                'iterable' => $iterable,
                'key' => $key,
            ]);
        }

        return $result;
    }

    /**
     * Returns the element of the given key if it exists, `$default` is returned otherwise.
     *
     * Example:
     * ```php
     * Arr::getOr(['a' => 1, 'b' => 2], key: 'a', default: 9); // 1
     * Arr::getOr(['a' => 1, 'b' => 2], key: 'c', default: 9); // 9
     * Arr::getOr([1, 2], key: 0, default: 9); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @param TDefault $default
     * Default value to return if key is not found.
     * @return TValue|TDefault
     */
    public static function getOr(
        iterable $iterable,
        int|string $key,
        mixed $default,
    ): mixed
    {
        $array = self::from($iterable);
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

    /**
     * Returns the element of the given key if it exists, `null` otherwise.
     *
     * Example:
     * ```php
     * Arr::getOrNull([1, 2], 0); // 1
     * Arr::getOrNull(['a' => 1], 'a'); // 1
     * Arr::getOrNull([], 1); // null
     * Arr::getOrNull(['a' => 1], 'b'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @return TValue|null
     */
    public static function getOrNull(
        iterable $iterable,
        int|string $key,
    ): mixed
    {
        return self::getOr($iterable, $key, null);
    }

    /**
     * Groups the elements of the given iterable according to the string
     * returned by the callback.
     *
     * ```php
     * Arr::groupBy([1, 2, 3, 4], fn($n) => $n % 3); // [1 => [1, 4], 2 => [2], 0 => [3]]
     * Arr::groupBy([65, 66, 65], fn($n) => chr($n)); // ['A' => [65, 65], 'B' => [66]]
     * ```
     *
     * @template TGroupKey of array-key
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TGroupKey $callback
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TGroupKey, array<int|TKey, TValue>>
     */
    public static function groupBy(
        iterable $iterable,
        Closure $callback,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $map = [];
        foreach ($iterable as $key => $val) {
            $groupKey = $callback($val, $key);
            if (is_not_array_key($groupKey)) {
                throw new InvalidKeyException('Expected: Grouping key of type int|string. Got: ' . gettype($groupKey), [
                    'iterable' => $iterable,
                    'callback' => $callback,
                    'key' => $key,
                    'value' => $val,
                    'groupKey' => $groupKey,
                ]);
            }
            $map[$groupKey] ??= [];
            $reindex
                ? $map[$groupKey][] = $val
                : $map[$groupKey][$key] = $val;
        }

        return $map;
    }

    /**
     * Takes an array (reference) and insert given values at the given position.
     *
     * Throws `DuplicateKeyException` when the keys in `$values` already exist in `$array`.
     * Change the `overwrite` argument to **true** to suppress this error.
     *
     * Example:
     * ```php
     * $list = [1, 3];
     * Arr::insert($list, 1, [2]); // [1, 2, 3]
     *
     * $map = ['a' => 1, 'c' => 2];
     * Arr::insert($map, 1, ['b' => 1]); // ['a' => 1, 'b' => 1 'c' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * [Reference] Array to be inserted.
     * @param int $at
     * The position where the values will be inserted.
     * @param iterable<TKey, TValue> $values
     * One or more values that will be inserted.
     * @param bool $overwrite
     * [Optional] If **true**, duplicates will be overwritten for string keys.
     * If **false**, exception will be thrown on duplicate key.
     * Defaults to **false**.
     * @return void
     */
    public static function insert(
        array &$array,
        int $at,
        iterable $values,
        bool $overwrite = false,
    ): void
    {
        // NOTE: This used to be simply array_splice($array, $index, 0, $value) but passing replacement
        // in the 4th argument does not preserve keys so implementation was changed to the current one.

        $values = self::from($values);

        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($at < 0) {
            $at = $at === -1 ? count($array) : $at + 1;
        }

        $reindex = array_is_list($array);

        if (self::isDifferentArrayType($array, $values)) {
            $arrayType = self::getArrayType($array);
            $valuesType = self::getArrayType($values);
            $message = "\$values' array type ({$valuesType}) does not match \$array's ({$arrayType})";
            throw new TypeMismatchException($message, [
                'array' => $array,
                'at' => $at,
                'values' => $values,
                'overwrite' => $overwrite,
            ]);
        }

        // If array is associative and overwrite is not allowed, check for duplicates before applying.
        if (!$reindex && !$overwrite) {
            $duplicates = self::keys(self::intersectKeys($array, $values));
            if (self::isNotEmpty($duplicates)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$duplicates[0]}", [
                    'array' => $array,
                    'values' => $values,
                    'key' => $duplicates[0],
                ]);
            }
        }

        $tail = array_splice($array, $at);

        foreach ([$values, $tail] as $inserting) {
            foreach ($inserting as $key => $val) {
                $reindex
                    ? $array[] = $val
                    : $array[$key] = $val;
            }
        }
    }

    /**
     * Returns the intersection of given iterable's values.
     *
     * Example:
     * ```php
     * Arr::intersect([1, 2, 3], [2, 3, 4]); // [2, 3]
     * Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1]); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be intersected.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function intersect(
        iterable $iterable1,
        iterable $iterable2,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        if (self::isDifferentArrayType($array1, $array2)) {
            $array1Type = self::getArrayType($array1);
            $array2Type = self::getArrayType($array2);
            $message = "\$iterable1's inner type ({$array1Type}) does not match \$iterable2's ({$array2Type})";
            throw new TypeMismatchException($message, [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
            ]);
        }

        $reindex ??= array_is_list($array1);

        $result = array_intersect($array1, $array2);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Returns the intersection of given iterables using keys for comparison.
     *
     * Example:
     * ```php
     * Arr::intersectKeys(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1])); // ['b' => 2]
     * Arr::intersectKeys([1, 2, 3], [1, 3])); // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be intersected.
     * @return array<TKey, TValue>
     */
    public static function intersectKeys(
        iterable $iterable1,
        iterable $iterable2,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        if (self::isDifferentArrayType($array1, $array2)) {
            $array1Type = self::getArrayType($array1);
            $array2Type = self::getArrayType($array2);
            $message = "\$iterable1's array type ({$array1Type}) does not match \$iterable2's ({$array2Type})";
            throw new TypeMismatchException($message, [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
            ]);
        }

        return array_intersect_key($array1, $array2);
    }

    /**
     * Returns **true** if iterable is empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::isEmpty([1, 2]); // false
     * Arr::isEmpty([]); // true
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isEmpty(
        iterable $iterable,
    ): bool
    {
        /** @noinspection PhpLoopNeverIteratesInspection */
        foreach ($iterable as $ignored) {
            return false;
        }
        return true;
    }

    /**
     * Returns **true** if iterable is a list or empty, **false** if it's a map.
     *
     * Example:
     * ```php
     * Arr::isList([1, 2]); // true
     * Arr::isList(['a' => 1, 'b' => 2]); // false
     * Arr::isList([]); // true
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isList(
        iterable $iterable,
    ): bool
    {
        return array_is_list(self::from($iterable));
    }

    /**
     * Returns **true** if iterable is a map or empty, **false** if it's a list.
     *
     * Example:
     * ```php
     * Arr::isMap([1, 2]); // false
     * Arr::isMap(['a' => 1, 'b' => 2]); // true
     * Arr::isMap([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isMap(
        iterable $iterable,
    ): bool
    {
        if (self::isEmpty($iterable)) {
            return true;
        }
        return !self::isList($iterable);
    }

    /**
     * Returns **true** if iterable is not empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::isNotEmpty([1, 2]); // true
     * Arr::isNotEmpty([]); // false
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isNotEmpty(
        iterable $iterable,
    ): bool
    {
        return !self::isEmpty($iterable);
    }

    /**
     * Concatenates all the elements in the given array into a single
     * string using the provided `$glue`. Optional prefix and suffix can
     * also be added to the result string.
     *
     * Example:
     * ```php
     * Arr::join([1, 2], ', '); // "1, 2"
     * Arr::join([1, 2], ', ', '[', ']'); // "[1, 2]"
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @param string $glue
     * @param string|null $prefix
     * [Optional] Prefix added to the joined string.
     * @param string|null $suffix
     * [Optional] Suffix added to the joined string.
     * @return string
     */
    public static function join(
        iterable $iterable,
        string $glue,
        ?string $prefix = null,
        ?string $suffix = null,
    ): string
    {
        $str = null;
        foreach ($iterable as $value) {
            $str .= $str !== null
                ? $glue . $value
                : $value;
        }
        return $prefix . $str . $suffix;
    }

    /**
     * Return an array which contains values from `$iterable` with the keys
     * being the results of running `$callback($val, $key)` on each element.
     *
     * Throws `DuplicateKeyException` when the value returned by `$callback`
     * already exist in `$array` as a key. Set `$overwrite` to **true** to
     * suppress this error.
     *
     * Example:
     * ```php
     * Arr::keyBy([1, 2], fn($v, $k) => "a{$k}"); // ['a0' => 1, 'a1' => 2]
     * ```
     *
     * @template TNewKey of array-key
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TNewKey $callback
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten.
     * If **false**, exception will be thrown on duplicate keys.
     * @return array<TNewKey, TValue>
     */
    public static function keyBy(
        iterable $iterable,
        Closure $callback,
        bool $overwrite = false,
    ): array
    {
        $result = [];
        foreach ($iterable as $oldKey => $val) {
            $newKey = self::ensureKey($callback($val, $oldKey));

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$newKey}", [
                    'iterable' => $iterable,
                    'newKey' => $newKey,
                ]);
            }

            $result[$newKey] = $val;
        }
        return $result;
    }

    /**
     * Returns all the keys of the given iterable as an array.
     *
     * Example:
     * ```php
     * Arr::keys([1, 2]); // [0, 1]
     * Arr::keys(['a' => 1, 'b' => 2]); // ['a', 'b']
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @return array<int, TKey>
     */
    public static function keys(
        iterable $iterable,
    ): array
    {
        return iterator_to_array(Iter::keys($iterable));
    }

    /**
     * Returns the last element in iterable.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::last([1, 2], fn($val) => true); // 2
     * Arr::last([1, 2], fn($val) => false); // NoMatchFoundException: Failed to find matching condition.
     * Arr::last([], fn($val) => true); // EmptyNotAllowedException: $iterable must contain at least one element.
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function last(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::lastOr($iterable, self::miss(), $condition);

        if ($result instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the last index of `$iterable` which meets the given condition.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::lastIndex([1, 2, 3, 4], fn($v) => true); // 3
     * Arr::lastIndex(['a' => 1, 'b' => 2]); // 1
     * Arr::lastIndex([1, 2], fn($v) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int
     */
    public static function lastIndex(
        iterable $iterable,
        ?Closure $condition = null,
    ): int
    {
        $result = self::lastIndexOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the last index of iterable which meets the given condition.
     * Returns **null** if there were no matches.
     *
     * Example:
     * ```php
     * Arr::lastIndexOrNull([1, 2, 3, 4], fn($v) => true); // 3
     * Arr::lastIndexOrNull(['a' => 1, 'b' => 2]); // 1
     * Arr::lastIndexOrNull([1, 2], fn($v) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int|null
     */
    public static function lastIndexOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): ?int
    {
        $array = self::from($iterable);

        $count = count($array);

        if ($count > 0) {
            if ($condition === null) {
                return $count - 1;
            }
            end($array);
            while (($key = key($array)) !== null) {
                --$count;
                $val = current($array);
                /** @var TKey $key */
                /** @var TValue $val */
                if (self::verify($condition, $key, $val)) {
                    return $count;
                }
                prev($array);
            }
        }

        return null;
    }

    /**
     * Returns the last key of `$iterable` which meets the given condition.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::lastKey(['a' => 1, 'b' => 2]); // 'b'
     * Arr::lastKey([1, 2], fn($val) => true); // 2
     * Arr::lastKey([1, 2], fn($val) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public static function lastKey(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::lastKeyOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        /** @var TKey */
        return $result;
    }

    /**
     * Returns the last key of `$iterable` which meets the given condition.
     * Returns **null** if condition is not met.
     *
     * Example:
     * ```php
     * Arr::lastKeyOrNull(['a' => 1, 'b' => 2]); // 'b'
     * Arr::lastKeyOrNull([1, 2], fn($val) => true); // 2
     * Arr::lastKeyOrNull([1, 2], fn($val) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function lastKeyOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $copy = self::from($iterable);
        end($copy);

        $condition ??= static fn() => true;

        while (($key = key($copy)) !== null) {
            $val = current($copy);
            /** @var TKey $key */
            /** @var TValue $val */
            if (self::verify($condition, $key, $val)) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * Returns the last element in `$iterable`.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns the value of `$default` if no condition met.
     *
     * Example:
     * ```php
     * Arr::lastOr([1, 2], 0, fn($val) => true); // 2
     * Arr::lastOr([1, 2], -1, fn($val) => false); // -1
     * Arr::lastOr([], 1); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function lastOr(
        iterable $iterable,
        mixed $default,
        ?Closure $condition = null,
    ): mixed
    {
        $array = self::from($iterable);
        end($array);

        $condition ??= static fn($v, $k) => true;

        while (($key = key($array)) !== null) {
            /** @var TKey $key */
            /** @var TValue $val */
            $val = current($array);
            if (self::verify($condition, $key, $val)) {
                return $val;
            }
            prev($array);
        }

        return $default;
    }

    /**
     * Returns the last element in iterable.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns **null** if no element matches the `$condition` or is empty.
     *
     * Example:
     * ```php
     * Arr::lastOrNull([1, 2]); // 2
     * Arr::lastOrNull(['a' => 10, 'b' => 20]); // 20
     * Arr::lastOrNull([1, 2, 3], fn($v) => true); // 3
     * Arr::lastOrNull([1, 2, 3], fn($v) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function lastOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        return self::lastOr($iterable, null, $condition);
    }

    /**
     * Returns a new array containing results returned from invoking
     * `$callback` on each element on `$iterable`.
     *
     * Example:
     * ```php
     * Arr::map([], fn($i) => true) // []
     * Arr::map(['', 'a', 'aa'], strlen(...)) // [0, 1, 2]
     * Arr::map(['a' => 1, 'b' => 2, 'c' => 3], fn($i) => $i * 2) // ['a' => 2, 'b' => 4, 'c' => 6]
     * Arr::map(['a', 'b', 'c'], fn($i, $k) => $k) // [0, 1, 2]
     * ```
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * Callback to be used to map the values.
     * @return array<TKey, TMapValue>
     */
    public static function map(
        iterable $iterable,
        Closure $callback,
    ): array
    {
        return iterator_to_array(Iter::map($iterable, $callback));
    }

    /**
     * Returns the largest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Throws `InvalidElementException`, If `$iterable` contains NAN.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::max([], fn($i) => true) // EmptyNotAllowedException
     * Arr::max([1, 2, 3]) // 3
     * Arr::max([-1, -2, -3]) // -1
     * Arr::max([-1, -2, -3], abs(...)) // 3
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue
     */
    public static function max(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $maxVal = self::maxOrNull($iterable, $by);

        if ($maxVal === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
            ]);
        }

        return $maxVal;
    }

    /**
     * Returns the largest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Returns **null** if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::maxOrNull([], fn($i) => true) // null
     * Arr::maxOrNull([1, 2, 3]) // 3
     * Arr::maxOrNull([-1, -2, -3]) // -1
     * Arr::maxOrNull([-1, -2, -3], abs(...)) // 3
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue|null
     */
    public static function maxOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $maxResult = null;
        $maxVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxVal = $val;
            }
        }

        if (is_float($maxVal) && is_nan($maxVal)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return $maxVal;
    }

    /**
     * Merge one or more iterables into a single array and returns it.
     *
     * If the given key is numeric, the keys will be renumbered with
     * an incremented number from the last number in the new array.
     *
     * If the two iterables have the same keys, the value inside the
     * iterable the comes later will overwrite the value in the key.
     *
     * This method will only merge the key value pairs of the root depth.
     *
     * Example:
     * ```php
     * // merge list
     * Arr::merge([1, 2], [3, 4]); // [1, 2, 3, 4]
     *
     * // merge assoc
     * Arr::merge(['a' => 1], ['b' => 2]); // ['a' => 1, 'b' => 2]
     *
     * // overrides key
     * Arr::merge(['a' => 1], ['a' => 2]); // ['a' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> ...$iterable
     * Iterable to be merged.
     * @return array<TKey, TValue>
     */
    public static function merge(
        iterable ...$iterable,
    ): array
    {
        $result = null;
        foreach ($iterable as $iter) {
            if ($result === null) {
                $result = self::from($iter);
                continue;
            }
            $result = self::mergeRecursive($result, $iter, 1);
        }

        if ($result === null) {
            throw new InvalidArgumentException('At least one iterable must be defined.');
        }

        return $result;
    }

    /**
     * Merge one or more iterables recursively into a single array and returns it.
     * Will merge recursively up to the given depth.
     *
     * @see merge for details on how keys and values are merged.
     *
     * Example:
     * ```php
     * Arr::mergeRecursive(
     *    ['a' => 1, 'b' => 2],
     *    ['a' => ['c' => 1]]
     * ); // ['a' => ['c' => 1], 'b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be merged.
     * @param int<1, max> $depth
     * [Optional] Defaults to INT_MAX
     * @return array<TKey, TValue>
     */
    public static function mergeRecursive(
        iterable $iterable1,
        iterable $iterable2,
        int $depth = PHP_INT_MAX,
    ): array
    {
        $merged = self::from($iterable1);
        $merging = self::from($iterable2);

        if (self::isDifferentArrayType($merged, $merging)) {
            throw new TypeMismatchException('Tried to merge list with map. Try converting the map to a list.', [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
                'depth' => $depth,
            ]);
        }

        foreach ($merging as $key => $val) {
            if (is_int($key)) {
                $merged[] = $val;
            } elseif ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($val)) {
                $left = $merged[$key];
                $right = $val;
                /**
                 * @var iterable<array-key, mixed> $left
                 * @var iterable<array-key, mixed> $right
                 */
                $merged[$key] = self::mergeRecursive($left, $right, $depth - 1);
            } else {
                $merged[$key] = $val;
            }
        }

        /** @var array<TKey, TValue> $merged */
        return $merged;
    }

    /**
     * Returns the smallest element from the given array.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::min([], fn($i) => true) // EmptyNotAllowedException
     * Arr::min([1, 2, 3]) // 1
     * Arr::min([-1, -2, -3]) // -3
     * Arr::min([-1, -2, -3], abs(...)) // 3
     * Arr::min([-INF, 0.0, INF]) // INF
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue
     */
    public static function min(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $minVal = self::minOrNull($iterable, $by);

        if ($minVal === null) {
            $exception = ($by !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $by,
            ]);
        }

        return $minVal;
    }

    /**
     * Returns the smallest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Returns **null** if the iterable is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minOrNull([], fn($i) => true) // null
     * Arr::minOrNull([1, 2, 3]) // 1
     * Arr::minOrNull([-1, -2, -3]) // -3
     * Arr::minOrNull([-1, -2, -3], abs(...)) // 3
     * Arr::minOrNull([-INF, 0.0, INF]) // INF
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue|null
     */
    public static function minOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $minResult = null;
        $minVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($minResult === null || $result < $minResult) {
                $minResult = $result;
                $minVal = $val;
            }
        }

        if (is_float($minVal) && is_nan($minVal)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return $minVal;
    }

    /**
     * Returns the smallest and largest element from `$iterable` as array{ min: , max: }.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minMax([-1, 0, 1]) // ['min' => -1, 'max' => 1]
     * Arr::minMax([1]) // ['min' => 1, 'max' => 1]
     * Arr::minMax([]) // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the highest number.
     * @return array{ min: TValue, max: TValue }
     */
    public static function minMax(
        iterable $iterable,
        ?Closure $by = null,
    ): array
    {
        $result = self::minMaxOrNull($iterable, $by);
        if ($result === null) {
            $exception = ($by !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $by,
            ]);
        }
        return $result;
    }

    /**
     * Returns the smallest and largest element from the given array.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * If the iterable is empty, **null** will be returned.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minMaxOrNull([-1, 0, 1]) // ['min' => -1, 'max' => 1]
     * Arr::minMaxOrNull([]) // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest and highest number.
     * @return array{ min: TValue, max: TValue }
     */
    public static function minMaxOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): ?array
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $minResult = null;
        $minVal = null;
        $maxResult = null;
        $maxVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($minResult === null || $result < $minResult) {
                $minResult = $result;
                $minVal = $val;
            }

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxVal = $val;
            }
        }

        if ($minVal === null || $maxVal === null) {
            return null;
        }

        if ((is_float($minVal) && is_nan($minVal)) || (is_float($maxVal) && is_nan($maxVal))) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return [
            'min' => $minVal,
            'max' => $maxVal,
        ];
    }

    /**
     * @param mixed ...$values
     * @return array<array-key, mixed>
     */
    public static function of(mixed ...$values): array
    {
        return $values;
    }

    /**
     * Returns a new array which only contain the elements that has matching
     * keys in the given iterable. Non-existent keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::only(['a' => 1, 'b' => 2, 'c' => 3], ['b', 'd']); // ['b' => 2]
     * Arr::only([1, 2, 3], [1]); // [2]
     * ```
     *
     * @template TKey of int|string
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Keys to be included.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in `$iterable`.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return (TKey is int ? array<int, TValue> : array<TKey, TValue>)
     */
    public static function only(
        iterable $iterable,
        iterable $keys,
        bool $safe = true,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $missingKeys = [];
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $reindex
                    ? $result[] = $array[$key]
                    : $result[$key] = $array[$key];
            } elseif ($safe) {
                $missingKeys[] = $key;
            }
        }

        if ($safe && self::isNotEmpty($missingKeys)) {
            throw new MissingKeyException($missingKeys, [
                'iterable' => $iterable,
                'givenKeys' => $keys,
                'missingKeys' => $missingKeys,
            ]);
        }

        return $result;
    }

    /**
     * Returns a list (array) with a given value padded to the right side of
     * `$iterable` up to `$length`.
     * To apply padding to the left instead, use a negative integer for `$length`.
     *
     * Padding can only be applied to a list, so make sure to provide an iterable
     * that only contain int as key. If an iterable with a string key is given,
     * a `TypeMismatchException` will be thrown.
     *
     * Example:
     * ```php
     * Arr::pad(['a'], 3, 'b'); // ['a', 'b', 'b']
     * Arr::pad([1], -3, 2); // [2, 2, 1]
     * Arr::pad('a' => 1], 2, 2); // TypeMismatchException
     * ```
     *
     * @template TValue
     * @param iterable<int, TValue> $iterable
     * Iterable to be traversed.
     * @param int $length
     * Apply padding until the array size reaches the given length.
     * @param TValue $value
     * Value inserted into each padding.
     * @return array<int, TValue>
     */
    public static function pad(
        iterable $iterable,
        int $length,
        mixed $value,
    ): array
    {
        $array = self::from($iterable);
        $arrSize = count($array);
        $absSize = abs($length);

        if (!array_is_list($array)) {
            throw new TypeMismatchException('Padding can only be applied to a list, map given.', [
                'iterable' => $iterable,
                'length' => $length,
                'value' => $value,
            ]);
        }

        if ($arrSize <= $absSize) {
            $repeated = array_fill(0, $absSize - $arrSize, $value);
            return $length > 0
                ? self::merge($array, $repeated)
                : self::merge($repeated, $array);
        }
        return $array;
    }

    /**
     * Returns list with two array elements.
     * All elements in `$iterable` evaluated to be **true** will be pushed to
     * the first array and all elements in that were evaluated as **false**.
     *
     * Example:
     * ```php
     * Arr::partition([1, 2, 3], fn($v) => (bool) ($v % 2)); // [[1, 3], [2]]
     * Arr::partition(['a' => 1, 'b' => 2], fn($v) => $v === 1); // [['a' => 1], ['b' => 2]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param Closure(TValue, TKey): bool $condition
     * @return array{ array<TKey, TValue>, array<TKey, TValue> }
     */
    public static function partition(
        iterable $iterable,
        Closure $condition,
    ): array
    {
        $arr = self::from($iterable);
        $isList = array_is_list($arr);
        $truthy = [];
        $falsy = [];
        foreach ($arr as $key => $value) {
            if (self::verify($condition, $key, $value)) {
                $isList
                    ? $truthy[] = $value
                    : $truthy[$key] = $value;
            } else {
                $isList
                    ? $falsy[] = $value
                    : $falsy[$key] = $value;
            }
        }
        return [$truthy, $falsy];
    }

    /**
     * Pops the element off the end of the given array (reference).
     * Throws `EmptyNotAllowedException`, if `&$array` is empty.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pop($array); // 1
     * Arr::pop($array); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be popped.
     * @return TValue
     */
    public static function pop(
        array &$array,
    ): mixed
    {
        $popped = self::popOrNull($array);

        if ($popped === null) {
            throw new EmptyNotAllowedException('&$array must contain at least one element.', [
                'array' => $array,
            ]);
        }

        return $popped;
    }

    /**
     * Pops elements off the end of the given array (reference).
     * Returns the popped elements in a new array.
     *
     * Example:
     * ```php
     * $array = [1, 2, 3];
     * Arr::popMany($array, 2); // [1] (and $array will be [2, 3])
     * Arr::popMany($array, 1); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be popped.
     * @param int $amount
     * Amount of elements to pop. Must be a positive integer.
     * @return array<TKey, TValue>
     */
    public static function popMany(
        array &$array,
        int $amount,
    ): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 1. Got: {$amount}", [
                'array' => $array,
                'amount' => $amount,
            ]);
        }
        return array_splice($array, -$amount);
    }

    /**
     * Pops the element off the end of the given array (reference).
     * Returns **null**, if the array is empty.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::popOrNull($array); // 1
     * Arr::popOrNull($array); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be popped.
     * @return TValue|null
     */
    public static function popOrNull(
        array &$array,
    ): mixed
    {
        return array_pop($array);
    }

    /**
     * Prepend value(s) to the front of `$iterable`.
     * The iterable must be convertable to a list.
     * Throws `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * Arr::prepend([1, 2], 0); // $array will be [0, 1, 2]
     * Arr::prepend([1, 2], 3, 4); // $array will be [1, 2, 3, 4]
     * ```
     *
     * @template T
     * @param array<int, T> $iterable
     * Iterable to be prepended.
     * @param T ...$value
     * Value(s) to be prepended to the array.
     * @return array<int, T>
     */
    public static function prepend(
        iterable $iterable,
        mixed ...$value,
    ): array
    {
        $array = self::from($iterable);
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'iterable' => $iterable,
                'values' => $value,
            ]);
        }
        if (!array_is_list($value)) {
            $value = array_values($value);
        }
        array_unshift($array, ...$value);
        return $array;
    }

    /**
     * Returns an array with elements that match `$condition` moved to the top.
     *
     * Example:
     * ```php
     * Arr::prioritize([1, 2, 3], fn($i) => $i === 2); // [2, 1, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function prioritize(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $isList = array_is_list($array);
        $reindex ??= $isList;

        $prioritized = [];
        $remains = [];
        foreach ($array as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                $isList
                    ? $prioritized[] = $val
                    : $prioritized[$key] = $val;
            } else {
                $isList
                    ? $remains[] = $val
                    : $remains[$key] = $val;
            }
        }

        $result = self::merge($prioritized, $remains);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Removes the given key from `&$array` and returns the pulled value.
     * If the given array is a list, the list will be re-indexed.
     * Throws `InvalidKeyException` if the given key is not found.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pull($array, 'a'); // 1
     * Arr::pull($array, 'a'); // InvalidKeyException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue
     */
    public static function pull(
        array &$array,
        int|string $key,
        ?bool $reindex = null,
    ): mixed
    {
        $result = self::pullOr($array, $key, self::miss(), $reindex);

        if ($result instanceof self) {
            throw new InvalidKeyException("Tried to pull undefined key \"$key\"", [
                'array' => $array,
                'key' => $key,
            ]);
        }

        return $result;
    }

    /**
     * Removes the given key from the array and returns the pulled value.
     * If the given key is not found, value of `$default` is returned instead.
     * If the given array is a list, the list will be re-indexed.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pullOr($array, 'a', -1); // 1
     * Arr::pullOr($array, 'a', -1); // -1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param array<TValue> &$array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param TDefault $default
     * Default value to be returned if `$key` is not found.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function pullOr(
        array &$array,
        int|string $key,
        mixed $default,
        ?bool $reindex = null,
    ): mixed
    {
        if (!array_key_exists($key, $array)) {
            return $default;
        }

        $reindex ??= array_is_list($array);

        $value = $array[$key];
        unset($array[$key]);

        if ($reindex) {
            self::reindex($array);
        }

        return $value;
    }

    /**
     * Removes the given key from the array and returns the pulled value.
     * If the given key is not found, **null** is returned instead.
     * If `&$array` is a list, the list will be re-indexed.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pullOrNull($array, 'a'); // 1
     * Arr::pullOrNull($array, 'a'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function pullOrNull(
        array &$array,
        int|string $key,
        ?bool $reindex = null,
    ): mixed
    {
        return self::pullOr($array, $key, null, $reindex);
    }

    /**
     * Removes `$keys` from the `&$array` and returns the pulled values as list.
     * If the given array is a list, the list will be re-indexed.
     *
     * Example:
     * ```php
     * $array = ['a' => 1, 'b' => 2, 'c' => 3];
     * Arr::pullMany($array, 'a'); // ['b' => 2, 'c' => 3]
     * Arr::pullMany($array, 'a'); // []
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be pulled.
     * @param iterable<TKey> $keys
     * Keys or indexes to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function pullMany(
        array &$array,
        iterable $keys,
        ?bool $reindex = null,
    ): array
    {
        $reindex ??= array_is_list($array);

        $pulled = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
                unset($array[$key]);
                $pulled[$key] = $value;
            }
        }

        if ($reindex) {
            self::reindex($array);
        }

        return $pulled;
    }

    /**
     * Pushes values to the end of the given list (reference).
     * Throws `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * $array = [1, 2]; Arr::push($array, 3); // [1, 2, 3]
     * $array = [1, 2]; Arr::push($array, 3, 4); // [1, 2, 3, 4]
     * $array = ['a' => 1]; Arr::push($array, 1); // TypeMismatchException
     * ```
     *
     * @template T
     * @param array<T> &$array
     * Array reference which the value is getting push to.
     * @param T ...$value
     * Value(s) to be pushed on to the array.
     * @return void
     */
    public static function push(
        array &$array,
        mixed ...$value,
    ): void
    {
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'array' => $array,
                'values' => $value,
            ]);
        }

        array_push($array, ...$value);
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduce([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue
     */
    public static function reduce(
        iterable $iterable,
        Closure $callback,
    ): mixed
    {
        $result = self::reduceOr($iterable, $callback, self::miss());

        if ($result instanceof self) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'callback' => $callback,
            ]);
        }

        return $result;
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns `$default` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduceOr([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * Arr::reduceOr([], fn($r, $v) => $r + $v, 'z'); // 'z'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @param TDefault $default
     * Value that is used when iterable is empty.
     * @return TValue|TDefault
     */
    public static function reduceOr(
        iterable $iterable,
        Closure $callback,
        mixed $default,
    ): mixed
    {
        $result = null;
        $initialized = false;
        foreach ($iterable as $key => $val) {
            if (!$initialized) {
                $result = $val;
                $initialized = true;
            } else {
                $result = $callback($result, $val, $key);
            }
        }

        return $initialized
            ? $result
            : $default;
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduceOrNull([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * Arr::reduceOrNull([], fn($r, $v) => $r + $v, 'z'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue|null
     */
    public static function reduceOrNull(
        iterable $iterable,
        Closure $callback,
    ): mixed
    {
        $result = self::reduceOr($iterable, $callback, self::miss());

        return ($result instanceof self)
            ? null
            : $result;
    }

    /**
     * Given array will be converted into list.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::reindex($array); // $array will be [1]
     * ```
     *
     * @param array<array-key, mixed> &$array
     * [Reference] Array to be re-indexed.
     * @return void
     */
    public static function reindex(
        array &$array,
    ): void
    {
        if (array_is_list($array)) {
            return;
        }

        $placeholder = [];
        foreach ($array as $key => $val) {
            unset($array[$key]);
            $placeholder[] = $val;
        }
        foreach ($placeholder as $i => $val) {
            $array[$i] = $val;
        }
    }

    /**
     * Removes the given value from `&$array`.
     * Limit can be set to specify the number of times a value should be removed.
     * Returns the keys of the removed value.
     *
     * Example:
     * ```php
     * $map = ['a' => 1, 'b' => 2, 'c' => 1];
     * Arr::remove($map, 1); // ['a', 'c'] and $map will be changed to ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to have the value removed.
     * @param TValue $value
     * Value to be removed.
     * @param int|null $limit
     * [Optional] Limits the number of items to be removed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<int, TKey>
     */
    public static function remove(
        array &$array,
        mixed $value,
        ?int $limit = null,
        ?bool $reindex = null,
    ): array
    {
        $count = 0;
        $limit ??= PHP_INT_MAX;
        $removed = [];

        // Must check before processing, since unset converts lists to assoc array.
        $reindex ??= array_is_list($array);

        foreach ($array as $key => $val) {
            if ($count < $limit && $val === $value) {
                unset($array[$key]);
                $removed[] = $key;
                ++$count;
            }
        }

        // if the list is an array, use array_splice to re-index
        if ($count > 0 && $reindex) {
            self::reindex($array);
        }

        return $removed;
    }

    /**
     * Removes the specified key from `&$array`.
     * Returns **true** if key exists, **false** otherwise.
     *
     * Example:
     * ```php
     * $map = ['a' => 1];
     * Arr::removeKey($map, 1); // ['a', 'c'] and $map will be changed to ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @param array<TKey, mixed> &$array
     * [Reference] Array to have the key removed.
     * @param TKey $key
     * Key to remove from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return bool
     */
    public static function removeKey(
        array &$array,
        int|string $key,
        ?bool $reindex = null,
    ): bool
    {
        return self::pullOrNull($array, $key, $reindex) !== null;
    }

    /**
     * Returns an array which contains `$iterable` for a given number of times.
     *
     * Example
     * ```php
     * Arr::repeat([1, 2], 2); // [1, 2, 1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int<0, max> $times
     * Number of times the given iterable will be repeated.
     * @return array<int, TValue>
     */
    public static function repeat(
        iterable $iterable,
        int $times,
    ): array
    {
        return iterator_to_array(Iter::repeat($iterable, $times), false);
    }

    /**
     * Returns an array which contains keys and values from `$iterable`
     * but with the `$search` value replaced with the `$replacement` value.
     *
     * Example:
     * ```php
     * Arr::replace([3, 1, 3], 3, 0); // [0, 1, 0]
     * Arr::replace(['a' => 1], 1, 2); // ['a' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return array<TKey, TValue>
     */
    public static function replace(
        iterable $iterable,
        mixed $search,
        mixed $replacement,
        int &$count = 0,
    ): array
    {
        return iterator_to_array(
            Iter::replace($iterable, $search, $replacement, $count),
        );
    }

    /**
     * Returns an array which contain all elements of `$iterable` in reverse order.
     *
     * Example:
     * ```php
     * Arr::reverse([1, 2]); // [2, 1]
     * Arr::reverse(['a' => 1, 'b' => 2]); // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function reverse(
        iterable $iterable,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $preserveKeys = !($reindex ?? array_is_list($array));
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Converts `$iterable` to an array and rotate the array to the right
     * by `$steps`. If `$steps` is a negative value, the array will rotate
     * to the left instead.
     *
     * Example:
     * ```php
     * Arr::rotate([1, 2, 3], 1);  // [2, 3, 1]
     * Arr::rotate([1, 2, 3], -1); // [3, 1, 2]
     * Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 1); // ['b' => 2, 'c' => 3, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $steps
     * Number of times the key/value will be rotated.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function rotate(
        iterable $iterable,
        int $steps,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $ptr = 0;
        $result = [];
        $rotated = [];

        if ($steps < 0) {
            $steps = count($array) + $steps;
        }

        if ($steps !== 0) {
            foreach ($array as $key => $val) {
                if ($ptr < $steps) {
                    $rotated[$key] = $val;
                } else {
                    $result[$key] = $val;
                }
                ++$ptr;
            }

            foreach ($rotated as $key => $val) {
                $result[$key] = $val;
            }
        }

        return ($reindex ?? array_is_list($array))
            ? array_values($result)
            : $result;
    }

    /**
     * Returns a random element from `$iterable`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sample(['a', 'b', 'c']); // 'b'
     * Arr::sample([]); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue
     */
    public static function sample(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);

        return $array[self::sampleKey($array, $randomizer)];
    }

    /**
     * Returns a random key picked from `$iterable`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleKey(['a', 'b', 'c']); // 1
     * Arr::sampleKey(['a' => 1, 'b' => 2, 'c' => 3]); // 'c'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey
     */
    public static function sampleKey(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $key = self::sampleKeyOrNull($iterable, $randomizer);

        if ($key === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'randomizer' => $randomizer,
            ]);
        }

        /** @var TKey $key */
        return $key;
    }

    /**
     * Returns a random key picked from `$iterable`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleKey(['a', 'b', 'c']); // 1
     * Arr::sampleKey(['a' => 1, 'b' => 2, 'c' => 3]); // 'c'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function sampleKeyOrNull(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);

        if (count($array) === 0) {
            return null;
        }

        return self::sampleKeys($array, 1, false, $randomizer)[0];
    }

    /**
     * Returns a list of random elements picked from `$iterable`.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than `$iterable`'s size.
     *
     * Example:
     * ```php
     * Arr::sampleKeys(['a', 'b', 'c'], 2); // [0, 2]
     * Arr::sampleKeys(['a' => 1, 'b' => 2, 'c' => 3], 2); // ['a', 'c'] <- without replacement
     * Arr::sampleKeys(['a' => 1, 'b' => 2, 'c' => 3], 2, true); // ['b', 'b'] <- with replacement
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return array<int, TKey>
     */
    public static function sampleKeys(
        iterable $iterable,
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): array
    {
        $randomizer ??= self::getDefaultRandomizer();
        $array = self::from($iterable);
        $max = count($array);

        if ($amount < 0 || $amount > $max) {
            throw new InvalidArgumentException('$amount must be between 0 and size of $iterable', [
                'iterable' => $iterable,
                'amount' => $amount,
                'replace' => $replace,
            ]);
        }

        if ($amount === 0) {
            return [];
        }

        if (!$replace) {
            // Randomizer::pickArrayKeys() returns keys in order, so we
            // shuffle the result to randomize the order as well.
            $keys = $randomizer->pickArrayKeys($array, $amount);
            return $randomizer->shuffleArray($keys);
        }

        $keys = array_keys($array);
        $max = count($keys) - 1;
        return array_map(
            static fn() => $keys[$randomizer->getInt(0, $max)],
            range(0, $amount - 1),
        );
    }

    /**
     * Returns a list of random elements picked from `$iterable`.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than `$iterable`'s size.
     *
     * Example:
     * ```php
     * Arr::sampleMany(['a', 'b', 'c'], 2, false); // ['a', 'b'] <- without replacement
     * Arr::sampleMany(['a', 'b', 'c'], 2, true); // ['c', 'c'] <- with replacement
     * Arr::sampleMany(['a' => 1], 1); // [1] <- map will be converted to list
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return array<int, TValue>
     */
    public static function sampleMany(
        iterable $iterable,
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): array
    {
        $array = self::from($iterable);
        return array_map(
            static fn($key) => $array[$key],
            self::sampleKeys($array, $amount, $replace, $randomizer),
        );
    }

    /**
     * Returns a random element from `$iterable`.
     * Returns `$default` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleOr(['a', 'b', 'c'], 'z'); // 'b'
     * Arr::sampleOr([], 'z'); // 'z'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param TDefault $default
     * Value that is used when iterable is empty.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function sampleOr(
        iterable $iterable,
        mixed $default,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);
        $key = self::sampleKeyOrNull($array, $randomizer);

        if ($key === null) {
            return $default;
        }

        return $array[$key];
    }

    /**
     * Returns a random element from `$iterable`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleOrNull(['a', 'b', 'c'], 'z'); // 'b'
     * Arr::sampleOrNull([]); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function sampleOrNull(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        return self::sampleOr($iterable, null, $randomizer);
    }

    /**
     * Runs the condition though each element of the given iterable and
     * will return **true** if all iterations that run through the condition
     * returned **true** or if the given iterable is empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::satisfyAll([1, 2], static fn($v) => is_int($v)); // true
     * Arr::satisfyAll([1, 2.1], static fn($v) => is_int($v)); // false
     * Arr::satisfyAll([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyAll(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs the condition though each element of the given iterable and
     * will return **true** if any iterations that run through the condition
     * returned **true**, **false** otherwise (including empty iterable).
     *
     * Example:
     * ```php
     * Arr::satisfyAny([1, null, 2, false], static fn($v) => is_null($v)); // true
     * Arr::satisfyAny([1, 2], static fn($v) => is_float($v)); // false
     * Arr::satisfyAny([]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyAny(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Runs the condition though each element of the given iterable and
     * will return **true** if all the iterations that run through the condition
     * returned **false**. **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::satisfyNone(['a', 'b'], static fn($v) => empty($v)); // true
     * Arr::satisfyNone([1, 2.1], static fn($v) => is_int($v)); // false
     * Arr::satisfyNone([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyNone(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs the condition though each element of the given iterable and
     * will return **true** if iterations that run through the condition
     * returned **true** only once, **false** otherwise (including empty iterable).
     *
     * Example:
     * ```php
     * Arr::satisfyOnce([1, 'a'], static fn($v) => is_int($v)); // true
     * Arr::satisfyOnce([1, 2], static fn($v) => is_int($v)); // false
     * Arr::satisfyOnce([]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyOnce(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        $satisfied = false;
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                if ($satisfied) {
                    return false;
                }
                $satisfied = true;
            }
        }
        return $satisfied;
    }

    /**
     * Add or update an entry in the given array.
     *
     * Example:
     * ```php
     * $map = ['a' => 1];
     * Arr::set($map, 'a', 2); // $map is now ['a' => 2]
     * Arr::set($map, 'b', 3); // $map is now ['a' => 2, 'b' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be set.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return void
     */
    public static function set(
        array &$array,
        int|string $key,
        mixed $value,
    ): void
    {
        $array[$key] = $value;
    }

    /**
     * Set an entry in the given array only if the entry already exists
     * in the array.
     *
     * Example:
     * ```php
     * $map = ['a' => 1]
     * Arr::setIfExists($map, 'a', 2); // true (and $map is now ['a' => 2])
     * Arr::setIfExists($map, 'b', 1); // false (and $map is still ['a' => 2])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be set.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return bool
     * **true** if set, **false** otherwise.
     */
    public static function setIfExists(
        array &$array,
        int|string $key,
        mixed $value,
    ): bool
    {
        if (self::containsKey($array, $key)) {
            self::set($array, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * Set an entry in the given array only if the entry does not exist
     * in the array.
     *
     * Example:
     * ```php
     * $map = ['a' => 1]
     * Arr::setIfNotExists($map, 'a', 2); // false (and $map is still ['a' => 1])
     * Arr::setIfNotExists($map, 'b', 1); // true (and $map is now ['a' => 1, 'b' => 1])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * Reference to the target array.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return bool
     * **true** if set, **false** otherwise.
     */
    public static function setIfNotExists(
        array &$array,
        int|string $key,
        mixed $value,
    ): bool
    {
        if (self::doesNotContainKey($array, $key)) {
            self::set($array, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * Shift an element off the beginning of `&$array`.
     * Throws a `EmptyNotAllowedException` if `&$array` is empty.
     *
     * Example:
     * ```php
     * $list = [1, 2];
     * Arr::shift($list); // 1 ($list is now [2])
     *
     * $empty = [];
     * Arr::shift($empty); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be shifted.
     * @return TValue
     * The shifted value.
     */
    public static function shift(
        array &$array,
    ): mixed
    {
        $shifted = self::shiftOrNull($array);

        if ($shifted === null) {
            throw new EmptyNotAllowedException('&$array must contain at least one element.', [
                'array' => $array,
            ]);
        }

        return $shifted;
    }

    /**
     * Shift an element off the beginning of array n times.
     * Returns the shifted elements as an array.
     *
     * Example:
     * ```php
     * $list = [1, 2, 3];
     * Arr::shiftMany($list, 2); // [1, 2] ($list is now [3])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be shifted.
     * @param int $amount
     * Amount of elements to be shifted.
     * Must be an integer with value >= 1.
     * @return array<TKey, TValue>
     * Elements that were shifted.
     */
    public static function shiftMany(
        array &$array,
        int $amount,
    ): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 1. Got: {$amount}", [
                'array' => $array,
                'amount' => $amount,
            ]);
        }
        return array_splice($array, 0, $amount);
    }

    /**
     * Shift an element off the beginning of array.
     * Returns **null** if the given array is empty.
     *
     * Example:
     * ```php
     * $list = [1, 2];
     * Arr::shiftOrNull($list); // 1 ($list is now [2])
     *
     * $empty = [];
     * Arr::shiftOrNull($empty); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> &$array
     * [Reference] Array to be shifted.
     * @return TValue|null
     * The shifted value.
     */
    public static function shiftOrNull(
        array &$array,
    ): mixed
    {
        return array_shift($array);
    }

    /**
     * Converts iterable to array and shuffles the array.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function shuffle(
        iterable $iterable,
        ?bool $reindex = null,
        ?Randomizer $randomizer = null,
    ): array
    {
        $randomizer ??= self::getDefaultRandomizer();
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $keys = $randomizer->shuffleArray(array_keys($array));

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $array[$key];
        }

        return $reindex
            ? self::values($shuffled)
            : $shuffled;
    }

    /**
     * Returns a shallow copy of a portion of an iterable into a new array.
     *
     * Example:
     * ```php
     * Arr::slice([1, 2, 3, 4], 1, 2); // [2, 3]
     * Arr::slice([1, 2, 3], -2); // [2, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $offset
     * Starting position of the slice.
     * @param int $length
     * Length of the slice.
     * [Optional] Defaults to `INT_MAX`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function slice(
        iterable $iterable,
        int $offset,
        int $length = PHP_INT_MAX,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::slice($array, $offset, $length, $reindex));
    }

    /**
     * Returns the only element in the given iterable.
     * If a condition is also given, the sole element of a sequence that satisfies a specified
     * condition is returned instead.
     * Throws `InvalidArgumentException` if there are more than one element in `$iterable`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sole([1]); // 1
     * Arr::sole([1, 2]); // InvalidArgumentException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function sole(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $found = self::miss();
        $count = 0;
        foreach ($iterable as $key => $val) {
            if ($condition === null || $condition($val, $key)) {
                ++$count;
                $found = $val;
            }
        }

        if ($count > 1) {
            throw new InvalidArgumentException("Expected only one element in result. $count given.", [
                'iterable' => $iterable,
                'condition' => $condition,
                'count' => $count,
            ]);
        }

        if ($found instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
                'count' => $count,
            ]);
        }

        return $found;
    }

    /**
     * Sort the given iterable by value in the given order.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $ascending
     * Sort by ascending order if **true**, descending order if **false**.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * @param bool|null $reindex
     * Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     * @see self::sortDesc()
     *
     * @template TKey of array-key
     * @template TValue
     * @see self::sortAsc()
     */
    public static function sort(
        iterable $iterable,
        bool $ascending,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        if ($by !== null) {
            $refs = self::map($copy, $by);
            $ascending
                ? asort($refs, $flag)
                : arsort($refs, $flag);
            $sorted = self::map($refs, fn($val, $key) => $copy[$key]);
        } else {
            $sorted = $copy;
            $ascending
                ? asort($sorted, $flag)
                : arsort($sorted, $flag);
        }

        return $reindex
            ? array_values($sorted)
            : $sorted;
    }

    /**
     * Sort the given iterable by value in ascending order.
     *
     * Example:
     * ```php
     * Arr::sort([2, 0, 1]);  // [0, 1, 2]
     * Arr::sort(['b' => 2, 'a' => 1]);  // ['a' => 1, 'b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortAsc(
        iterable $iterable,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        return self::sort($iterable, true, $by, $flag, $reindex);
    }

    /**
     * Sort the given iterable by key in ascending order.
     *
     * Example:
     * ```php
     * Arr::sortByKey(['b' => 0, 'a' => 1]);  // ['a' => 1, 'b' => 0]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKey(
        iterable $iterable,
        bool $ascending,
        int $flag = SORT_REGULAR,
    ): array
    {
        $copy = self::from($iterable);
        $ascending
            ? ksort($copy, $flag)
            : krsort($copy, $flag);
        return $copy;
    }

    /**
     * Sort the given iterable by key in ascending order.
     *
     * Example:
     * ```php
     * Arr::sortByKey(['b' => 0, 'a' => 1]);  // ['a' => 1, 'b' => 0]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKeyAsc(
        iterable $iterable,
        int $flag = SORT_REGULAR,
    ): array
    {
        $copy = self::from($iterable);
        ksort($copy, $flag);
        return $copy;
    }

    /**
     * Sort the given iterable by key in descending order.
     *
     * Example:
     * ```php
     * Arr::sortByKeyDesc(['a' => 1, 'b' => 2]);  // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKeyDesc(
        iterable $iterable,
        int $flag = SORT_REGULAR,
    ): array
    {
        $copy = self::from($iterable);
        krsort($copy, $flag);
        return $copy;
    }

    /**
     * Sort the given iterable by value in descending order.
     *
     * Example:
     * ```php
     * Arr::sortDesc([2, 0, 1]);  // [2, 1, 0]
     * Arr::sortDesc(['a' => 1, 'b' => 2]);  // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortDesc(
        iterable $iterable,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        return self::sort($iterable, false, $by, $flag, $reindex);
    }

    /**
     * Sorts the given iterable by value using the provided comparison function.
     *
     * Example:
     * ```php
     * Arr::sortWith([1, 3, 2], fn($a, $b) => ($a < $b) ? -1 : 1); // [1, 2, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue): int $comparison
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortWith(
        iterable $iterable,
        Closure $comparison,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        uasort($copy, $comparison);

        return $reindex
            ? array_values($copy)
            : $copy;
    }

    /**
     * Sorts the given iterable by key using the provided comparison function.
     *
     * Example:
     * ```php
     * $compare = fn($a, $b) => ($a < $b) ? -1 : 1;
     * Arr::sortWithKey([1 => 'a', 3 => 'b', 2 => 'c'], $compare); // [1 => 'a', 2 => 'c', 3 => 'b']
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TKey, TKey): int $comparison
     * @return array<TKey, TValue>
     */
    public static function sortWithKey(
        iterable $iterable,
        Closure $comparison,
    ): array
    {
        $copy = self::from($iterable);
        uksort($copy, $comparison);
        return $copy;
    }

    /**
     * Get the sum of the elements inside iterable.
     * The elements must be af type int or float.
     * Throws `InvalidElementException` if the iterable contains NAN.
     *
     * Example:
     * ```php
     * Arr::sum([1, 2, 3]); // 6
     * Arr::sum(['b' => 1, 'a' => 2]); // 3
     * Arr::sum([]) // 0
     * ```
     *
     * @template TKey of array-key
     * @template TValue of int|float
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function sum(
        iterable $iterable,
    ): mixed
    {
        $total = 0;
        foreach ($iterable as $val) {
            $total += $val;
        }

        if (is_float($total) && is_nan($total)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return $total;
    }

    /**
     * Returns the symmetric difference of the given iterables.
     * Throws `TypeMismatchException` if comparing a map to a list.
     *
     * Example:
     * ```php
     * Arr::symDiff([1, 2], [2, 3]); // [1, 3]
     * Arr::symDiff(['a' => 1, 'b' => 2], ['c' => 2, 'd' => 3]); // ['a' => 1, 'd' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be traversed.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] User defined comparison callback.
     * Return 1 if first argument is greater than the 2nd.
     * Return 0 if first argument is equal to the 2nd.
     * Return -1 if first argument is less than the 2nd.
     * Defaults to **null**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function symDiff(
        iterable $iterable1,
        iterable $iterable2,
        Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        if (self::isDifferentArrayType($array1, $array2)) {
            throw new TypeMismatchException('Tried to compare list with map. Try converting the map to a list.', [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
                'by' => $by,
            ]);
        }

        $by ??= static fn(mixed $a, mixed $b): int => $a <=> $b;
        $reindex ??= array_is_list($array1) && array_is_list($array2);

        $diff1 = array_udiff($array1, $array2, $by);
        $diff2 = array_udiff($array2, $array1, $by);

        if ($reindex) {
            $diff1 = array_values($diff1);
            $diff2 = array_values($diff2);
        }

        return self::merge($diff1, $diff2);
    }

    /**
     * Take the first n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::takeFirst([2, 3, 4], 2); // [2, 3]
     * Arr::takeFirst(['a' => 1, 'b' => 2], 1); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be taken from the front. Must be a positive integer.
     * @return array<TKey, TValue>
     */
    public static function takeFirst(
        iterable $iterable,
        int $amount,
    ): array
    {
        return iterator_to_array(Iter::takeFirst($iterable, $amount));
    }

    /**
     * Take the last n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::takeLast([2, 3, 4], 2); // [3, 4]
     * Arr::takeLast(['a' => 1, 'b' => 2], 1); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function takeLast(
        iterable $iterable,
        int $amount,
        ?bool $reindex = null,
    ): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected \$amount >= 0. Got: {$amount}", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        $array = self::from($iterable);
        $length = count($array);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::slice($array, $length - $amount, PHP_INT_MAX, $reindex));
    }

    /**
     * Takes elements in `$iterable` until `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::takeUntil([1, 1, 3, 2], fn($v) => $v > 2); // [1, 1]
     * Arr::takeUntil(['b' => 1, 'a' => 3], fn($v) => $v > 2); // ['b' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return array<TKey, TValue>
     */
    public static function takeUntil(
        iterable $iterable,
        Closure $condition,
    ): array
    {
        return iterator_to_array(Iter::takeUntil($iterable, $condition));
    }

    /**
     * Takes elements in iterable while `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::takeWhile([1, 1, 3, 2], fn($v) => $v <= 2); // [1, 1]
     * Arr::takeWhile(['a' => 1, 'b' => 4], fn($v) => $v < 4); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return array<TKey, TValue>
     */
    public static function takeWhile(
        iterable $iterable,
        Closure $condition,
    ): array
    {
        return iterator_to_array(Iter::takeWhile($iterable, $condition));
    }

    /**
     * Generates URL encoded query string.
     * Encoding follows RFC3986 (spaces will be converted to `%20`).
     *
     * Example:
     * ```php
     * Arr::toUrlQuery(['a' => 1, 'b' => 2]); // "a=1&b=2"
     * Arr::toUrlQuery(['a' => 1], 't'); // t%5Ba%5D=1 (decoded: t[a]=1)
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param string|null $namespace
     * [Optional] Adds namespace to wrap the iterable.
     * Defaults to **null**.
     * @return string
     */
    public static function toUrlQuery(
        iterable $iterable,
        ?string $namespace = null,
    ): string
    {
        $array = self::from($iterable);
        $data = $namespace !== null ? [$namespace => $array] : $array;
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Removes duplicate values from the given iterable and returns it
     * as an array.
     *
     * This is different from `array_unique` in that, this does not do a
     * string conversion before comparing.
     * For example, `array_unique([1, true])` will result in: `[1]` but
     * doing `Arr::unique([1, true])` will result in: `[1, true]`.
     *
     * Example:
     * ```php
     * Arr::unique([1, 1, null, 0, '']); // [1, null, 0, '']
     * Arr::unique([1, 2, 3, 4], fn($v) => $v % 2); // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to check for duplicates.
     * [Optional] Defaults to **null**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function unique(
        iterable $iterable,
        ?Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $by ??= static fn(mixed $val, int|string $key) => $val;

        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $refs = [];
        $preserved = [];

        foreach ($array as $key => $val) {
            $ref = self::valueToKeyString($by($val, $key));
            if (!array_key_exists($ref, $refs)) {
                $refs[$ref] = null;
                $reindex
                    ? $preserved[] = $val
                    : $preserved[$key] = $val;
            }
        }
        return $preserved;
    }

    /**
     * Convert an iterable to a list. Any keys will be dropped.
     *
     * Example:
     * ```php
     * Arr::values(['a' => 1, 'b' => 2]) // [1, 2]
     * Arr::values([1 => 1, 0 => 2]) // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return array<int, TValue>
     */
    public static function values(
        iterable $iterable,
    ): array
    {
        return iterator_to_array(Iter::values($iterable));
    }

    /**
     * Ensure that a given key is an int or string and return the key.
     *
     * @param mixed $key
     * @return array-key
     */
    private static function ensureKey(
        mixed $key,
    ): int|string
    {
        if (is_int($key) || is_string($key)) {
            return $key;
        }

        throw new InvalidKeyException('Expected: key of type int|string. ' . gettype($key) . ' given.', [
            'key' => $key,
        ]);
    }

    /**
     * Converts value into an identifiable string.
     * Used for checking for duplicates.
     *
     * @see self::duplicates()
     * @see self::unique()
     *
     * @param mixed $val
     * @return string
     */
    private static function valueToKeyString(
        mixed $val,
    ): string
    {
        try {
            return match (true) {
                is_null($val) => '',
                is_int($val) => "i:$val",
                is_float($val) => "f:$val",
                is_bool($val) => "b:$val",
                is_string($val) => "s:$val",
                is_array($val) => 'a:' . json_encode(array_map(self::valueToKeyString(...), $val), JSON_THROW_ON_ERROR),
                is_object($val) => 'o:' . spl_object_id($val),
                is_resource($val) => 'r:' . get_resource_id($val),
                default => throw new UnreachableException('Invalid Type: ' . gettype($val), [
                    'value' => $val,
                ]),
            };
        } catch (JsonException $e) {
            throw new UnreachableException(
                message: 'json_encode should never throw an error here but it did.',
                context: ['value' => $val],
                previous: $e,
            );
        }
    }

    /**
     * Runs the given condition with `$val` and `$key` as the argument.
     *
     * @template TKey of array-key
     * @template TValue
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param TKey $key
     * Key to pass on to the given condition.
     * @param TValue $val
     * Value to pass on to the given condition.
     * @return bool
     */
    private static function verify(
        Closure $condition,
        mixed $key,
        mixed $val,
    ): bool
    {
        return $condition($val, $key);
    }

    /**
     * Set the default randomizer for the following methods.
     *
     * @see self::sample()
     * @see self::sampleMany()
     * @see self::shuffle()
     *
     * @param Randomizer|null $randomizer
     * @return void
     */
    public static function setDefaultRandomizer(
        ?Randomizer $randomizer,
    ): void
    {
        self::$defaultRandomizer = $randomizer;
    }

    /**
     * Get the default randomizer used in this class.
     *
     * @return Randomizer
     */
    public static function getDefaultRandomizer(): Randomizer
    {
        return self::$defaultRandomizer ??= new Randomizer();
    }

    /**
     * @param array<array-key, mixed> $array1
     * @param array<array-key, mixed> $array2
     * @return bool
     */
    private static function isDifferentArrayType(array $array1, array $array2): bool
    {
        return array_is_list($array1) !== array_is_list($array2)
            && $array1 !== self::EMPTY
            && $array2 !== self::EMPTY;
    }

    /**
     * @param array<array-key, mixed> $array
     * @return string
     */
    private static function getArrayType(array $array): string
    {
        return array_is_list($array) ? 'list' : 'map';
    }

    /**
     * A dummy instance used to check for miss in methods below.
     *
     * @return self
     * @see atOrNull
     * @see firstOrNull
     * @see getOrNull
     * @see lastOrNull
     * @see pullOrNull
     */
    private static function miss(): self
    {
        static $miss = new self();
        return $miss;
    }
}
