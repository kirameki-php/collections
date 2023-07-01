<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\DuplicateKeyException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidElementException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\MissingKeyException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Dumper\Config;
use Kirameki\Dumper\Writer;
use Random\Engine\Mt19937;
use Random\Randomizer;
use stdClass;
use function fopen;
use function fread;
use function fseek;
use function is_string;
use function range;
use const INF;
use const NAN;
use const SORT_ASC;
use const SORT_DESC;
use const SORT_STRING;

final class EnumerableTest extends TestCase
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
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: 0.');
        $this->vec()->compact(0);
    }

    public function test_compact_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: -1.');
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
        $this->assertNull($this->vec()->firstIndexOrNull(fn() => true), 'empty');
        $this->assertNull($this->vec([1, 2])->firstIndexOrNull(fn() => false), 'match none');
        $this->assertSame(0, $this->vec([1, 2])->firstIndexOrNull(fn() => true), 'match all');
        $this->assertSame(1, $this->vec([1, 2, 3])->firstIndexOrNull(fn($i) => $i > 1), 'match some');

        $this->assertNull($this->map()->firstIndexOrNull(fn() => true), 'empty');
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

    public function test_fold(): void
    {
        $this->assertSame(0, $this->vec()->fold(0, fn($sum, $i) => $sum + $i), 'empty');
        $this->assertSame(10, $this->vec([1, 2, 3])->fold(4, fn($sum, $i) => $sum + $i), 'sum');
        $this->assertSame(['a1' => 1, 'a2' => 2], $this->vec([1, 2])->fold([], function($sum, $i) {
            $sum["a{$i}"] = $i;
            return $sum;
        }), 'sum');

        $this->assertSame(0, $this->map()->fold(0, fn($acc, $i) => $acc + $i), 'empty');
        $this->assertSame(6, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->fold(0, fn($acc, $i) => $acc + $i), 'sum');
        $this->assertSame(['c' => 3, 'xa' => 1, 'xb' => 2], $this->map(['a' => 1, 'b' => 2])->fold(['c' => 3], function($sum, $i, $k) {
            $sum["x{$k}"] = $i;
            return $sum;
        }), 'sum');
    }

    public function test_groupBy(): void
    {
        $this->assertSame([], $this->vec()->groupBy(fn($i) => $i)->all(), 'empty');
        $this->assertSame([[0, 2], [1, 3]], $this->vec(range(0, 3))->groupBy(fn($i) => $i % 2)->toArray(), 'odd/even');

        $this->assertSame([], $this->map()->groupBy(fn($i) => $i)->all(), 'empty');
        $this->assertSame([1 => ['a' => 1, 'c' => 3], 0 => ['b' => 2]], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->groupBy(fn($i) => $i % 2)->toArray(), 'odd/even');
    }

    public function test_instantiate(): void
    {
        $emptyVec = $this->vec();
        $this->assertNotSame($emptyVec, $emptyVec->instantiate([]), 'different instance from original');
        $this->assertInstanceOf(Vec::class, $emptyVec->instantiate([]), 'same instance');
        $this->assertSame([], $emptyVec->instantiate([])->all(), 'empty vec and arg');
        $this->assertSame([], $this->vec([1])->instantiate([])->all(), 'empty arg');
        $this->assertSame([2], $emptyVec->instantiate([2])->all(), 'with arg');
        $this->assertSame([2], $this->vec([1])->instantiate([2])->all(), 'with arg override');

        $emptyMap = $this->map();
        $this->assertNotSame($emptyMap, $emptyMap->instantiate([]), 'different instance from original');
        $this->assertInstanceOf(Map::class, $emptyMap->instantiate([]), 'same instance');
        $this->assertSame([], $emptyMap->instantiate([])->all(), 'empty map and arg');
        $this->assertSame([], $this->map(['a' => 1])->instantiate([])->all(), 'empty arg');
        $this->assertSame(['b' => 2], $emptyMap->instantiate(['b' => 2])->all(), 'with arg');
        $this->assertSame(['b' => 2], $this->map(['a' => 1])->instantiate(['b' => 2])->all(), 'with arg override');
    }

    public function test_intersect(): void
    {
        $this->assertSame([], $this->vec()->intersect([])->all(), 'empty');
        $this->assertSame([], $this->vec([1, 2])->intersect([])->all(), 'empty arg');
        $this->assertSame([], $this->vec()->intersect([1, 2])->all(), 'empty vec');
        $this->assertSame([2], $this->vec([1, 2])->intersect([2, 3])->all(), 'intersect');
        $this->assertSame([1, 1], $this->vec([1, 1])->intersect([1])->all(), 'intersect multi on left');
        $this->assertSame([1], $this->vec([1])->intersect([1, 1, 1])->all(), 'intersect multi on right');

        $this->assertSame([], $this->map()->intersect([])->all(), 'empty');
        $this->assertSame([], $this->map(['a' => 1, 'b' => 2])->intersect([])->all(), 'empty arg');
        $this->assertSame([], $this->map()->intersect(['a' => 1, 'b' => 2])->all(), 'empty map');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2])->intersect(['b' => 2, 'c' => 3])->all(), 'intersect');
        $this->assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2])->intersect(['y' => 2, 'z' => 3])->all(), 'intersect takes left');
    }

    public function test_intersect_map_with_list(): void
    {
        $this->expectExceptionMessage("\$iterable1's inner type (map) does not match \$iterable2's (list).");
        $this->expectException(TypeMismatchException::class);
        $this->map(['a' => 1])->intersect([1]);
    }

    public function test_intersect_list_with_map(): void
    {
        $this->expectExceptionMessage("\$iterable1's inner type (list) does not match \$iterable2's (map).");
        $this->expectException(TypeMismatchException::class);
        $this->vec([1])->intersect(['a' => 1]);
    }

    public function test_isEmpty(): void
    {
        $this->assertTrue($this->vec()->isEmpty(), 'empty');
        $this->assertFalse($this->vec([1])->isEmpty(), 'not empty');
        $this->assertTrue($this->map()->isEmpty(), 'empty');
        $this->assertFalse($this->map(['a' => 1])->isEmpty(), 'not empty');
    }

    public function test_isNotEmpty(): void
    {
        $this->assertFalse($this->vec()->isNotEmpty(), 'empty');
        $this->assertTrue($this->vec([1])->isNotEmpty(), 'not empty');
        $this->assertFalse($this->map()->isNotEmpty(), 'empty');
        $this->assertTrue($this->map(['a' => 1])->isNotEmpty(), 'not empty');
    }

    public function test_join(): void
    {
        $this->assertSame('', $this->vec()->join('|'), 'empty');
        $this->assertSame('1', $this->vec([1])->join('|'), 'single');
        $this->assertSame('1|2|3', $this->vec([1, 2, 3])->join('|'), 'join');
        $this->assertSame('<1', $this->vec([1])->join('|', '<'), 'single with prefix');
        $this->assertSame('<1|2|3', $this->vec([1, 2, 3])->join('|', '<'), 'multi with prefix');
        $this->assertSame('<>', $this->vec()->join('|', '<', '>'), 'empty with *fix');
        $this->assertSame('<1>', $this->vec([1])->join('|', '<', '>'), 'single with *fix');
        $this->assertSame('<1|2|3>', $this->vec([1, 2, 3])->join('|', '<', '>'), 'multi with *fix');

        $this->assertSame('', $this->map()->join('|'), 'empty');
        $this->assertSame('1', $this->map(['a' => 1])->join('|'), 'single');
        $this->assertSame('1|2|3', $this->map(['a' => 1, 'b' => 2, 'c' => 3])->join('|'), 'join');
        $this->assertSame('<1', $this->map(['a' => 1])->join('|', '<'), 'single with prefix');
        $this->assertSame('<1|2|3', $this->map(['a' => 1, 'b' => 2, 'c' => 3])->join('|', '<'), 'multi with prefix');
        $this->assertSame('<>', $this->map()->join('|', '<', '>'), 'empty with *fix');
        $this->assertSame('<1>', $this->map(['a' => 1])->join('|', '<', '>'), 'single with *fix');
        $this->assertSame('<1|2|3>', $this->map(['a' => 1, 'b' => 2, 'c' => 3])->join('|', '<', '>'), 'multi with *fix');
    }

    public function test_keyBy(): void
    {
        $this->assertSame([], $this->vec()->keyBy(fn() => null)->all(), 'empty');
        $this->assertSame([2 => 1], $this->vec([1])->keyBy(fn(int $i) => $i * 2)->all(), 'multiply');
        $this->assertSame(['_1' => 1, '_2' => 2], $this->vec([1, 2])->keyBy(fn(int $i) => "_{$i}")->all(), 'string');
        $this->assertSame([1 => 1], $this->vec([1, 1])->keyBy(fn(int $i) => "{$i}", true)->all(), 'overwrite');

        $this->assertSame([], $this->map()->keyBy(fn() => null)->all(), 'empty');
        $this->assertSame([2 => 1], $this->map(['a' => 1])->keyBy(fn(int $i) => $i * 2)->all(), 'multiply');
        $this->assertSame(['_1' => 1], $this->map(['a' => 1])->keyBy(fn(int $i) => "_{$i}")->all(), 'string');
        $this->assertSame(['_a' => 1], $this->map(['a' => 1])->keyBy(fn(int $v, string $k) => "_{$k}")->all(), 'string');
        $this->assertSame(['1' => 1], $this->map([1 => 1, 2 => 1])->keyBy(fn(int $i) => "{$i}", true)->all(), 'overwrite');
    }

    public function test_keyBy_wrong_key_type(): void
    {
        $this->expectExceptionMessage('Expected: key of type int|string. NULL given.');
        $this->expectException(InvalidKeyException::class);
        $this->vec([1])->keyBy(fn() => null)->all();
    }

    public function test_keyBy_duplicate_keys(): void
    {
        $this->expectExceptionMessage('Tried to overwrite existing key: 1.');
        $this->expectException(DuplicateKeyException::class);
        $this->vec([1, 1])->keyBy(fn(int $i) => "{$i}")->all();
    }

    public function test_keys(): void
    {
        $this->assertInstanceOf(Vec::class, $this->vec()->keys(), 'instance');
        $this->assertSame([], $this->vec()->keys()->all(), 'empty');
        $this->assertSame([0, 1], $this->vec([1, 2])->keys()->all(), 'keys');

        $this->assertInstanceOf(Vec::class, $this->map()->keys(), 'instance');
        $this->assertSame([], $this->map()->keys()->all(), 'empty');
        $this->assertSame(['a', 'b'], $this->map(['a' => 1, 'b' => 2])->keys()->all(), 'keys');
    }

    public function test_last(): void
    {
        $this->assertSame(2, $this->vec([1, 2])->last(), 'last');
        $this->assertSame(2, $this->vec([1, 2])->last(fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->last(fn($i) => $i < 3), 'match some');

        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->last(), 'last');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->last(fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->last(fn($i) => $i < 3), 'match some');
    }

    public function test_last_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class) ;
        $this->vec()->last();
    }

    public function test_last_no_match(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec([1])->last(fn() => false);
    }

    public function test_lastIndex(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->lastIndex(fn() => true), 'match all');
        $this->assertSame(1, $this->vec([1, 2, 3])->lastIndex(fn($i) => $i < 3), 'match some');

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->lastIndex(fn() => true), 'match all');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->lastIndex(fn($i) => $i < 3), 'match some');
    }

    public function test_lastIndex_on_empty(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec()->lastIndex(fn() => true);
    }

    public function test_lastIndex_no_match(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class) ;
        $this->vec([1, 2])->lastIndex(fn() => false);
    }

    public function test_lastIndexOrNull(): void
    {
        $this->assertNull($this->vec()->lastIndexOrNull(fn() => true), 'empty');
        $this->assertNull($this->vec([1, 2])->lastIndexOrNull(fn() => false), 'match none');
        $this->assertSame(1, $this->vec([1, 2])->lastIndexOrNull(fn() => true), 'match all');
        $this->assertSame(1, $this->vec([1, 2, 3])->lastIndexOrNull(fn($i) => $i < 3), 'match some');

        $this->assertNull($this->map()->lastIndexOrNull(fn() => true), 'empty');
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->lastIndexOrNull(fn() => false), 'match none');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->lastIndexOrNull(fn() => true), 'match all');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->lastIndexOrNull(fn($i) => $i < 3), 'match some');
    }

    public function test_lastOr(): void
    {
        $default = '!';

        $this->assertSame($default, $this->vec()->lastOr($default), 'empty');
        $this->assertSame(2, $this->vec([1, 2])->lastOr($default), 'last');
        $this->assertSame($default, $this->vec([1, 2])->lastOr($default, fn() => false), 'match none');
        $this->assertSame(2, $this->vec([1, 2])->lastOr($default, fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->lastOr($default, fn($i) => $i < 3), 'match some');

        $this->assertSame($default, $this->map()->lastOr($default), 'empty');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->lastOr($default), 'last');
        $this->assertSame($default, $this->map(['a' => 1, 'b' => 2])->lastOr($default, fn() => false), 'match none');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->lastOr($default, fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->lastOr($default, fn($i) => $i < 3), 'match some');
    }

    public function test_lastOrNull(): void
    {
        $this->assertNull($this->vec()->lastOrNull(), 'empty');
        $this->assertSame(2, $this->vec([1, 2])->lastOrNull(), 'last');
        $this->assertNull($this->vec([1, 2])->lastOrNull(fn() => false), 'match none');
        $this->assertSame(2, $this->vec([1, 2])->lastOrNull(fn() => true), 'match all');
        $this->assertSame(2, $this->vec([1, 2, 3])->lastOrNull(fn($i) => $i < 3), 'match some');

        $this->assertNull($this->map()->lastOrNull(), 'empty');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->lastOrNull(), 'last');
        $this->assertNull($this->map(['a' => 1, 'b' => 2])->lastOrNull(fn() => false), 'match none');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->lastOrNull(fn() => true), 'match all');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->lastOrNull(fn($i) => $i < 3), 'match some');
    }

    public function test_map(): void
    {
        $this->assertSame([], $this->vec()->map(fn() => 1)->toArray(), 'empty');
        $this->assertSame([2, 3], $this->vec([1, 2])->map(fn($i) => $i + 1)->toArray(), 'non-empty');

        $this->assertSame([], $this->map()->map(fn() => 1)->toArray(), 'empty');
        $this->assertSame(['a' => 2, 'b' => 3], $this->map(['a' => 1, 'b' => 2])->map(fn($i) => $i + 1)->toArray(), 'non-empty');
    }

    public function test_max(): void
    {
        $this->assertSame(2, $this->vec([1, 2])->max(), 'basic use');
        $this->assertSame(1, $this->vec([1, -2])->max(), 'contains negative');
        $this->assertSame(0.2, $this->vec([0.2, 0.1])->max(), 'floats');
        $this->assertSame(INF, $this->vec([-INF, INF])->max(), 'INF');
        $this->assertSame(-3, $this->vec([2, -3])->max(fn($i) => -$i), 'with condition');
        $this->assertSame(1, $this->vec([1, 2])->max(fn($i) => 1), 'all same');

        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->max(), 'basic use');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => -2])->max(), 'contains negative');
        $this->assertSame(-3, $this->map(['a' => 2, 'b' => -3])->max(fn($i) => -$i), 'with condition');
    }

    public function test_max_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class) ;
        $this->vec()->max();
    }

    public function test_max_contains_nan(): void
    {
        $this->expectExceptionMessage('$iterable cannot contain NAN.');
        $this->expectException(InvalidElementException::class);
        $this->vec([1, NAN, 1])->max();
    }

    public function test_maxOrNull(): void
    {
        $this->assertNull($this->vec()->maxOrNull(), 'basic use');
        $this->assertSame(2, $this->vec([1, 2])->maxOrNull(), 'basic use');
        $this->assertSame(1, $this->vec([1, -2])->maxOrNull(), 'contains negative');
        $this->assertSame(0.2, $this->vec([0.2, 0.1])->maxOrNull(), 'floats');
        $this->assertSame(INF, $this->vec([-INF, INF])->maxOrNull(), 'INF');
        $this->assertSame(-3, $this->vec([2, -3])->maxOrNull(fn($i) => -$i), 'with condition');
        $this->assertSame(1, $this->vec([1, 2])->maxOrNull(fn($i) => 1), 'all same');

        $this->assertNull($this->map()->maxOrNull(), 'basic use');
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->maxOrNull(), 'empty');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => -2])->maxOrNull(), 'contains negative');
        $this->assertSame(-3, $this->map(['a' => 2, 'b' => -3])->maxOrNull(fn($i) => -$i), 'with condition');
    }

    public function test_maxOrNull_contains_nan(): void
    {
        $this->expectExceptionMessage('$iterable cannot contain NAN.');
        $this->expectException(InvalidElementException::class);
        $this->vec([1, NAN, 1])->maxOrNull();
    }

    public function test_min(): void
    {
        $this->assertSame(1, $this->vec([1, 2])->min(), 'basic use');
        $this->assertSame(-2, $this->vec([1, -2])->min(), 'contains negative');
        $this->assertSame(0.1, $this->vec([0.2, 0.1])->min(), 'floats');
        $this->assertSame(-INF, $this->vec([-INF, INF])->min(), 'INF');
        $this->assertSame(2, $this->vec([2, -3])->min(fn($i) => -$i), 'with condition');
        $this->assertSame(1, $this->vec([1, 2])->min(fn($i) => 1), 'all same');

        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->min(), 'basic use');
        $this->assertSame(-2, $this->map(['a' => 1, 'b' => -2])->min(), 'contains negative');
        $this->assertSame(2, $this->map(['a' => 2, 'b' => -3])->min(fn($i) => -$i), 'with condition');
    }

    public function test_min_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->min();
    }

    public function test_min_contains_nan(): void
    {
        $this->expectExceptionMessage('$iterable cannot contain NAN.');
        $this->expectException(InvalidElementException::class);
        $this->vec([1, NAN, 1])->min();
    }

    public function test_minOrNull(): void
    {
        $this->assertNull($this->vec()->minOrNull(), 'basic use');
        $this->assertSame(1, $this->vec([1, 2])->minOrNull(), 'basic use');
        $this->assertSame(-2, $this->vec([1, -2])->minOrNull(), 'contains negative');
        $this->assertSame(0.1, $this->vec([0.2, 0.1])->minOrNull(), 'floats');
        $this->assertSame(-INF, $this->vec([-INF, INF])->minOrNull(), 'INF');
        $this->assertSame(2, $this->vec([2, -3])->minOrNull(fn($i) => -$i), 'with condition');
        $this->assertSame(1, $this->vec([1, 2])->minOrNull(fn($i) => 1), 'all same');

        $this->assertNull($this->map()->minOrNull(), 'basic use');
        $this->assertSame(1, $this->map(['a' => 1, 'b' => 2])->minOrNull(), 'empty');
        $this->assertSame(-2, $this->map(['a' => 1, 'b' => -2])->minOrNull(), 'contains negative');
        $this->assertSame(2, $this->map(['a' => 2, 'b' => -3])->minOrNull(fn($i) => -$i), 'with condition');
    }

    public function test_minOrNull_contains_nan(): void
    {
        $this->expectExceptionMessage('$iterable cannot contain NAN.');
        $this->expectException(InvalidElementException::class) ;
        $this->vec([1, NAN, 1])->minOrNull();
    }

    public function test_minMax(): void
    {
        $this->assertSame(['min' => 1, 'max' => 1], $this->vec([1])->minMax(), 'only one value');
        $this->assertSame(['min' => 1, 'max' => 2], $this->vec([1, 2])->minMax(), 'basic use');
        $this->assertSame(['min' => -2, 'max' => 1], $this->vec([1, -2])->minMax(), 'contains negative');
        $this->assertSame(['min' => 0.1, 'max' => 0.2], $this->vec([0.2, 0.1])->minMax(), 'floats');
        $this->assertSame(['min' => -INF, 'max' => INF], $this->vec([-INF, INF])->minMax(), 'INF');
        $this->assertSame(['min' => 2, 'max' => -3], $this->vec([2, -3])->minMax(fn($i) => -$i), 'with condition');
        $this->assertSame(['min' => 1, 'max' => 1], $this->vec([1, 2])->minMax(fn($i) => 1), 'all same');

        $this->assertSame(['min' => 1, 'max' => 1], $this->map(['a' => 1])->minMax(), 'only one value');
        $this->assertSame(['min' => 1, 'max' => 2], $this->map(['a' => 1, 'b' => 2])->minMax(), 'basic use');
        $this->assertSame(['min' => -2, 'max' => 1], $this->map(['a' => 1, 'b' => -2])->minMax(), 'contains negative');
        $this->assertSame(['min' => 2, 'max' => -3], $this->map(['a' => 2, 'b' => -3])->minMax(fn($i) => -$i), 'with condition');
    }

    public function test_minMax_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->minMax();
    }

    public function test_partition(): void
    {
        $split = $this->vec()->partition(fn($i) => $i > 0);
        $this->assertSame([], $split[0]->all(), 'empty');
        $this->assertSame([], $split[1]->all(), 'empty');

        $split = $this->vec([0, 1, 2])->partition(fn($i) => $i > 0);
        $this->assertSame([1, 2], $split[0]->all(), 'split');
        $this->assertSame([0], $split[1]->all(), 'split');

        $split = $this->vec([0, 1, 2])->partition(fn($i) => $i >= 0);
        $this->assertSame([0, 1, 2], $split[0]->all(), 'no false');
        $this->assertSame([], $split[1]->all(), 'no false');

        $split = $this->vec([0, 1, 2])->partition(fn($i) => $i < 0);
        $this->assertSame([], $split[0]->all(), 'no true');
        $this->assertSame([0, 1, 2], $split[1]->all(), 'no true');

        $split = $this->map()->partition(fn($i) => $i > 0);
        $this->assertSame([], $split[0]->all(), 'empty');
        $this->assertSame([], $split[1]->all(), 'empty');

        $split = $this->map(['a' => 0, 'b' => 1])->partition(fn($i) => $i > 0);
        $this->assertSame(['b' => 1], $split[0]->all(), 'split');
        $this->assertSame(['a' => 0], $split[1]->all(), 'split');

        $split = $this->map(['a' => 0, 'b' => 1])->partition(fn($i) => $i >= 0);
        $this->assertSame(['a' => 0, 'b' => 1], $split[0]->all(), 'no false');
        $this->assertSame([], $split[1]->all(), 'no false');

        $split = $this->map(['a' => 0, 'b' => 1])->partition(fn($i) => $i < 0);
        $this->assertSame([], $split[0]->all(), 'no true');
        $this->assertSame(['a' => 0, 'b' => 1], $split[1]->all(), 'no true');
    }

    public function test_pipe(): void
    {
        $this->assertSame([1, 1], $this->vec([1])->pipe(fn(Vec $v) => $v->append(1))->all());
        $this->assertSame(2, $this->vec([1, 2])->pipe(fn($i) => $i->last()));
        $this->assertSame(0, $this->vec([1, 2])->pipe(fn($i) => 0));

        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1])->pipe(fn(Map $v) => $v->set('b', 2))->all());
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->pipe(fn($i) => $i->last()));
        $this->assertSame(0, $this->map(['a' => 1, 'b' => 2])->pipe(fn($i) => 0));
    }

    public  function test_prioritize(): void
    {
        $this->assertSame([], $this->vec()->prioritize(fn($i) => $i > 1)->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->prioritize(fn($i) => $i > 1)->all(), 'one');
        $this->assertSame([2, 3, 0, 1], $this->vec([0, 1, 2, 3])->prioritize(fn($i) => $i > 1)->all(), 'different priority');
        $this->assertSame([1, 1, 0, 2], $this->vec([0, 1, 1, 2])->prioritize(fn($i) => $i === 1)->all(), 'same priority');
        $this->assertSame([1, 0, 1, 2], $this->vec([0, 1, 1, 2])->prioritize(fn($i) => $i === 1, 1)->all(), 'one');

        $this->assertSame([], $this->map()->prioritize(fn($i) => $i > 1)->all(), 'empty');
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->prioritize(fn($i) => $i > 1)->all(), 'one');
        $this->assertSame(['c' => 2, 'a' => 0, 'b' => 1], $this->map(['a' => 0, 'b' => 1, 'c' => 2])->prioritize(fn($i) => $i > 1)->all(), 'different priority');
        $this->assertSame(['b' => 1, 'c' => 2, 'a' => 0], $this->map(['a' => 0, 'b' => 1, 'c' => 2])->prioritize(fn($i) => $i > 0)->all(), 'one');
        $this->assertSame(['b' => 1, 'a' => 0, 'c' => 2], $this->map(['a' => 0, 'b' => 1, 'c' => 2])->prioritize(fn($i) => $i > 0, 1)->all(), 'one');
    }

    public function test_reduce(): void
    {
        $this->assertSame(1, $this->vec([1])->reduce(fn($carry, $i) => $carry + $i), 'one');
        $count = 0;
        $this->assertSame(3, $this->vec([1, 2])->reduce(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }), 'multiple');
        $this->assertSame(1, $count, 'multiple count');

        $this->assertSame(1, $this->map(['a' => 1])->reduce(fn($carry, $i) => $carry + $i), 'one');
        $this->assertSame('bc', $this->map(['a' => 'b', 'c' => 'd'])->reduce(fn($carry, $i, $k) => "{$carry}{$k}"), 'with key');
        $count = 0;
        $this->assertSame(3, $this->map(['a' => 1, 'b' => 2])->reduce(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }), 'multiple');
        $this->assertSame(1, $count, 'multiple count');
    }

    public function test_reduce_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->reduce(fn($carry, $i) => $carry + $i);
    }

    public function test_reduceOr(): void
    {
        $this->assertSame(INF, $this->vec()->reduceOr(fn($carry, $i) => $carry + $i, INF), 'empty');
        $this->assertSame(1, $this->vec([1])->reduceOr(fn($carry, $i) => $carry + $i, INF), 'one');
        $count = 0;
        $this->assertSame(3, $this->vec([1, 2])->reduceOr(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }, INF), 'multiple');
        $this->assertSame(1, $count, 'multiple count');

        $this->assertSame(INF, $this->map()->reduceOr(fn($carry, $i) => $carry + $i, INF), 'empty');
        $this->assertSame(1, $this->map(['a' => 1])->reduceOr(fn($carry, $i) => $carry + $i, INF), 'one');
        $this->assertSame('bc', $this->map(['a' => 'b', 'c' => 'd'])->reduceOr(fn($carry, $i, $k) => "{$carry}{$k}", INF), 'with key');
        $count = 0;
        $this->assertSame(3, $this->map(['a' => 1, 'b' => 2])->reduceOr(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }, INF), 'multiple');
        $this->assertSame(1, $count, 'multiple count');
    }

    public function test_reduceOrNull(): void
    {
        $this->assertNull($this->vec()->reduceOrNull(fn($carry, $i) => $carry + $i), 'empty');
        $this->assertSame(1, $this->vec([1])->reduceOrNull(fn($carry, $i) => $carry + $i), 'one');
        $count = 0;
        $this->assertSame(3, $this->vec([1, 2])->reduceOrNull(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }), 'multiple');
        $this->assertSame(1, $count, 'multiple count');

        $this->assertNull($this->map()->reduceOrNull(fn($carry, $i) => $carry + $i), 'empty');
        $this->assertSame(1, $this->map(['a' => 1])->reduceOrNull(fn($carry, $i) => $carry + $i), 'one');
        $this->assertSame('bc', $this->map(['a' => 'b', 'c' => 'd'])->reduceOrNull(fn($carry, $i, $k) => "{$carry}{$k}"), 'with key');
        $count = 0;
        $this->assertSame(3, $this->map(['a' => 1, 'b' => 2])->reduceOrNull(function($carry, $i) use (&$count) {
            $count++;
            return $carry + $i;
        }), 'multiple');
        $this->assertSame(1, $count, 'multiple count');
    }

    public function test_replace(): void
    {
        $this->assertSame([], $this->vec()->replace(1, 3)->all(), 'empty');
        $this->assertSame([3, 2], $this->vec([1, 2])->replace(1, 3)->all(), 'one');
        $this->assertSame([3, 2, 1], $this->vec([1, 2, 1])->replace(1, 3, 1)->all(), 'with limit');
        $count = 0;
        $this->assertSame([1, 2, 1], $this->vec([1, 2, 1])->replace(0, 3, 1, $count)->all(), 'no match');
        $this->assertSame(0, $count);
        $this->assertSame([3, 2, 1], $this->vec([1, 2, 1])->replace(1, 3, 1, $count)->all(), 'with count and limit (hit limit)');
        $this->assertSame(1, $count);
        $this->assertSame([3, 2, 3], $this->vec([1, 2, 1])->replace(1, 3, 10, $count)->all(), 'with count and limit (not hit limit)');
        $this->assertSame(2, $count);

        $this->assertSame([], $this->map()->replace(1, 3)->all(), 'empty');
        $this->assertSame(['a' => 3, 'b' => 2], $this->map(['a' => 1, 'b' => 2])->replace(1, 3)->all(), 'one');
        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 1], $this->map(['a' => 1, 'b' => 2, 'c' => 1])->replace(1, 3, 1)->all(), 'with limit');
        $count = 0;
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 1], $this->map(['a' => 1, 'b' => 2, 'c' => 1])->replace(0, 3, 1, $count)->all(), 'no match');
        $this->assertSame(0, $count);
        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 1], $this->map(['a' => 1, 'b' => 2, 'c' => 1])->replace(1, 3, 1, $count)->all(), 'with count and limit (hit limit)');
        $this->assertSame(1, $count);
        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 1])->replace(1, 3, 10, $count)->all(), 'with count and limit (not hit limit)');
        $this->assertSame(2, $count);

    }

    public function test_replace_negative_limit(): void
    {
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1, 2, 1])->replace(1, 0, -1);
    }

    public function test_reverse(): void
    {
        $this->assertSame([], $this->vec()->reverse()->all(), 'empty');
        $this->assertSame([2, 1], $this->vec([1, 2])->reverse()->all(), 'multiple');

        $this->assertSame([], $this->map()->reverse()->all(), 'empty');
        $this->assertSame(['b' => 2, 'a' => 1], $this->map(['a' => 1, 'b' => 2])->reverse()->all(), 'multiple');
    }

    public function test_rotate(): void
    {
        $this->assertSame([], $this->vec()->rotate(1)->all(), 'empty');
        $this->assertSame([], $this->vec()->rotate(-1)->all(), 'empty');
        $this->assertSame([2, 3, 1], $this->vec([1, 2, 3])->rotate(1)->all(), 'empty');
        $this->assertSame([3, 1, 2], $this->vec([1, 2, 3])->rotate(-1)->all(), 'empty');

        $this->assertSame([], $this->map()->rotate(1)->all(), 'empty');
        $this->assertSame([], $this->map()->rotate(-1)->all(), 'empty');
        $this->assertSame(['b' => 2, 'c' => 3, 'a' => 1], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->rotate(1)->all(), 'empty');
        $this->assertSame(['c' => 3, 'a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-1)->all(), 'empty');
    }

    public function test_sample(): void
    {
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(1, $this->vec([1])->sample(), 'one');
        $this->assertSame(2, $this->vec([1, 2])->sample($randomizer), 'many');
        $this->assertSame(1, $this->map(['a' => 1])->sampleOr('a', $randomizer), 'one');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2])->sample($randomizer), 'one');
    }

    public function test_sample_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->sample()->all();
    }

    public function test_sampleMany(): void
    {
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame([1, 3], $this->vec([1, 2, 3])->sampleMany(2, false, $randomizer)->all(), 'vec: no replace');
        $randomizer = new Randomizer(new Mt19937(5));
        $this->assertSame([2, 1], $this->vec([1, 2, 3])->sampleMany(2, false, $randomizer)->all(), 'vec: no replace (out of order)');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame([2, 3, 1, 3], $this->vec([1, 2, 3])->sampleMany(4, true, $randomizer)->all(), 'vec: amount > size w/replace (out of order)');
        $randomizer = new Randomizer(new Mt19937(2));
        $this->assertSame([1, 1, 3], $this->vec([1, 2, 3])->sampleMany(3, true, $randomizer)->all(), 'vec: exact size w/replace');

        $randomizer = new Randomizer(new Mt19937(5));
        $this->assertSame([2, 1], $this->map(['a' => 1, 'b' => 2])->sampleMany(2, true, $randomizer)->all(), 'map: exact size w/replace (out of order)');
        $randomizer = new Randomizer(new Mt19937(2));
        $this->assertSame([1, 2, 2], $this->map(['a' => 1, 'b' => 2])->sampleMany(3, true, $randomizer)->all(), 'map: amount > size w/replace');
    }

    public function test_sampleMany_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->sampleMany(1)->all();
    }

    public function test_sampleMany_amount_gt_size_no_replace(): void
    {
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->sampleMany(2)->all();
    }

    public function test_sampleOr(): void
    {
        $this->assertSame('a', $this->vec()->sampleOr('a'), 'empty');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(1, $this->vec([1])->sampleOr('a'), 'one');
        $this->assertSame(2, $this->vec([1, 2])->sampleOr('a', $randomizer), 'many');
        $this->assertSame(1, $this->map(['a' => 1])->sampleOr('a', $randomizer), 'one');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->sampleOr('a', $randomizer), 'many');
    }

    public function test_sampleOrNull(): void
    {
        $this->assertNull($this->vec()->sampleOrNull(), 'empty');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(1, $this->vec([1])->sampleOrNull(), 'one');
        $this->assertSame(2, $this->vec([1, 2])->sampleOrNull($randomizer), 'many');
        $this->assertSame(1, $this->map(['a' => 1])->sampleOrNull($randomizer), 'one');
        $randomizer = new Randomizer(new Mt19937(1));
        $this->assertSame(2, $this->map(['a' => 1, 'b' => 2, 'c' => 3])->sampleOrNull($randomizer), 'many');
    }

    public function test_satisfyAll(): void
    {
        $this->assertTrue($this->vec()->satisfyAll(fn() => true), 'empty');
        $this->assertFalse($this->vec([1, 2])->satisfyAll(fn() => false), 'all false');
        $this->assertTrue($this->vec([1, 2])->satisfyAll(fn() => true), 'all true');
        $this->assertFalse($this->vec([1, 2])->satisfyAll(fn($i) => $i > 1), 'one true one false');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->satisfyAll(fn($i, $k) => is_string($k)), 'all true');
    }

    public function test_satisfyAny(): void
    {
        $this->assertFalse($this->vec()->satisfyAny(fn() => true), 'empty');
        $this->assertFalse($this->vec([1, 2])->satisfyAny(fn() => false), 'all false');
        $this->assertTrue($this->vec([1, 2])->satisfyAny(fn() => true), 'all true');
        $this->assertTrue($this->vec([1, 2])->satisfyAny(fn($i) => $i > 1), 'one true one false');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->satisfyAny(fn($i, $k) => is_string($k)), 'all true');
    }

    public function test_satisfyNone(): void
    {
        $this->assertTrue($this->vec()->satisfyNone(fn() => true), 'empty');
        $this->assertTrue($this->vec([1, 2])->satisfyNone(fn() => false), 'all false');
        $this->assertFalse($this->vec([1, 2])->satisfyNone(fn() => true), 'all true');
        $this->assertFalse($this->vec([1, 2])->satisfyNone(fn($i) => $i > 1), 'one true one false');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->satisfyNone(fn($i, $k) => !is_string($k)), 'all true');
    }

    public function test_satisfyOnce(): void
    {
        $this->assertFalse($this->vec()->satisfyOnce(fn() => true), 'empty');
        $this->assertFalse($this->vec([1, 2])->satisfyOnce(fn() => false), 'all false');
        $this->assertFalse($this->vec([1, 2])->satisfyOnce(fn() => true), 'all true');
        $this->assertTrue($this->vec([1, 2])->satisfyOnce(fn($i) => $i > 1), 'one true one false');
        $this->assertTrue($this->map(['a' => 1, 'b' => 2])->satisfyOnce(fn($i) => $i > 1), 'map: one true');
    }

    public function test_shuffle(): void
    {
        $this->assertSame([], $this->vec()->shuffle()->all(), 'empty');
        $this->assertSame([2], $this->vec([2])->shuffle()->all(), 'one');
        $randomizer = new Randomizer(new Mt19937(2));
        $this->assertSame([2, 1], $this->vec([1, 2])->shuffle($randomizer)->all(), 'one');
        $randomizer = new Randomizer(new Mt19937(2));
        $this->assertSame(['b' => 2, 'a' => 1], $this->map(['a' => 1, 'b' => 2])->shuffle($randomizer)->all(), 'map');
    }

    public function test_slice(): void
    {
        $this->assertSame([], $this->vec()->slice(0)->all(), 'empty');
        $this->assertSame([], $this->vec()->slice(0, 1)->all(), 'empty with length');
        $this->assertSame([], $this->vec()->slice(0, -1)->all(), 'empty with negative length');
        $this->assertSame([1], $this->vec([1])->slice(0)->all(), 'same');
        $this->assertSame([1], $this->vec([1])->slice(0, 1)->all(), 'length == size');
        $this->assertSame([1], $this->vec([1])->slice(0, 3)->all(), 'length > size');
        $this->assertSame([], $this->vec([1])->slice(1)->all(), 'offset == size');
        $this->assertSame([], $this->vec([1])->slice(2)->all(), 'offset > size');
        $this->assertSame([2], $this->vec([1, 2])->slice(1)->all(), 'positive offset');
        $this->assertSame([2], $this->vec([1, 2])->slice(-1)->all(), 'negative offset');
        $this->assertSame([2], $this->vec([1, 2])->slice(1)->all(), 'positive offset');
        $this->assertSame([2], $this->vec([1, 2, 3])->slice(-2, 1)->all(), 'negative offset with length');
        $this->assertSame([2, 3], $this->vec([1, 2, 3, 4])->slice(-3, -2)->all(), 'negative length');
    }

    public function test_sole(): void
    {
        $this->assertSame(2, $this->vec([2])->sole(), 'no condition');
        $this->assertSame(2, $this->vec([1, 2, 3])->sole(fn($i) => $i === 2), 'with condition');
    }

    public function test_sole_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->sole();
    }

    public function test_sole_no_match(): void
    {
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->expectException(NoMatchFoundException::class);
        $this->vec([1, 2])->sole(fn() => false);
    }

    public function test_sole_multi(): void
    {
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1, 1])->sole();
    }

    public function test_sole_multi_condition(): void
    {
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([0, 1, 1, 2])->sole(fn($i) => $i === 1);
    }

    public function test_sort(): void
    {
        $this->assertSame([], $this->vec()->sort(SORT_ASC)->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->sort(SORT_ASC)->all(), 'one');
        $this->assertSame([1, 2], $this->vec([2, 1])->sort(SORT_ASC)->all(), 'sort asc');
        $this->assertSame([2, 1], $this->vec([1, 2])->sort(SORT_DESC)->all(), 'sort desc');
        $this->assertSame([1, 2, 3], $this->vec([2, 1, 3])->sort(SORT_ASC, fn($a) => $a)->all(), 'with by');
        $this->assertSame(["2", "10"], $this->vec(["10", "2"])->sort(SORT_ASC)->all(), 'sort regular');
        $this->assertSame(["10", "2"], $this->vec(["2", "10"])->sort(SORT_ASC, flag: SORT_STRING)->all(), 'with flag');
    }

    public function test_sortAsc(): void
    {
        $this->assertSame([], $this->vec()->sortAsc()->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->sortAsc()->all(), 'one');
        $this->assertSame([1, 2], $this->vec([2, 1])->sortAsc()->all(), 'sort asc');
        $this->assertSame([3, 2, 1], $this->vec([2, 1, 3])->sortAsc(fn($a) => -$a)->all(), 'with by');
        $this->assertSame(["2", "10"], $this->vec(["10", "2"])->sortAsc()->all(), 'sort regular');
        $this->assertSame(["10", "2"], $this->vec(["2", "10"])->sortAsc(flag: SORT_STRING)->all(), 'with flag');
    }

    public function test_sortDesc(): void
    {
        $this->assertSame([], $this->vec()->sortDesc()->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->sortDesc()->all(), 'one');
        $this->assertSame([2, 1], $this->vec([1, 2])->sortDesc()->all(), 'sort desc');
        $this->assertSame([1, 2, 3], $this->vec([2, 1, 3])->sortDesc(fn($a) => -$a)->all(), 'with by');
        $this->assertSame(["10", "2"], $this->vec(["2", "10"])->sortDesc()->all(), 'sort regular');
        $this->assertSame(["2", "10"], $this->vec(["10", "2"])->sortDesc(flag: SORT_STRING)->all(), 'with flag');
    }

    public function test_takeFirst(): void
    {
        $this->assertSame([], $this->vec([1, 2])->takeFirst(0)->all(), 'take none');
        $this->assertSame([1], $this->vec([1, 2])->takeFirst(1)->all(), 'take one');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeFirst(2)->all(), 'two');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeFirst(3)->all(), 'overflow');
        $this->assertSame(['a' => 1, 'b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->takeFirst(2)->all(), 'retain keys');
    }

    public function test_takeFirst_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1, 2])->takeFirst(-1);
    }

    public function test_takeLast(): void
    {
        $this->assertSame([], $this->vec([1, 2])->takeLast(0)->all(), 'take none');
        $this->assertSame([2], $this->vec([1, 2])->takeLast(1)->all(), 'take one');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeLast(2)->all(), 'two');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeLast(3)->all(), 'overflow');
        $this->assertSame(['b' => 2, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->takeLast(2)->all(), 'retain keys');
    }

    public function test_takeLast_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1, 2])->takeLast(-1);
    }

    public function test_takeWhile(): void
    {
        $this->assertSame([], $this->vec()->takeWhile(fn() => true)->all(), 'take empty');
        $this->assertSame([], $this->vec([1, 2])->takeWhile(fn() => false)->all(), 'take none');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeWhile(fn() => true)->all(), 'take all');
        $this->assertSame([1, 1], $this->vec([1, 1, 2, 1])->takeWhile(fn($i) => $i === 1)->all(), 'take check');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => 2])->takeWhile(fn($i) => $i === 1)->all(), 'take map');
    }

    public function test_takeUntil(): void
    {
        $this->assertSame([], $this->vec([1, 2])->takeUntil(fn() => true)->all(), 'take none');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeUntil(fn() => false)->all(), 'take all');
        $this->assertSame([1, 1], $this->vec([1, 1, 2, 1])->takeUntil(fn($i) => $i === 2)->all(), 'take check');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => 2])->takeUntil(fn($i) => $i === 2)->all(), 'take map');
    }

    public function test_toArray(): void
    {
        $this->assertSame([], $this->vec()->toArray(), 'empty');
        $this->assertSame([[1], ['a' => 1]], $this->vec([$this->vec([1]), $this->map(['a' => 1])])->toArray(), 'mixed');
        $this->assertInstanceOf(Map::class, $this->map(['a' => $this->map(['b' => 1])])->toArray(1)['a'], 'depth 1');
        $this->assertSame(['a' => ['b' => 1]], $this->map(['a' => $this->map(['b' => 1])])->toArray(), 'depth max');
    }

    public function test_toArray_with_negative_depth(): void
    {
        $this->expectExceptionMessage('Expected: $depth >= 1. Got: -1.');
        $this->expectException(InvalidArgumentException::class) ;
        $this->vec([1, 2])->toArray(-1);
    }

    public function test_toJson(): void
    {
        $this->assertSame('[]', $this->vec()->toJson(), 'empty list');
        $this->assertSame('{}', $this->map()->toJson(), 'empty map');
        $this->assertSame('[1,2]', $this->vec([1, 2])->toJson(), 'empty list');
        $this->assertSame('{"a":1}', $this->map(['a' => 1])->toJson(), 'empty map');
        $this->assertSame('[[1],{"a":1}]', $this->vec([$this->vec([1]), $this->map(['a' => 1])])->toJson(), 'mixed');
        $this->assertSame('{"a":{"b":1}}', $this->map(['a' => $this->map(['b' => 1])])->toJson(), 'depth max');
        $this->assertSame("{\n    \"a\": 1\n}", $this->map(['a' => 1])->toJson(true), 'pretty');
    }

    public function test_unique(): void
    {
        $this->assertSame([], $this->vec()->unique()->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->unique()->all(), 'one');
        $this->assertSame([1, 2], $this->vec([1, 1, 2, 1])->unique()->all(), 'keep first occurrence');
        $this->assertSame([null, 1], $this->vec([null, null, 1, 1])->unique()->all(), 'can handle null');
        $this->assertSame([true, false, 1, 0, null, ''], $this->vec([true, false, 1, 0, null, ''])->unique()->all(), 'strict');
        $this->assertSame([INF], $this->vec([INF, INF])->unique()->all(), 'one');
        $this->assertNan($this->vec([NAN, NAN])->unique()->sole(), 'one');
        $this->assertSame([1, 2], $this->vec([1, 2, 3, 4])->unique(fn($n) => $n % 2 === 0)->all(), 'with callback');

        $this->assertSame([], $this->map()->unique()->all(), 'empty');
        $this->assertSame(['a' => 1], $this->map(['a' => 1])->unique()->all(), 'one');
        $this->assertSame(['a' => 1], $this->map(['a' => 1, 'b' => 1])->unique()->all(), 'keep first occurrence');
    }

    public function test_when(): void
    {
        $callback = fn(Vec $s) => $s->append(1);
        $fallback = fn(Vec $s) => $s->append(2);

        $this->assertSame([1], $this->vec()->when(true, $callback, $fallback)->all(), 'when true on empty');
        $this->assertSame([2], $this->vec()->when(false, $callback, $fallback)->all(), 'when false on empty');

        $this->assertSame([1, 1], $this->vec([1])->when(true, $callback)->all(), 'when true no fallback');
        $this->assertSame([1, 1], $this->vec([1])->when(true, $callback, $fallback)->all(), 'when true with fallback');
        $this->assertSame([1], $this->vec([1])->when(false, $callback)->all(), 'when false no fallback');
        $this->assertSame([1, 2], $this->vec([1])->when(false, $callback, $fallback)->all(), 'when false with fallback');

        $this->assertSame([1], $this->vec()->when(fn() => true, $callback, $fallback)->all(), 'when callback true');
        $this->assertSame([2], $this->vec()->when(fn() => false, $callback, $fallback)->all(), 'when callback false');
    }
}
