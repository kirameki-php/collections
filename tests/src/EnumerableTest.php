<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;

class EnumerableTest extends TestCase
{
    public function test_at(): void
    {
        $this->assertEquals(1, $this->vec([1, 2])->at(0));
        $this->assertEquals(2, $this->vec([1, 2])->at(1));
        $this->assertEquals(2, $this->vec([1, 2])->at(-1));
        $this->assertEquals(1, $this->vec([1, 2])->at(-2));

        $this->assertEquals(1, $this->map(['a' => 1, 'b' => 2])->at(0));
        $this->assertEquals(2, $this->map(['a' => 1, 'b' => 2])->at(1));
        $this->assertEquals(2, $this->map(['a' => 1, 'b' => 2])->at(-1));
        $this->assertEquals(1, $this->map(['a' => 1, 'b' => 2])->at(-2));
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
}
