<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;
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
     * @param TKey $key
     * @return bool
     */
    public function removeKey(int|string $key): bool
    {
        return Arr::removeKey($this->items, $key);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function set(int|string $key, mixed $value): static
    {
        Arr::set($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value): static
    {
        Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value): static
    {
        Arr::setIfNotExists($this->items, $key, $value);
        return $this;
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
