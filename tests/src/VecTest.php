<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\TypeMismatchException;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class VecTest extends TestCase
{
    /**
     * @template T
     * @param iterable<int, T> $items
     * @return Vec<T>
     */
    private function vec(iterable $items = []): Vec
    {
        return new Vec($items);
    }

    public function test_constructor(): void
    {
        $vec = $this->vec([1, 2]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([1, 2], $vec->toArray());
    }

    public function test_constructor_no_args(): void
    {
        $vec = new Vec();
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->toArray());
    }

    public function test_constructor_empty(): void
    {
        $vec = $this->vec([]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->toArray());
    }

    public function test_constructor_non_list(): void
    {
        $this->expectExceptionMessage('$items must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        $this->vec(['a' => 1]);
    }

    public function test_loop(): void
    {
        $vec = Vec::loop(3);
        self::assertSame([0, 1, 2], $vec->toArray());
    }

    public function test_jsonSerialize(): void
    {
        self::assertSame([1, 2], $this->vec([1, 2])->jsonSerialize());
    }

    public function test_offsetExists(): void
    {
        self::assertTrue(isset($this->vec([1, 2])[0]));
        self::assertFalse(isset($this->vec([1, 2])[2]));

        self::assertTrue($this->vec([1, 2])->offsetExists(0));
        self::assertFalse($this->vec([1, 2])->offsetExists(2));
    }

    public function test_offsetExists_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string');
        $this->expectException(InvalidKeyException::class);
        isset($this->vec([1, 2])['0']);
    }

    public function test_offsetGet(): void
    {
        self::assertSame(1, $this->vec([1, 2])[0]);
        self::assertSame(null, $this->vec([1, 2])[2]);

        self::assertSame(1, $this->vec([1, 2])->offsetGet(0));
        self::assertSame(null, $this->vec([1, 2])->offsetGet(2));
    }

    public function test_offsetGet_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string');
        $this->expectException(InvalidKeyException::class);
        $this->vec([1, 2])['0'];
    }

    public function test_offsetSet(): void
    {
        $vec = $this->vec([1, 2]);
        $vec[0] = 3;
        self::assertSame([3, 2], $vec->toArray(), 'Overwriting existing value');

        $vec = $this->vec([1, 2]);
        $vec[] = 3;
        self::assertSame([1, 2, 3], $vec->toArray(), 'Appending to the end');

        $vec = $this->vec([1, 2]);
        $vec->offsetSet(0, 3);
        self::assertSame([3, 2], $vec->toArray(), 'Overwriting existing value using method');

        $vec = $this->vec([1, 2]);
        $vec->offsetSet(null, 3);
        self::assertSame([1, 2, 3], $vec->toArray(), 'Appending to the end using method');
    }

    public function test_offsetSet_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string');
        $this->expectException(InvalidKeyException::class);
        $this->vec([1, 2])['0'] = 3;
    }

    public function test_offsetUnset(): void
    {
        $vec = $this->vec([1, 2]);
        unset($vec[0]);
        self::assertSame([2], $vec->toArray(), 'Unset first element');

        $vec = $this->vec([1, 2, 3]);
        unset($vec[1]);
        self::assertSame([1, 3], $vec->toArray(), 'Unset middle element');

        $vec = $this->vec([1, 2]);
        unset($vec[2]);
        self::assertSame([1, 2], $vec->toArray(), 'Unset non-existing element');

        $vec = $this->vec([1, 2]);
        $vec->offsetUnset(0);
        self::assertSame([2], $vec->toArray(), 'Unset first element using method');

    }

    public function test_offsetUnset_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string');
        $this->expectException(InvalidKeyException::class);
        unset($this->vec([1, 2])['0']);
    }

    public function test_append(): void
    {
        self::assertSame([3], $this->vec()->append(3)->toArray(), 'on empty');
        self::assertSame([1], $this->vec([1])->append()->toArray(), 'append nothing');
        self::assertSame([1, 2, 3], $this->vec([1, 2])->append(3)->toArray());
        self::assertSame([1, 2, 3, 3, 4], $this->vec([1, 2])->append(3, 3, 4)->toArray(), 'append multiple');
        self::assertSame([1, 2, 3], $this->vec([1, 2])->append(a: 3)->toArray(), 'named args');
    }

    public function test_map():void
    {
        $mapped = $this->vec([1, 2])->map(fn($v): int => $v * 2);
        self::assertSame([2, 4], $mapped->toArray());
    }

    public function test_pad(): void
    {
        self::assertSame([], $this->vec()->pad(0, 1)->toArray(), 'zero pad');
        self::assertSame([1, 1], $this->vec()->pad(2, 1)->toArray(), 'on empty');
        self::assertSame([0], $this->vec([0])->pad(1, 1)->toArray(), 'no pad');
        self::assertSame([0], $this->vec([0])->pad(-1, 1)->toArray(), 'negative pad');
        self::assertSame([1, 1, 0], $this->vec([0])->pad(-3, 1)->toArray(), 'negative pad');
        self::assertSame([0, 1, 1], $this->vec([0])->pad(3, 1)->toArray(), 'pad left');
    }

    public function test_prepend(): void
    {
        self::assertSame([], $this->vec()->prepend()->toArray(), 'empty prepend on empty array');
        self::assertSame([1], $this->vec()->prepend(1)->toArray(), 'prepend on empty array');
        self::assertSame([1], $this->vec([1])->prepend()->toArray(), 'empty prepend');
        self::assertSame([1, 2], $this->vec([2])->prepend(1)->toArray(), 'prepend one');
        self::assertSame([1, 1, 2], $this->vec([2])->prepend(1, 1)->toArray(), 'prepend multi');
        self::assertSame([1, 2], $this->vec([2])->prepend(a: 1)->toArray(), 'named args');
    }

    public function test_repeat(): void
    {
        self::assertSame([], $this->vec()->repeat(2)->toArray(), 'empty repeat');
        self::assertSame([1], $this->vec([1])->repeat(1)->toArray(), 'repeat single');
        self::assertSame([1, 2], $this->vec([1, 2])->repeat(1)->toArray(), 'repeat multiple');
        self::assertSame([1, 1], $this->vec([1])->repeat(2)->toArray(), 'repeat x2');
        self::assertSame([1, 2, 1, 2], $this->vec([1, 2])->repeat(2)->toArray(), 'repeat multi x2');
    }

    public function test_repeat_negative(): void
    {
        $this->expectExceptionMessage('Expected: $times >= 0. Got: -1');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->repeat(-1);
    }

    public function test_reindex(): void
    {
        self::assertSame([1, 2], $this->vec([null, 1, 2])->compact()->toArray(), 'with compact');
        self::assertSame([1, 3], $this->vec([1, 2, 3])->except([1])->toArray(), 'with except');
        self::assertSame([1, 3], $this->vec([1, 2, 3])->filter(fn($n) => (bool)($n % 2))->toArray(), 'with filter');
        self::assertSame([2], $this->vec([1, 2, 3])->only([1])->toArray(), 'with only');
        self::assertSame([2, 1], $this->vec([1, 2])->reverse()->toArray(), 'with reverse');
    }

    public function test_sampleIndex(): void
    {
        self::assertLessThanOrEqual(2, $this->vec([1, 2, 3])->sampleIndex(), 'sample index');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        $index = $this->vec([10, 20, 30])->sampleIndex($randomizer);
        self::assertSame(1, $index, 'sample index with randomizer');
    }

    public function test_sampleIndex_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->sampleIndex();
    }

    public function test_sampleIndexOrNull(): void
    {
        self::assertLessThanOrEqual(2, $this->vec([1, 2, 3])->sampleIndexOrNull(), 'sample index');

        self::assertNull($this->vec()->sampleIndexOrNull(), 'sample empty array');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        $index = $this->vec([10, 20, 30])->sampleIndexOrNull($randomizer);
        self::assertSame(1, $index, 'sample index with randomizer');
    }

    public function test_sampleIndexes(): void
    {
        self::assertSame([], $this->vec([1])->sampleIndexes(0)->toArray(), 'sample zero');
        self::assertSame([0], $this->vec([1])->sampleIndexes(1)->toArray(), 'sample one');
        self::assertSame([0, 0], $this->vec([1])->sampleIndexes(2, true)->toArray(), 'sample overflow with replacement');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        self::assertSame([0, 1], $this->vec([10, 20])->sampleIndexes(2, false, $randomizer)->toArray(), 'size == amount');
        self::assertSame([2, 1], $this->vec([10, 20, 30])->sampleIndexes(2, false, $randomizer)->toArray(), 'size > amount');
        self::assertSame([1, 1], $this->vec([10, 20])->sampleIndexes(2, true, $randomizer)->toArray(), 'size == amount with replacement');
        self::assertSame([0, 0], $this->vec([10, 20])->sampleIndexes(2, true, $randomizer)->toArray(), 'size > amount with replacement');
        self::assertSame([1, 1, 0], $this->vec([10, 20])->sampleIndexes(3, true, $randomizer)->toArray(), 'size < amount with replacement');
    }

    public function test_sampleIndexes_overflow(): void
    {
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->sampleIndexes(2);
    }
}
