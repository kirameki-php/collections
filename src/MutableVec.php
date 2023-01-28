<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use Webmozart\Assert\Assert;
use function assert;
use function is_array;

/**
 * @template TValue
 * @extends Vec<TValue>
 */
class MutableVec extends Vec
{
    /**
     * @use MutatesSelf<int, TValue>
     */
    use MutatesSelf;

    /**
     * @return array<int, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        assert(is_array($this->items));
        return $this->items;
    }
}
