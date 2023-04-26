<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\MissingKeyException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Dumper\Config;
use Kirameki\Dumper\Writer;
use stdClass;
use function fopen;
use function fread;
use function fseek;

class EnumerableTest extends TestCase
{
    public function test_all(): void
    {
        $this->assertSame([], $this->vec()->all());
        $this->assertSame([], $this->map()->all());
        $this->assertSame([1, 2], $this->vec([1, 2])->all());
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->all());
    }

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
        $this->assertSame([], $this->vec()->compact()->all(), 'empty');
        $this->assertSame([], $this->vec([null, null])->compact()->all(), 'all null');
        $this->assertSame([1], $this->vec([1, null])->compact()->all(), 'null at end');
        $this->assertSame([1], $this->vec([null, 1])->compact()->all(), 'null at front');
        $this->assertSame([1, 2], $this->vec([1, null, 2])->compact()->all(), 'null in the middle');
        $this->assertSame([1, 2], $this->vec([1, 2])->compact()->all(), 'no nulls');
        $this->assertSame([1, 2], $this->vec([null, 1, null, 2, null])->compact()->all(), 'mixed nulls');
        $this->assertSame([0, false, ''], $this->vec([null, 0, false, ''])->compact()->all(), 'null like');
        $this->assertSame([[1]], $this->vec([[1, null]])->compact(2)->all(), 'nested');

        $this->assertSame([], $this->map()->compact()->all(), 'empty');
        $this->assertSame([], $this->map(['a' => null, 'b' => null])->compact()->all(), 'all null');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => null])->compact()->all(), 'null at end');
        $this->assertSame(['b' => 1], $this->map(['a' => null, 'b' => 1])->compact()->all(), 'null at front');
        $this->assertSame(['a' => 1, 'c' => 2], $this->map(['a' => 1, 'b' => null, 'c' => 2])->compact()->all(), 'null in the middle');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->compact()->all(), 'no nulls');
        $this->assertSame(['b' => 1, 'd' => 2], $this->map(['a' => null, 'b' => 1, 'c' => null, 'd' => 2])->compact()->all(), 'mixed nulls');
        $this->assertSame(['b' => 0, 'c' => false, 'd' => ''], $this->map(['a' => null, 'b' => 0, 'c' => false, 'd' => ''])->compact()->all(), 'null like');
        $this->assertSame(['a' => [1]], $this->map(['a' => [1, null]])->compact(2)->all(), 'nested');
    }

    public function test_compact_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: 0');
        $this->vec()->compact(0);
    }

    public function test_compact_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: -1');
        $this->vec()->compact(-1);
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
        $this->assertSame(1, $this->vec([1, 2, 3])->count(fn(int $n) => $n % 2 === 0), 'with condition');
        $this->assertSame(0, $this->vec([1, 2])->count(fn() => false), 'no condition match');

        $this->assertSame(0, $this->map()->count());
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->count());
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->count(fn(int $n) => $n % 2 === 0), 'with condition');
        $this->assertSame(0, $this->map(['a' => 1, 'b' => 2])->count(fn() => false), 'no condition match');
    }

    public function test_diff(): void
    {
        $this->assertSame([], $this->vec()->diff([])->all(), 'both empty');
        $this->assertSame([], $this->vec()->diff([1])->all(), 'diff empty');
        $this->assertSame([1], $this->vec([1])->diff([])->all(), 'empty arg');
        $this->assertSame([2], $this->vec([1, 2])->diff([1])->all(), 'diff single');
        $this->assertSame([2], $this->vec([1, 2, 1])->diff([1])->all(), 'diff multiple items');
        $this->assertSame([2], $this->vec([1, 2])->diff([1, 1])->all(), 'diff multiple in arg');
        $this->assertSame([], $this->vec([1, 2])->diff([1, 2])->all(), 'diff exact');
        $this->assertSame([], $this->vec([2])->diff([1, 2])->all(), 'diff bigger args');
        $this->assertSame([0], $this->vec([2, 2, 0])->diff([2])->all(), 're-indexed');
        $this->assertSame([], $this->vec([1, 2, 3])->diff([3, 4, 5], fn() => 0)->all(), 'use by callback reject');
        $this->assertSame([1, 2], $this->vec([1, 2])->diff([2, 3], fn() => 1)->all(), 'use by callback accept');

        $this->assertSame([], $this->map()->diff([])->all(), 'both empty');
        $this->assertSame([], $this->map()->diff([1])->all(), 'diff empty');
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->diff([])->all(), 'empty arg');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2])->diff(['a' => 1])->all(), 'diff single');
        $this->assertSame([], $this->map(['a' => 1])->diff(['b' => 1])->all(), 'map key doesnt matter');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 1])->diff(['a' => 1])->all(), 'diff multiple items');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2])->diff(['a' => 1, 'b' => 1])->all(), 'same key but different value');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2])->diff(['a' => 2, 'b' => 3], fn() => 0)->all(), 'use by callback reject');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->diff(['a' => 2, 'b' => 3], fn() => 1)->all(), 'use by callback accept');
    }

    public function test_doesNotContain(): void
    {
        $this->assertTrue($this->vec()->doesNotContain(null), 'null in empty');
        $this->assertTrue($this->vec()->doesNotContain(''), 'blank in empty');
        $this->assertFalse($this->vec([''])->doesNotContain(''), 'contains blank');
        $this->assertTrue($this->vec([1, 2])->doesNotContain('2'), 'wrong type');
        $this->assertFalse($this->vec([1, 2])->doesNotContain(2), 'has int');
        $this->assertTrue($this->vec([1, 2])->doesNotContain(3), 'no match');
        $this->assertFalse($this->vec([1, 2])->doesNotContain(1), 'match');
        $this->assertTrue($this->vec([1])->doesNotContain($this->vec([1])), 'vec as arg');

        $this->assertTrue($this->map()->doesNotContain(null), 'null in empty');
        $this->assertTrue($this->map()->doesNotContain(''), 'blank in empty');
        $this->assertFalse($this->map(['a' => ''])->doesNotContain(''), 'contains blank');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->doesNotContain('2'), 'wrong type');
        $this->assertFalse($this->map(['a' => 1, 'b' => 2])->doesNotContain(2), 'has int');
        $this->assertTrue($this->map(['a' => 1])->doesNotContain($this->vec([1])), 'vec as arg');
    }

    public function test_dropFirst(): void
    {
        $this->assertSame([], $this->vec()->dropFirst(0)->all(), 'zero on empty');
        $this->assertSame([], $this->vec()->dropFirst(2)->all(), 'over limit on empty');
        $this->assertSame([2, 3], $this->vec([1, 2, 3])->dropFirst(1)->all(), 'drop 1');
        $this->assertSame([3], $this->vec([1, 2, 3])->dropFirst(2)->all(), 'drop 2');
        $this->assertSame([], $this->vec([1])->dropFirst(2)->all(), 'over limit');

        $this->assertSame([], $this->map()->dropFirst(0)->all(), 'zero on empty');
        $this->assertSame([], $this->map()->dropFirst(2)->all(), 'over limit on empty');
        $this->assertSame(['b' => 2, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropFirst(1)->all(), 'drop 1');
        $this->assertSame(['c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropFirst(2)->all(), 'drop 2');
        $this->assertSame([], $this->map(['a' => 1])->dropFirst(2)->all(), 'over limit');
    }

    public function test_dropFirst_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->dropFirst(-1)->all();
    }

    public function test_dropLast(): void
    {
        $this->assertSame([], $this->vec()->dropLast(0)->all(), 'zero on empty');
        $this->assertSame([], $this->vec()->dropLast(2)->all(), 'over limit on empty');
        $this->assertSame([1, 2], $this->vec([1, 2, 3])->dropLast(1)->all(), 'drop 1');
        $this->assertSame([1], $this->vec([1, 2, 3])->dropLast(2)->all(), 'drop 2');
        $this->assertSame([], $this->vec([1])->dropLast(2)->all(), 'over limit');

        $this->assertSame([], $this->map()->dropLast(0)->all(), 'zero on empty');
        $this->assertSame([], $this->map()->dropLast(2)->all(), 'over limit on empty');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropLast(1)->all(), 'drop 1');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropLast(2)->all(), 'drop 2');
        $this->assertSame([], $this->map(['a' => 1])->dropLast(2)->all(), 'over limit');
    }

    public function test_dropLast_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->dropLast(-1)->all();
    }

    public function test_dropUntil(): void
    {
        $this->assertSame([], $this->vec()->dropUntil(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->vec([1, 2, 3])->dropUntil(fn() => false)->all(), 'no match');
        $this->assertSame([2, 3], $this->vec([1, 2, 3])->dropUntil(fn($v) => $v > 1)->all(), 'match');
        $this->assertSame([], $this->vec([1, 2, 3])->dropUntil(fn($v) => $v > 3)->all(), 'no match');

        $this->assertSame([], $this->map()->dropUntil(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropUntil(fn() => false)->all(), 'no match');
        $this->assertSame(['b' => 2, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropUntil(fn($v) => $v > 1)->all(), 'match');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropUntil(fn($v) => $v > 3)->all(), 'no match');
    }

    public function test_dropWhile(): void
    {
        $this->assertSame([], $this->vec()->dropWhile(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->vec([1, 2])->dropWhile(fn() => true)->all(), 'no match');
        $this->assertSame([1, 2], $this->vec([1, 2])->dropWhile(fn() => false)->all(), 'no match');
        $this->assertSame([2, 3], $this->vec([1, 2, 3])->dropWhile(fn($v) => $v < 2)->all(), 'match');
        $this->assertSame([], $this->vec([1, 2, 3])->dropWhile(fn($v) => $v < 4)->all(), 'no match');

        $this->assertSame([], $this->map()->dropWhile(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropWhile(fn() => true)->all(), 'no match');
        $this->assertSame(['b' => 2, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropWhile(fn($v) => $v < 2)->all(), 'match');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropWhile(fn($v) => $v < 4)->all(), 'no match');
    }

    public function test_dump(): void
    {
        $resource = fopen('php://memory', 'r+') ?: throw new UnreachableException();
        $config = new Config(writer: new Writer($resource), decorator: 'plain');

        $original = $this->vec();
        $returned = $original->dump($config);

        fseek($resource, 0);
        $expected = fread($resource, 100) ?: '';
        $this->assertStringContainsString('items: []', $expected);
        $this->assertSame($original, $returned);
    }

    public function test_duplicates(): void
    {
        $this->assertSame([], $this->vec()->duplicates()->all());
        $this->assertSame([], $this->vec([1, 2, 3])->duplicates()->all());
        $this->assertSame([1], $this->vec([1, 1, 2, 3])->duplicates()->all());
        $this->assertSame([1, 2], $this->vec([1, 1, 1, 2, 2, 3])->duplicates()->all());

        $this->assertSame([], $this->map()->duplicates()->all());
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => 1])->duplicates()->all());
    }

    public function test_each(): void
    {
        $this->assertInstanceOf(Vec::class, $this->vec()->each(fn() => null));
        $this->assertSame([], $this->vec()->each(fn() => null)->all());
        $this->assertSame([1], $this->vec([1])->each(fn() => null)->all());
        $obj = new stdClass();
        $this->vec([1, 2])->each(fn($n, $k) => $obj->{"x{$k}"} = $n);
        $this->assertSame(['x0' => 1, 'x1' => 2], (array) $obj);

        $this->assertInstanceOf(Map::class, $this->map()->each(fn() => null));
        $this->assertSame([], $this->map()->each(fn() => null)->all());
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->each(fn() => null)->all());
        $obj = new stdClass();
        $this->map(['a' => 1, 'b' => 2])->each(fn($n, $k) => $obj->{"x{$k}"} = $n);
        $this->assertSame(['xa' => 1, 'xb' => 2], (array) $obj);
    }

    public function test_except(): void
    {
        $this->assertSame([], $this->vec()->except([])->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->except([])->all(), 'remove none');
        $this->assertSame([1], $this->vec([1, 2])->except([1])->all(), 'remove one');
        $this->assertSame([], $this->vec([1, 2])->except([0, 1])->all(), 'remove all');
        $this->assertSame(['a'], $this->vec(['a'])->except([1], false)->all(), 'remove missing unsafe');

        $this->assertSame([], $this->map()->except([])->all(), 'empty');
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->except([])->all(), 'remove none');
        $this->assertSame(['b' => 1], $this->map(['a' => 1, 'b' => 1])->except(['a'])->all(), 'remove one');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 1])->except(['a', 'b'])->all(), 'remove all');
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->except(['b'], false)->all(), 'remove missing unsafe');
    }

    public function test_expect_safe_vec(): void
    {
        $this->expectExceptionMessage('Keys: [0, 1] did not exist.');
        $this->expectException(MissingKeyException::class);
        $this->vec()->except([0, 1])->all();
    }

    public function test_expect_safe_map(): void
    {
        $this->expectExceptionMessage("Keys: ['a', 'b'] did not exist.");
        $this->expectException(MissingKeyException::class);
        $this->map()->except(['a', 'b'])->all();
    }

    public function test_filter(): void
    {
        $this->assertSame([], $this->vec()->filter(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->vec()->filter(fn() => false)->all(), 'empty');
        $this->assertSame([], $this->vec([1, 2])->filter(fn() => false)->all(), 'no match');
        $this->assertSame([1, 2], $this->vec([1, 2])->filter(fn() => true)->all(), 'match all');
        $this->assertSame([2], $this->vec([1, 2])->filter(fn($v) => $v > 1)->all(), 'match some');
        $this->assertSame([], $this->vec([1, 2])->filter(fn($v) => $v > 2)->all(), 'match none');

        $this->assertSame([], $this->map()->filter(fn() => true)->all(), 'empty');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2])->filter(fn() => false)->all(), 'no match');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->filter(fn() => true)->all(), 'match all');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2])->filter(fn($v) => $v > 1)->all(), 'match some');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2])->filter(fn($v) => $v > 2)->all(), 'match none');
    }

    public function test_first(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->first(), 'first');
        $this->assertSame(1, $this->vec([1, 2])->first(fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->first(fn($i) => $i > 1), 'match some');

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->first(), 'first');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->first(fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->first(fn($i) => $i > 1), 'match some');
    }

    public function test_first_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class) ;
        $this->vec()->first();
    }

    public function test_first_no_match(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec([1])->first(fn() => false);
    }

    public function test_firstIndex(): void
    {
        $this->assertSame(0, $this->vec([1, 2])->firstIndex(fn() => true), 'match all');
        $this->assertSame(1, $this->vec([1, 2, 3])->firstIndex(fn($i) => $i > 1), 'match some');

        $this->assertSame(0, $this->map(['a' => 1, 'b' => 2])->firstIndex(fn() => true), 'match all');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->firstIndex(fn($i) => $i > 1), 'match some');
    }

    public function test_firstIndex_on_empty(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec()->firstIndex(fn() => true);
    }

    public function test_firstIndex_no_match(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec([1, 2])->firstIndex(fn() => false);
    }

    public function test_firstIndexOrNull(): void
    {
        $this->assertNull($this->vec([])->firstIndexOrNull(fn() => true), 'empty');
        $this->assertNull($this->vec([1, 2])->firstIndexOrNull(fn() => false), 'match none');
        $this->assertSame(0, $this->vec([1, 2])->firstIndexOrNull(fn() => true), 'match all');
        $this->assertSame(1, $this->vec([1, 2, 3])->firstIndexOrNull(fn($i) => $i > 1), 'match some');

        $this->assertNull($this->map([])->firstIndexOrNull(fn() => true), 'empty');
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->firstIndexOrNull(fn() => false), 'match none');
        $this->assertSame(0, $this->map(['a' => 1, 'b' => 2])->firstIndexOrNull(fn() => true), 'match all');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->firstIndexOrNull(fn($i) => $i > 1), 'match some');
    }

    public function test_firstOr(): void
    {
        $default = '!';

        $this->assertSame($default, $this->vec()->firstOr($default), 'empty');
        $this->assertSame(1, $this->vec([1, 2])->firstOr($default), 'first');
        $this->assertSame($default, $this->vec([1, 2])->firstOr($default, fn() => false), 'match none');
        $this->assertSame(1, $this->vec([1, 2])->firstOr($default, fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->firstOr($default, fn($i) => $i > 1), 'match some');

        $this->assertSame($default, $this->map()->firstOr($default), 'empty');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->firstOr($default), 'first');
        $this->assertSame($default, $this->map(['a' => 1, 'b' => 2])->firstOr($default, fn() => false), 'match none');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->firstOr($default, fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->firstOr($default, fn($i) => $i > 1), 'match some');
    }

    public function test_firstOrNull(): void
    {
        $this->assertNull($this->vec()->firstOrNull(), 'empty');
        $this->assertSame(1, $this->vec([1, 2])->firstOrNull(), 'first');
        $this->assertNull($this->vec([1, 2])->firstOrNull(fn() => false), 'match none');
        $this->assertSame(1, $this->vec([1, 2])->firstOrNull(fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->firstOrNull(fn($i) => $i > 1), 'match some');

        $this->assertNull($this->map()->firstOrNull(), 'empty');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->firstOrNull(), 'first');
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->firstOrNull(fn() => false), 'match none');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->firstOrNull(fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->firstOrNull(fn($i) => $i > 1), 'match some');
    }
}
