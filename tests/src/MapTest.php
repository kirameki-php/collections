<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Vec;
use function dump;

class MapTest extends TestCase
{
    public function test_lazy(): void
    {
        $vec = new Vec([1, 2, 3]);
        $vec->dump()
            ->lazy()
            ->dump()
            ->each(fn($i) => dump($i))
            ->first();
    }
}
