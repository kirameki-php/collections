<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Vec;

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
        $this->expectExceptionMessage('$items must only contain integer for key.');
        $this->expectException(InvalidKeyException::class);
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

    public function test_append(): void
    {
        self::assertSame([1, 2, 3], $this->vec([1, 2])->append(3)->toArray());
    }

    public function test_append_multiple_variables(): void
    {
        self::assertSame([1, 2, 3, 3, 4], $this->vec([1, 2])->append(3, 3, 4)->toArray());
    }

    public function test_map():void
    {
        $mapped = $this->vec([1, 2])->map(fn($v): int => $v * 2);
        self::assertSame([2, 4], $mapped->toArray());
    }

    public function test_reindex(): void
    {
        self::assertSame([1, 2], $this->vec([null, 1, 2])->compact()->toArray(), 'with compact');
        self::assertSame([1, 3], $this->vec([1, 2, 3])->except([1])->toArray(), 'with except');
        self::assertSame([1, 3], $this->vec([1, 2, 3])->filter(fn($n) => (bool)($n % 2))->toArray(), 'with filter');
        self::assertSame([2], $this->vec([1, 2, 3])->only([1])->toArray(), 'with only');
        self::assertSame([2, 1], $this->vec([1, 2])->reverse()->toArray(), 'with reverse');
    }
}
