<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\LazyIterator;
use Kirameki\Collections\Map;
use Kirameki\Collections\MapMutable;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use stdClass;

final class MapTest extends TestCase
{
    public function test_constructor(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 1, 'b' => 2], $map->all());
    }

    public function test_constructor_no_args(): void
    {
        $map = new Map();
        self::assertInstanceOf(Map::class, $map);
        self::assertSame([], $map->all());
    }

    public function test_constructor_empty(): void
    {
        $map = $this->map([]);
        self::assertInstanceOf(Map::class, $map);
        self::assertSame([], $map->all());
    }

    public function test_jsonSerialize(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        $data = $map->jsonSerialize();
        self::assertInstanceOf(stdClass::class, $data);
        self::assertEquals((object)['a' => 1, 'b' => 2], $data);
    }

    public function test_offsetExists(): void
    {
        $map = $this->map(['a' => 1]);
        $this->assertTrue(isset($map['a']));
        $this->assertFalse(isset($map['b']));
    }

    public function test_offsetGet(): void
    {
        $map = $this->map(['a' => 1]);
        $this->assertSame(1, $map['a']);
        $this->assertNull($map['b'] ?? null);
    }

    public function test_offsetGet_non_existent_access(): void
    {
        $this->throwOnError();
        $this->expectExceptionMessage('Undefined array key "b"');
        $this->expectException(ErrorException::class);

        $map = $this->map();
        $this->assertNull($map['b']);
    }

    public function test_offsetGet_invalid_type(): void
    {
        $this->throwOnError();
        $this->expectExceptionMessage("Map's inner item must be of type array|ArrayAccess, Kirameki\Collections\LazyIterator given.");
        $this->expectException(NotSupportedException::class);
        $vec = $this->map(new LazyIterator([1]));
        $vec[0];
    }

    public function test_offsetSet_throws_error(): void
    {
        $this->expectExceptionMessage("Kirameki\Collections\Map::offsetSet is not supported.");
        $this->expectException(NotSupportedException::class);
        $map = $this->map();
        $map['a'] = 1;
    }

    public function test_offsetUnset_throws_error(): void
    {
        $this->expectExceptionMessage("Kirameki\Collections\Map::offsetUnset is not supported.");
        $this->expectException(NotSupportedException::class);
        $map = $this->map(['a' => 1]);
        unset($map['a']);
    }

    public function test_containsAllKeys(): void
    {
        $map = $this->map();
        self::assertTrue($map->containsAllKeys([]), 'empty map and empty keys');
        self::assertFalse($map->containsAllKeys(['a']), 'empty map and non-empty keys');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertTrue($map->containsAllKeys([]), 'empty keys');
        self::assertTrue($map->containsAllKeys(['a', 'b']));
        self::assertFalse($map->containsAllKeys(['a', 'b', 'c']));
    }

    public function test_containsAnyKeys(): void
    {
        $map = $this->map();
        self::assertFalse($map->containsAnyKeys([]), 'empty map and empty keys');
        self::assertFalse($map->containsAnyKeys(['a']), 'empty map and non-empty keys');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertFalse($map->containsAnyKeys([]), 'empty keys');
        self::assertFalse($map->containsAnyKeys(['d']), 'only non-existing keys');
        self::assertTrue($map->containsAnyKeys(['a']), 'only existing keys');
        self::assertTrue($map->containsAnyKeys(['a', 'b']), 'exact matching keys');
        self::assertTrue($map->containsAnyKeys(['a', 'b', 'c']), 'some existing keys');
    }

    public function test_containsKey(): void
    {
        $map = $this->map();
        self::assertFalse($map->containsKey('a'), 'empty map');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertTrue($map->containsKey('a'));
        self::assertFalse($map->containsKey('c'));
    }

    public function test_diffKeys(): void
    {
        $map = $this->map();
        self::assertSame([], $map->diffKeys([])->all(), 'empty map and empty keys');
        self::assertSame([], $map->diffKeys(['a' => 1])->all(), 'empty map and non-empty keys');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->diffKeys([])->all());
        self::assertSame(['a' => 1, 'b' => 2], $map->diffKeys(['c' => 1])->all());
        self::assertSame(['a' => 1], $map->diffKeys(['b' => 8, 'c' => 9])->all());
        self::assertSame([], $map->diffKeys(['a' => 7, 'b' => 8, 'c' => 9])->all());
    }

    public function test_doesNotContainKey(): void
    {
        $map = $this->map();
        self::assertTrue($map->doesNotContainKey('a'), 'empty map');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertFalse($map->doesNotContainKey('a'), 'existing key');
        self::assertTrue($map->doesNotContainKey('c'), 'non-existing key');
    }

    public function test_firstKey(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('a', $map->firstKey(), 'first key');
    }

    public function test_firstKey_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->map()->firstKey();
    }

    public function test_firstKeyOrNull(): void
    {
        $map = $this->map();
        self::assertNull($map->firstKeyOrNull(), 'first key on empty');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('a', $map->firstKeyOrNull(), 'first key');
    }

    public function test_get(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(1, $map->get('a'), 'existing key');
        self::assertSame(2, $map->get('b'), 'existing key');
    }

    public function test_get_non_exiting_key(): void
    {
        $this->expectExceptionMessage('Key: "c" does not exist.');
        $this->expectException(InvalidKeyException::class);
        $this->map()->get('c');
    }

    public function test_getOr(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(1, $map->getOr('a', 3), 'existing key');
        self::assertSame(2, $map->getOr('b', 3), 'existing key');
        self::assertTrue($map->getOr('c', true), 'non-existing key');
        self::assertTrue($map->getOr(1, true), 'non-existing key');
    }

    public function test_getOrNull(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(1, $map->getOrNull('a'), 'existing key');
        self::assertSame(2, $map->getOrNull('b'), 'existing key');
        self::assertNull($map->getOrNull('c'), 'non-existing key');
        self::assertNull($map->getOrNull(1), 'non-existing key');
    }

    public function test_intersectKeys(): void
    {
        $map = $this->map();
        self::assertSame([], $map->intersectKeys([])->all(), 'empty map and empty keys');
        self::assertSame([], $map->intersectKeys(['a' => 1])->all(), 'empty map and non-empty keys');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame([], $map->intersectKeys([])->all(), 'empty keys');
        self::assertSame([], $map->intersectKeys(['c' => 3])->all(), 'non-existing keys');
        self::assertSame(['b' => 2], $map->intersectKeys(['b' => 8, 'c' => 9])->all(), 'some existing keys');
        self::assertSame(['a' => 1, 'b' => 2], $map->intersectKeys(['a' => 7, 'b' => 8, 'c' => 9])->all(), 'all existing keys');
    }

    public function test_keyAt(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('a', $map->keyAt(0), 'first key');
        self::assertSame('b', $map->keyAt(1), 'second key');
        self::assertSame('b', $map->keyAt(-1), 'second key from negative');
        self::assertSame('a', $map->keyAt(-2), 'first key from negative');
    }

    public function test_keyAt_empty(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: 0.');
        $this->expectException(IndexOutOfBoundsException::class);
        $this->map()->keyAt(0);
    }

    public function test_keyAt_out_of_bounds_positive(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: 2.');
        $this->expectException(IndexOutOfBoundsException::class);
        $this->map(['a' => 1, 'b' => 2])->keyAt(2);
    }

    public function test_keyAt_out_of_bounds_negative(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: -3.');
        $this->expectException(IndexOutOfBoundsException::class);
        $this->map(['a' => 1, 'b' => 2])->keyAt(-3);
    }

    public function test_keyAtOrNull(): void
    {
        self::assertNull($this->map()->keyAtOrNull(0), 'empty');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('a', $map->keyAtOrNull(0), 'first key');
        self::assertSame('b', $map->keyAtOrNull(1), 'second key');
        self::assertSame('b', $map->keyAtOrNull(-1), 'second key from negative');
        self::assertSame('a', $map->keyAtOrNull(-2), 'first key from negative');
        self::assertNull($map->keyAtOrNull(2), 'out of bounds positive');
        self::assertNull($map->keyAtOrNull(-3), 'out of bounds negative');
    }

    public function test_lastKey(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('b', $map->lastKey(), 'last key');
    }

    public function test_lastKey_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->map()->lastKey();
    }

    public function test_lastKeyOrNull(): void
    {
        $map = $this->map();
        self::assertNull($map->firstKeyOrNull(), 'first key on empty');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('b', $map->lastKeyOrNull(), 'first key');
    }

    public function test_map(): void
    {
        $map = $this->map();
        self::assertSame([], $map->map(static fn($v) => $v * 2)->all(), 'empty map');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 2, 'b' => 4], $map->map(static fn($v) => $v * 2)->all(), 'non-empty map');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 'aa', 'b' => 'bb'], $map->map(static fn($v, $k) => $k . $k)->all(), 'non-empty map with key args');
    }

    public function test_mutable():void
    {
        self::assertInstanceOf(MapMutable::class, $this->map(['a' => 1])->mutable());
    }

    public function test_sampleKey(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertIsString($map->sampleKey(), 'sample key');

        $randomizer = new Randomizer(new Xoshiro256StarStar(10));
        self::assertSame('b', $map->sampleKey($randomizer), 'sample key with randomizer');
    }

    public function test_sampleKey_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->map()->sampleKey();
    }

    public function test_sampleKeyOrNull(): void
    {
        self::assertNull($this->map()->sampleKeyOrNull(), 'sample key on empty');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertIsString($map->sampleKey(), 'sample key');

        $randomizer = new Randomizer(new Xoshiro256StarStar(10));
        self::assertSame('b', $map->sampleKey($randomizer), 'sample key with randomizer');
    }

    public function test_sampleKeys(): void
    {
        self::assertCount(
            2,
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2)->all(),
            'sample keys exact no-randomizer',
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(2));

        self::assertSame(
            ['b'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(1, false, $randomizer)->all(),
            'sample 1 keys +no-replacement +randomizer',
        );

        self::assertSame(
            ['b', 'a'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2, false, $randomizer)->all(),
            'sample keys exact (should be out of order) +no-replacement +randomizer',
        );

        self::assertSame(
            ['b', 'b'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2, true, $randomizer)->all(),
            'sample keys exact +replacement +randomizer',
        );

        self::assertSame(
            ['a', 'c'],
            $this->map(['a' => 1, 'b' => 1, 'c' => 1])->sampleKeys(2, true, $randomizer)->all(),
            'sample keys less than size +replacement +randomizer',
        );
    }

    public function test_sampleKeys_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->map()->sampleKeys(1);
    }

    public function test_sampleKeys_on_negative(): void
    {
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable.');
        $this->expectException(InvalidArgumentException::class);
        $this->map(['a' => 1])->sampleKeys(-1);
    }

    public function test_sampleKeys_on_too_large(): void
    {
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable.');
        $this->expectException(InvalidArgumentException::class);
        $this->map(['a' => 1])->sampleKeys(2);
    }

    public function test_sortByKey(): void
    {
        $map = $this->map();
        self::assertSame([], $map->sortByKey(true)->all(), 'set on empty ascending');
        self::assertSame([], $map->sortByKey(false)->all(), 'set on empty descending');

        $map = $this->map(['b' => 2, 'a' => 1, 'c' => -1]);
        self::assertSame(['a' => 1, 'b' => 2, 'c' => -1], $map->sortByKey(true)->all(), 'sort by key');
        self::assertSame(['c' => -1, 'b' => 2, 'a' => 1], $map->sortByKey(false)->all(), 'sort by key reverse');
    }

    public function test_sortByKeyAsc(): void
    {
        $map = $this->map();
        self::assertSame([], $map->sortByKeyAsc()->all(), 'set on empty');

        $map = $this->map(['b' => 2, 'a' => 1, 'c' => -1]);
        self::assertSame(['a' => 1, 'b' => 2, 'c' => -1], $map->sortByKeyAsc()->all(), 'sort by key');
    }

    public function test_sortByKeyDesc(): void
    {
        $map = $this->map();
        self::assertSame([], $map->sortByKeyDesc()->all(), 'set on empty');

        $map = $this->map(['b' => 2, 'a' => 1, 'c' => -1]);
        self::assertSame(['c' => -1, 'b' => 2, 'a' => 1], $map->sortByKeyDesc()->all(), 'sort by key reverse');
    }

    public function test_sortWithKey(): void
    {
        $map = $this->map();
        self::assertSame([], $map->sortWithKey(fn($a, $b) => $a <=> $b)->all(), 'set on empty');

        $map = $this->map(['b' => 2, 'a' => 1, 'c' => -1]);
        self::assertSame(['a' => 1, 'b' => 2, 'c' => -1], $map->sortWithKey(fn($a, $b) => $a <=> $b)->all(), 'sort by key');
        self::assertSame(['c' => -1, 'b' => 2, 'a' => 1], $map->sortWithKey(fn($a, $b) => $b <=> $a)->all(), 'sort by key reverse');
    }

    public function test_toUrlQuery(): void
    {
        $map = $this->map();
        self::assertSame('', $map->toUrlQuery(), 'empty');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('a=1&b=2', $map->toUrlQuery(), 'simple');

        $map = $this->map(['a' => 1, 'b' => 2, 'c' => 'a']);
        self::assertSame('a=1&b=2&c=a', $map->toUrlQuery(), 'mixed types');

        $map = $this->map();
        self::assertSame('', $map->toUrlQuery('x'), 'empty with namespace');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame('x%5Ba%5D=1&x%5Bb%5D=2', $map->toUrlQuery('x'), 'simple with namespace');

        $map = $this->map(['a' => 1, 'b' => 2, 'c' => 'a']);
        self::assertSame('x%5Ba%5D=1&x%5Bb%5D=2&x%5Bc%5D=a', $map->toUrlQuery('x'), 'mixed types with namespace');
    }

    public function test_values(): void
    {
        $this->assertInstanceOf(Vec::class, $this->map()->values());
        $this->assertSame([], $this->map()->values()->all());
        $this->assertSame([1, 2], $this->map(['a' => 1, 'b' => 2])->values()->all());
    }

    public function test_reindex(): void
    {
        self::assertSame(['b' => 1, 'c' => 2], $this->map(['a' => null, 'b' => 1, 'c' => 2])->compact()->all(), 'with compact');
        self::assertSame(['a' => 1, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->dropKeys(['b'])->all(), 'with except');
        self::assertSame(['a' => 1, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->filter(fn($n) => (bool)($n % 2))->all(), 'with filter');
        self::assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->takeKeys(['b'])->all(), 'with only');
        self::assertSame(['b' => 2, 'a' => 1], $this->map(['a' => 1, 'b' => 2])->reverse()->all(), 'with reverse');
    }
}
