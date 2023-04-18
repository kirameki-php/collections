<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\Map;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use function dump;

class EnumerableTest extends TestCase
{
    public function test_at(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->at(0));
        $this->assertSame(2, $this->vec([1, 2])->at(1));
        $this->assertSame(2, $this->vec([1, 2])->at(-1));
        $this->assertSame(1, $this->vec([1, 2])->at(-2));

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->at(0));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->at(1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->at(-1));
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->at(-2));
    }

    public function test_at_out_of_bounds_positive(): void
    {
        $this->expectException(IndexOutOfBoundsException::class);
        $this->vec([1, 2])->at(2);
    }

    public function test_at_out_of_bounds_negative(): void
    {
        $this->expectException(IndexOutOfBoundsException::class);
        $this->vec([1, 2])->at(-3);
    }

    public function test_atOr(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->atOr(0, 0));
        $this->assertSame(2, $this->vec([1, 2])->atOr(1, -1));
        $this->assertSame(2, $this->vec([1, 2])->atOr(-1, -1));
        $this->assertSame(1, $this->vec([1, 2])->atOr(0, -2));
        $this->assertSame('fb', $this->vec([1, 2])->atOr(2, 'fb'), 'out of bounds');
        $this->assertSame('fb', $this->vec([1, 2])->atOr(-3, 'fb'), 'out of bounds');

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->atOr(0, 0));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->atOr(1, -1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->atOr(-1, -1));
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->atOr(0, -2));
        $this->assertSame('fb', $this->map(['a' => 1, 'b' => 2])->atOr(2, 'fb'), 'out of bounds positive');
        $this->assertSame('fb', $this->map(['a' => 1, 'b' => 2])->atOr(-3, 'fb'), 'out of bounds negative');
    }

    public function test_atOrNull(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->atOrNull(0));
        $this->assertSame(2, $this->vec([1, 2])->atOrNull(1));
        $this->assertSame(2, $this->vec([1, 2])->atOrNull(-1));
        $this->assertSame(1, $this->vec([1, 2])->atOrNull(-2));
        $this->assertNull($this->vec([1, 2])->atOrNull(2), 'out of bounds positive');
        $this->assertNull($this->vec([1, 2])->atOrNull(-3), 'out of bounds negative');

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->atOrNull(0));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->atOrNull(1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->atOrNull(-1));
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->atOrNull(-2));
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->atOrNull(2), 'out of bounds positive');
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->atOrNull(-3), 'out of bounds negative');
    }

    public function test_coalesce(): void
    {
        $this->assertSame(1, $this->vec([1])->coalesce(), 'single value');
        $this->assertSame(1, $this->vec([1, null])->coalesce(), 'first value');
        $this->assertSame(1, $this->vec([null, 1])->coalesce(), 'skip null');
        $this->assertSame(1, $this->vec([null, 1, 2])->coalesce(), 'skip null');

        $this->assertSame(1, $this->map(['a' => 1])->coalesce(), 'single value');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => null])->coalesce(), 'first value');
        $this->assertSame(1, $this->map(['a' => null, 'b' => 1])->coalesce(), 'skip null');
        $this->assertSame(2, $this->map(['b' => null, 'a' => 2])->coalesce(), 'reverse alphabetical');
    }

    public function test_coalesce_empty(): void
    {
        $this->expectExceptionMessage('Non-null value could not be found.');
        $this->expectException(NoMatchFoundException::class);
        $this->vec()->coalesce();
    }

    public function test_coalesce_all_null(): void
    {
        $this->expectExceptionMessage('Non-null value could not be found.');
        $this->expectException(NoMatchFoundException::class);
        $this->vec([null, null])->coalesce();
    }

    public function test_coalesceOrNull(): void
    {
        $this->assertNull($this->vec()->coalesceOrNull(), 'empty');
        $this->assertNull($this->vec([null, null])->coalesceOrNull(), 'all null');
        $this->assertSame(1, $this->vec([1])->coalesceOrNull(), 'single value');
        $this->assertSame(1, $this->vec([1, null])->coalesceOrNull(), 'first value');
        $this->assertSame(1, $this->vec([null, 1])->coalesceOrNull(), 'skip null');
        $this->assertSame(1, $this->vec([null, 1, 2])->coalesceOrNull(), 'skip null');

        $this->assertNull($this->map()->coalesceOrNull(), 'empty');
        $this->assertNull($this->map(['a' => null, 'b' => null])->coalesceOrNull(), 'all null');
        $this->assertSame(1, $this->map(['a' => 1])->coalesceOrNull(), 'single value');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => null])->coalesceOrNull(), 'first value');
        $this->assertSame(1, $this->map(['a' => null, 'b' => 1])->coalesceOrNull(), 'skip null');
        $this->assertSame(2, $this->map(['b' => null, 'a' => 2])->coalesceOrNull(), 'reverse alphabetical');
    }

    public function test_compact(): void
    {
        $this->assertSame([], $this->vec()->compact()->toArray(), 'empty');
        $this->assertSame([], $this->vec([null, null])->compact()->toArray(), 'all null');
        $this->assertSame([1], $this->vec([1, null])->compact()->toArray(), 'null at end');
        $this->assertSame([1], $this->vec([null, 1])->compact()->toArray(), 'null at front');
        $this->assertSame([1, 2], $this->vec([1, null, 2])->compact()->toArray(), 'null in the middle');
        $this->assertSame([1, 2], $this->vec([1, 2])->compact()->toArray(), 'no nulls');
        $this->assertSame([1, 2], $this->vec([null, 1, null, 2, null])->compact()->toArray(), 'mixed nulls');
        $this->assertSame([0, false, ''], $this->vec([null, 0, false, ''])->compact()->toArray(), 'null like');
        $this->assertSame([[1]], $this->vec([[1, null]])->compact(2)->toArray(), 'nested');

        $this->assertSame([], $this->map()->compact()->toArray(), 'empty');
        $this->assertSame([], $this->map(['a' => null, 'b' => null])->compact()->toArray(), 'all null');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => null])->compact()->toArray(), 'null at end');
        $this->assertSame(['b' => 1], $this->map(['a' => null, 'b' => 1])->compact()->toArray(), 'null at front');
        $this->assertSame(['a' => 1, 'c' => 2], $this->map(['a' => 1, 'b' => null, 'c' => 2])->compact()->toArray(), 'null in the middle');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->compact()->toArray(), 'no nulls');
        $this->assertSame(['b' => 1, 'd' => 2], $this->map(['a' => null, 'b' => 1, 'c' => null, 'd' => 2])->compact()->toArray(), 'mixed nulls');
        $this->assertSame(['b' => 0, 'c' => false, 'd' => ''], $this->map(['a' => null, 'b' => 0, 'c' => false, 'd' => ''])->compact()->toArray(), 'null like');
        $this->assertSame(['a' => [1]], $this->map(['a' => [1, null]])->compact(2)->toArray(), 'nested');
    }

    public function test_compact_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: 0');
        $this->vec()->compact(0); /** @phpstan-ignore-line */
    }

    public function test_compact_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: -1');
        $this->vec()->compact(-1); /** @phpstan-ignore-line */
    }

    public function test_contains(): void
    {
        $this->assertFalse($this->vec()->contains(0), 'empty');
        $this->assertFalse($this->vec()->contains(null), 'null in empty');
        $this->assertFalse($this->vec()->contains(''), 'blank in empty');
        $this->assertTrue($this->vec([''])->contains(''), 'contains blank');
        $this->assertFalse($this->vec([1, 2])->contains('2'), 'wrong type');
        $this->assertTrue($this->vec([1, 2])->contains(2), 'has int');
        $this->assertTrue($this->vec([1, null])->contains(null), 'has null');

        $this->assertFalse($this->map()->contains(0), 'empty');
        $this->assertFalse($this->map()->contains(null), 'null in empty');
        $this->assertFalse($this->map()->contains(''), 'blank in empty');
        $this->assertTrue($this->map(['a' => ''])->contains(''), 'contains blank');
        $this->assertFalse($this->map(['a' => 1, 'b' => 2])->contains('2'), 'wrong type');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->contains(2), 'has int');
        $this->assertTrue($this->map(['a' => 1, 'b' => null])->contains(null), 'has null');
    }

    public function test_containsAll(): void
    {
        $this->assertTrue($this->vec()->containsAll([]), 'both empty');
        $this->assertFalse($this->vec()->containsAll([null]), 'null in empty');
        $this->assertFalse($this->vec()->containsAll(['']), 'blank in empty');
        $this->assertTrue($this->vec([''])->containsAll(['']), 'contains blank');
        $this->assertTrue($this->vec([''])->containsAll([]), 'empty arg');
        $this->assertFalse($this->vec([1, 2])->containsAll(['2']), 'wrong type');
        $this->assertFalse($this->vec([1, 2])->containsAll([3, 4]), 'no match');
        $this->assertFalse($this->vec([1, 2])->containsAll([1, 2, 3]), 'match partial');
        $this->assertTrue($this->vec([1, 2])->containsAll([2]), 'has int');
        $this->assertTrue($this->vec([1])->containsAll([1, 1]), 'more in haystack');
        $this->assertTrue($this->vec([1, null])->containsAll([null, 1]), 'match out of order');
        $this->assertTrue($this->vec([1, null])->containsAll([1, null]), 'exact match');
        $this->assertTrue($this->vec([1])->containsAll(['b' => 1]), 'assoc as arg');
        $this->assertTrue($this->vec([1])->containsAll($this->vec([1])), 'vec as arg');

        $this->assertTrue($this->map()->containsAll([]), 'both empty');
        $this->assertFalse($this->map()->containsAll([null]), 'null in empty');
        $this->assertFalse($this->map()->containsAll(['']), 'blank in empty');
        $this->assertTrue($this->map(['a' => ''])->containsAll(['']), 'contains blank');
        $this->assertTrue($this->map(['a' => ''])->containsAll([]), 'empty arg');
        $this->assertFalse($this->map(['a' => 1, 'b' => 2])->containsAll(['2']), 'wrong type');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->containsAll([2]), 'has int');
        $this->assertTrue($this->map(['a' => 1])->containsAll([1, 1]), 'more in haystack');
        $this->assertTrue($this->map(['a' => 1, 'b' => null])->containsAll([null, 1]), 'match out of order');
        $this->assertTrue($this->map(['a' => 1, 'b' => null])->containsAll([1, null]), 'exact match');
        $this->assertTrue($this->map(['a' => 1])->containsAll(['b' => 1]), 'assoc as arg');
        $this->assertTrue($this->map(['a' => 1])->containsAll($this->vec([1])), 'vec as arg');
    }

    public function test_containsAny(): void
    {
        $this->assertFalse($this->vec()->containsAny([]), 'both empty');
        $this->assertFalse($this->vec()->containsAny([null]), 'null in empty');
        $this->assertFalse($this->vec()->containsAny(['']), 'blank in empty');
        $this->assertTrue($this->vec([''])->containsAny(['']), 'contains blank');
        $this->assertFalse($this->vec([''])->containsAny([]), 'empty arg');
        $this->assertFalse($this->vec([1, 2])->containsAny(['2']), 'wrong type');
        $this->assertTrue($this->vec([1, 2])->containsAny([2]), 'has int');
        $this->assertFalse($this->vec([1, 2])->containsAny([3, 4]), 'no match');
        $this->assertTrue($this->vec([1, 2])->containsAny([1, 2, 3]), 'match partial');
        $this->assertTrue($this->vec([1])->containsAny([1, 1]), 'more in haystack');
        $this->assertTrue($this->vec([1, null])->containsAny([null, 1]), 'match out of order');
        $this->assertTrue($this->vec([1, null])->containsAny([1, null]), 'exact match');
        $this->assertTrue($this->vec([1])->containsAny(['b' => 1]), 'assoc as arg');
        $this->assertTrue($this->vec([1])->containsAny($this->vec([1])), 'vec as arg');

        $this->assertFalse($this->map()->containsAny([]), 'both empty');
        $this->assertFalse($this->map()->containsAny([null]), 'null in empty');
        $this->assertFalse($this->map()->containsAny(['']), 'blank in empty');
        $this->assertTrue($this->map(['a' => ''])->containsAny(['']), 'contains blank');
        $this->assertFalse($this->map(['a' => ''])->containsAny([]), 'empty arg');
        $this->assertFalse($this->map(['a' => 1, 'b' => 2])->containsAny(['2']), 'wrong type');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->containsAny([2]), 'has int');
        $this->assertTrue($this->map(['a' => 1])->containsAny([1, 1]), 'more in haystack');
        $this->assertTrue($this->map(['a' => 1, 'b' => null])->containsAny([null, 1]), 'match out of order');
    }

    public function test_containsNone(): void
    {
        $this->assertTrue($this->vec()->containsNone([]), 'both empty');
        $this->assertTrue($this->vec()->containsNone([null]), 'null in empty');
        $this->assertTrue($this->vec()->containsNone(['']), 'blank in empty');
        $this->assertFalse($this->vec([''])->containsNone(['']), 'contains blank');
        $this->assertTrue($this->vec([''])->containsNone([]), 'empty arg');
        $this->assertTrue($this->vec([1, 2])->containsNone(['2']), 'wrong type');
        $this->assertFalse($this->vec([1, 2])->containsNone([2]), 'has int');
        $this->assertTrue($this->vec([1, 2])->containsNone([3, 4]), 'no match');
        $this->assertFalse($this->vec([1, 2])->containsNone([1, 2, 3]), 'match partial');
        $this->assertFalse($this->vec([1])->containsNone([1, 1]), 'more in haystack');
        $this->assertFalse($this->vec([1, null])->containsNone([null, 1]), 'match out of order');
        $this->assertFalse($this->vec([1, null])->containsNone([1, null]), 'exact match');
        $this->assertFalse($this->vec([1])->containsNone(['b' => 1]), 'assoc as arg');
        $this->assertFalse($this->vec([1])->containsNone($this->vec([1])), 'vec as arg');

        $this->assertTrue($this->map()->containsNone([]), 'both empty');
        $this->assertTrue($this->map()->containsNone([null]), 'null in empty');
        $this->assertTrue($this->map()->containsNone(['']), 'blank in empty');
        $this->assertFalse($this->map(['a' => ''])->containsNone(['']), 'contains blank');
        $this->assertTrue($this->map(['a' => ''])->containsNone([]), 'empty arg');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->containsNone(['2']), 'wrong type');
        $this->assertFalse($this->map(['a' => 1, 'b' => 2])->containsNone([2]), 'has int');
        $this->assertTrue($this->map(['a' => 1])->containsAny([1, 1]), 'more in haystack');
        $this->assertTrue($this->map(['a' => 1, 'b' => null])->containsAny([null, 1]), 'match out of order');
    }

    public function test_count(): void
    {
        $this->assertSame(0, $this->vec()->count());
        $this->assertSame(2, $this->vec([1, 2])->count());
        $this->assertSame(1, $this->vec([1, 2, 3])->count(fn(int $n) => $n %2 === 0), 'with condition');
        $this->assertSame(0, $this->vec([1, 2])->count(fn() => false), 'no condition match');

        $this->assertSame(0, $this->map()->count());
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->count());
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->count(fn(int $n) => $n %2 === 0), 'with condition');
        $this->assertSame(0, $this->map(['a' => 1, 'b' => 2])->count(fn() => false), 'no condition match');
    }
}
