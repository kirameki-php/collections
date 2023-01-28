<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use Webmozart\Assert\Assert;
use function assert;
use function is_array;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Map<TKey, TValue>
 */
class MutableMap extends Map
{
    /**
     * @use MutatesSelf<TKey, TValue>
     */
    use MutatesSelf {
        MutatesSelf::offsetSet as baseOffsetSet;
    }

    /**
     * @param TKey $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        Assert::validArrayKey($offset);
        $this->baseOffsetSet($offset, $value);
    }

    /**
     * @return array<TKey, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        assert(is_array($this->items));
        return $this->items;
    }
}
