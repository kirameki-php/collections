<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use function assert;
use function gettype;
use function is_array;
use function Kirameki\Core\is_not_array_key;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Map<TKey, TValue>
 */
class MapMutable extends Map
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
        if (is_not_array_key($offset)) {
            throw new InvalidArgumentException('Expected: $offset\'s type to be int|string. Got: ' . gettype($offset), [
                'this' => $this,
                'offset' => $offset,
                'value' => $value,
            ]);
        }

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
