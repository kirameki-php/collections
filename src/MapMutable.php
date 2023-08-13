<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Map<TKey, TValue>
 */
class MapMutable extends Map
{
    /**
     * @use MutatesSelf<TKey, TValue>
     */
    use MutatesSelf;

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct(Arr::from($items));
    }

    /**
     * @return Map<TKey, TValue>
     */
    public function immutable(): Map
    {
        return new Map($this->items);
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
     * @param bool $result
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value, bool &$result = false): static
    {
        $result = Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @param bool $result
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, bool &$result = false): static
    {
        $result = Arr::setIfNotExists($this->items, $key, $value);
        return $this;
    }
}
