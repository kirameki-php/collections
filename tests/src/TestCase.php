<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Generator;
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
}
