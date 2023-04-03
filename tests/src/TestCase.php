<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Generator;
use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @return Generator<TKey, TValue>
     */
    public function toGenerator(array $array): Generator
    {
        foreach ($array as $key => $val) {
            yield $key => $val;
        }
    }

    /**
     * @template T
     * @param iterable<int, T> $items
     * @return Vec<T>
     */
    protected function vec(iterable $items = []): Vec
    {
        return new Vec($items);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $items
     * @return Map<TKey, TValue>
     */
    protected function map(iterable $items = []): Map
    {
        return new Map($items);
    }
}
