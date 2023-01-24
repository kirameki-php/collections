<?php declare(strict_types=1);

namespace SouthPointe\Collections\Utils;

use Closure;
use Generator;
use Webmozart\Assert\Assert;
use function count;
use function is_iterable;
use const PHP_INT_MAX;

final class Iter
{
    /**
     * Creates a Generator which chunks elements into given size and passes it to the Generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of each chunk. Must be >= 1.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size, bool $reindex = false): Generator
    {
        Assert::positiveInteger($size);

        $remaining = $size;
        $chunk = [];
        foreach ($iterable as $key => $val) {
            $reindex
                ? $chunk[] = $val
                : $chunk[$key] = $val;

            if (--$remaining === 0) {
                yield $chunk;
                $remaining = $size;
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            yield $chunk;
        }
    }

    /**
     * Creates a Generator which iterates while ignoring all **null** values.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function compact(iterable $iterable, bool $reindex = false): Generator
    {
        foreach ($iterable as $key => $val) {
            if ($val !== null) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Creates a Generator which iterates after the given amount.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of elements to drop from the front. Must be >= 0.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropFirst(iterable $iterable, int $amount, bool $reindex = false): Generator
    {
        Assert::greaterThanEq($amount, 0);
        return self::slice($iterable, $amount, PHP_INT_MAX, $reindex);
    }

    /**
     * Creates a Generator which iterates and drop values until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, Closure $condition, bool $reindex = false): Generator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && self::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Creates a Generator which iterates and drop values while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, Closure $condition, bool $reindex = false): Generator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && !self::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Creates a Generator that will send the key/value to the generator if the condition is **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function filter(iterable $iterable, Closure $condition, bool $reindex = false): Generator
    {
        foreach ($iterable as $key => $val) {
            if (self::verify($condition, $key, $val)) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Creates a Generator that will map and also flatten the result of the callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Generator<int, mixed>
     */
    public static function flatMap(iterable $iterable, Closure $callback): Generator
    {
        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);
            if (is_iterable($result)) {
                foreach ($result as $each) {
                    yield $each;
                }
            } else {
                yield $result;
            }
        }
    }

    /**
     * Creates a Generator that will flatten any iterable value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Generator<mixed, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): Generator
    {
        Assert::positiveInteger($depth);
        return self::flattenImpl($iterable, $depth);
    }

    /**
     * Actual implementation for flatten.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Generator<mixed, mixed>
     */
    protected static function flattenImpl(iterable $iterable, int $depth = 1): Generator
    {
        foreach ($iterable as $key => $val) {
            if (is_iterable($val) && $depth > 0) {
                foreach (self::flattenImpl($val, $depth - 1) as $_key => $_val) {
                    yield $_key => $_val;
                }
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator that will send the key to the generator as value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @return Generator<int, TKey>
     */
    public static function keys(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $item) {
            yield $key;
        }
    }

    /**
     * Creates a Generator that will send the result of the closure as value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * Closure which the result will be mapped as value.
     * @return Generator<TKey, TMapValue>
     */
    public static function map(iterable $iterable, Closure $callback): Generator
    {
        foreach ($iterable as $key => $val) {
            yield $key => $callback($val, $key);
        }
    }

    /**
     * Creates a Generator that will repeat through the iterable for a given amount of times.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int<0, max> $times
     * Amount of times the iterable will be repeated.
     * @return Generator<TKey, TValue>
     */
    public static function repeat(iterable $iterable, int $times): Generator
    {
        Assert::greaterThanEq($times, 0);

        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $key => $val) {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator that will iterate starting from the offset up to the given length.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $offset
     * If offset is non-negative, the sequence will start at that offset.
     * If offset is negative, the sequence will start that far from the end.
     * @param int $length
     * If length is given and is positive, then the sequence will have up to that many elements in it.
     * If the iterable is shorter than the length, then only the available array elements will be present.
     * If length is given and is negative then the sequence will stop that many elements from the end.
     * If it is omitted, then the sequence will have everything from offset up until the end.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, int $length = PHP_INT_MAX, bool $reindex = false): Generator
    {
        $isNegativeOffset = $offset < 0;
        $isNegativeLength = $length < 0;

        if ($isNegativeOffset || $isNegativeLength) {
            $count = 0;
            foreach ($iterable as $ignored) {
                ++$count;
            }
            if ($isNegativeOffset) {
                $offset = $count + $offset;
            }
            if ($isNegativeLength) {
                $length = $count + $length;
            }
        }

        $i = 0;
        foreach ($iterable as $key => $val) {
            if ($i++ < $offset) {
                continue;
            }

            if ($i > $offset + $length) {
                break;
            }

            if ($reindex) {
                yield $val;
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator which takes the given amount of values.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * $iterable Iterable to be traversed.
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return Generator<TKey, TValue>
     */
    public static function takeFirst(iterable $iterable, int $amount): Generator
    {
        Assert::greaterThanEq($amount, 0);
        return self::slice($iterable, 0, $amount);
    }

    /**
     * Creates a Generator which iterates and sends key/value to the generator until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return Generator<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, Closure $condition): Generator
    {
        foreach ($iterable as $key => $item) {
            if (!self::verify($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Creates a Generator which iterates and sends key/value to the generator while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return Generator<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, Closure $condition): Generator
    {
        foreach ($iterable as $key => $item) {
            if (self::verify($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Creates a Generator that will send only the value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return Generator<int, TValue>
     */
    public static function values(iterable $iterable): Generator
    {
        foreach ($iterable as $val) {
            yield $val;
        }
    }

    /**
     * Invoke the condition closure and make sure that it returns a boolean value.
     *
     * @template TKey of array-key
     * @template TValue
     * @param Closure(TValue, TKey): bool $condition
     * The condition that returns a boolean value.
     * @param TKey $key
     * the 2nd argument for the condition closure.
     * @param TValue $val
     * 1st argument for the condition closure.
     * @return bool
     */
    private static function verify(Closure $condition, mixed $key, mixed $val): bool
    {
        return $condition($val, $key);
    }
}
