<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use InvalidArgumentException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;

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
        $this->assertNull($this->vec()->coalesce(), 'empty');
    }

    public function test_coalesce_all_null(): void
    {
        $this->expectExceptionMessage('Non-null value could not be found.');
        $this->expectException(NoMatchFoundException::class);
        $this->assertNull($this->vec([null, null])->coalesce(), 'all null');
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
}
