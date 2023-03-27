<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Vec;

class VecTest extends TestCase
{
    public function test_constructor(): void
    {
        $vec = new Vec([1, 2]);
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
        $vec = new Vec([]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->toArray());
    }

    public function test_constructor_non_list(): void
    {
        $this->expectExceptionMessage('$items must only contain integer for key.');
        $this->expectException(InvalidKeyException::class);
        new Vec(['a' => 1]);
    }

    public function test_loop(): void
    {
        $vec = Vec::loop(3);
        self::assertSame([0, 1, 2], $vec->toArray());
    }

    public function test_jsonSerialize(): void
    {
        $vec = new Vec([1, 2]);
        self::assertSame([1, 2], $vec->jsonSerialize());
    }

    public function test_append(): void
    {
        $vec = new Vec([1, 2]);
        self::assertSame([1, 2, 3], $vec->append(3)->toArray());
    }

    public function test_append_multiple_variables(): void
    {
        $vec = new Vec([1, 2]);
        self::assertSame([1, 2, 3, 3, 4], $vec->append(3, 3, 4)->toArray());
    }

    public function test_map():void
    {
        $vec = new Vec([1, 2]);
        $mapped = $vec->map(fn($v): int => $v * 2);
        self::assertSame([2, 4], $mapped->toArray());
    }
}
