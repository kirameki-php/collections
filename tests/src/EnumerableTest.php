<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;

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
}
