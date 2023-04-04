<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\DuplicateKeyException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Map;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use stdClass;
use function dump;

class MapTest extends TestCase
{
    public function test_constructor(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 1, 'b' => 2], $map->toArray());
    }

    public function test_constructor_no_args(): void
    {
        $map = new Map();
        self::assertInstanceOf(Map::class, $map);
        self::assertSame([], $map->toArray());
    }

    public function test_constructor_empty(): void
    {
        $map = $this->map([]);
        self::assertInstanceOf(Map::class, $map);
        self::assertSame([], $map->toArray());
    }

    public function test_of(): void
    {
        $map = Map::of(a: 1);
        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 1], $map->toArray());
    }

    public function test_jsonSerialize(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        $data = $map->jsonSerialize();
        self::assertInstanceOf(stdClass::class, $data);
        self::assertEquals((object)['a' => 1, 'b' => 2], $data);
    }

    public function test_offsetSet_assignment_with_invalid_key(): void
    {
        $this->expectExceptionMessage("Expected: \$offset's type to be int|string. Got: double.");
        $this->expectException(InvalidArgumentException::class);
        $map = $this->map();
        $map[0.3] = 3;
    }

    public function test_clear(): void
    {
        $map = $this->map([]);
        self::assertSame([], $map->clear()->toArray(), 'empty map');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame([], $map->clear()->toArray(), 'non-empty map');
    }

    public function test_insertAt(): void
    {
        self::assertSame(
            ['a' => 1],
            $this->map()->insertAt(0, ['a' => 1])->toArray(),
            'empty map',
        );

        self::assertSame(
            ['a' => 1],
            $this->map()->insertAt(-100, ['a' => 1])->toArray(),
            'negative overflows on empty map',
        );

        self::assertSame(
            ['a' => 1],
            $this->map()->insertAt(100, ['a' => 1])->toArray(),
            'overflows on empty map',
        );

        self::assertSame(
            ['b' => 2, 'a' => 1],
            $this->map(['a' => 1])->insertAt(0, ['b' => 2])->toArray(),
            'non-empty map',
        );

        self::assertSame(
            ['a' => 1, 'b' => 2, 'c' => 3],
            $this->map(['a' => 1, 'b' => 2])->insertAt(-1, ['c' => 3])->toArray(),
            'negative insert index',
        );

        self::assertSame(
            message: 'negative insert index',
            actual: $this->map(['a' => 1, 'b' => 2])->insertAt(1, ['c' => 3])->toArray(),
            expected: ['a' => 1, 'c' => 3, 'b' => 2],
        );

        self::assertSame(
            message: 'insert with overwrite',
            actual: $this->map(['a' => 1, 'b' => 2])->insertAt(-1, ['c' => 3, 'a' => 0], true)->toArray(),
            expected: ['b' => 2, 'c' => 3, 'a' => 0],
        );
    }

    public function test_insertAt_duplicate_without_overwrite(): void
    {
        $this->expectExceptionMessage('Tried to overwrite existing key: a.');
        $this->expectException(DuplicateKeyException::class);
        $this->map(['a' => 1])->insertAt(0, ['a' => 2]);
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
        self::assertSame([], $map->diffKeys([])->toArray(), 'empty map and empty keys');
        self::assertSame([], $map->diffKeys(['a' => 1])->toArray(), 'empty map and non-empty keys');

        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->diffKeys([])->toArray());
        self::assertSame(['a' => 1, 'b' => 2], $map->diffKeys(['c' => 1])->toArray());
        self::assertSame(['a' => 1], $map->diffKeys(['b' => 8, 'c' => 9])->toArray());
        self::assertSame([], $map->diffKeys(['a' => 7, 'b' => 8, 'c' => 9])->toArray());
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
        $this->expectExceptionMessage('"c" does not exist.');
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

    public function test_pullOrNull(): void
    {
        $map = $this->map(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->pullOrNull('b'));
        self::assertNull($map->pullOrNull('b'));
        self::assertSame(['a' => 1], $map->toArray());
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
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2)->toArray(),
            'sample keys exact no-randomizer',
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(2));

        self::assertSame(
            ['b'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(1, false, $randomizer)->toArray(),
            'sample 1 keys +no-replacement +randomizer',
        );

        self::assertSame(
            ['b', 'a'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2, false, $randomizer)->toArray(),
            'sample keys exact (should be out of order) +no-replacement +randomizer',
        );

        self::assertSame(
            ['b', 'b'],
            $this->map(['a' => 1, 'b' => 2])->sampleKeys(2, true, $randomizer)->toArray(),
            'sample keys exact +replacement +randomizer',
        );

        self::assertSame(
            ['a', 'c'],
            $this->map(['a' => 1, 'b' => 1, 'c' => 1])->sampleKeys(2, true, $randomizer)->toArray(),
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

    public function test_reindex(): void
    {
        self::assertSame(['b' => 1, 'c' => 2], $this->map(['a' => null, 'b' => 1, 'c' => 2])->compact()->toArray(), 'with compact');
        self::assertSame(['a' => 1, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->except(['b'])->toArray(), 'with except');
        self::assertSame(['a' => 1, 'c' => 3], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->filter(fn($n) => (bool)($n % 2))->toArray(), 'with filter');
        self::assertSame(['b' => 2], $this->map(['a' => 1, 'b' => 2, 'c' => 3])->only(['b'])->toArray(), 'with only');
        self::assertSame(['b' => 2, 'a' => 1], $this->map(['a' => 1, 'b' => 2])->reverse()->toArray(), 'with reverse');
    }

}
