<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Utils\Arr;
use function count;

/**
 * @template TValue
 * @extends Vec<TValue>
 */
class VecMutable extends Vec
{
    /**
     * @use MutatesSelf<int, TValue>
     */
    use MutatesSelf {
        offsetSet as traitOffsetSet;
        offsetUnset as traitOffsetUnset;
    }

    /**
     * @param iterable<int, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct(Arr::from($items));
    }

    /**
     * @param int|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->ensureOffsetIsIndex($offset);

        $ref = $this->getItemsAsRef();
        $size = count($ref);
        if ($offset > $size) {
            throw new IndexOutOfBoundsException("Can not assign to a non-existing index. (size: {$size} index: {$offset})", [
                'this' => $this,
                'offset' => $offset,
                'size' => $size,
            ]);
        }

        self::traitOffsetSet($offset, $value);
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->ensureOffsetIsIndex($offset);
        self::traitOffsetUnset($offset);
    }

}
