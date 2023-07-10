<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use function gettype;
use function is_array;
use function is_int;

/**
 * @template TKey of array-key
 * @template TValue
 */
trait MutatesSelf
{
    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    abstract public function instantiate(iterable $iterable): static;

    /**
     * @return bool
     */
    abstract protected function reindex(): bool;

    /**
     * @return array<TKey, TValue>
     */
    abstract protected function &getItemsAsRef(): array;

    /**
     * @param int|string|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $ref = &$this->getItemsAsRef();

        if ($offset === null && $this->reindex()) {
            $ref[] = $value;
            return;
        }

        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Expected: $offset\'s type to be int|string. Got: ' . gettype($offset) . '.', [
                'this' => $this,
                'offset' => $offset,
                'value' => $value,
            ]);
        }

        $ref[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $ref = &$this->getItemsAsRef();
        Arr::pullOrNull($ref, $offset, $this->reindex());
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $ref = &$this->getItemsAsRef();
        $ref = [];
        return $this;
    }

    /**
     * @param int $index
     * The position where the values will be inserted.
     * @param iterable<TKey, TValue> $values
     * One or more values that will be inserted.
     * @param bool $overwrite
     * [Optional] If **true**, duplicates will be overwritten for string keys.
     * If **false**, exception will be thrown on duplicate key.
     * Defaults to **false**.
     * @return $this
     */
    public function insertAt(int $index, iterable $values, bool $overwrite = false): static
    {
        $ref = &$this->getItemsAsRef();
        Arr::insertAt($ref, $index, $values, $this->reindex(), $overwrite);
        return $this;
    }

    /**
     * @return TValue
     */
    public function pop(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pop($ref);
    }

    /**
     * @return TValue|null
     */
    public function popOrNull(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::popOrNull($ref);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function popMany(int $amount): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::popMany($ref, $amount));
    }

    /**
     * @param TKey $key
     * @return TValue
     */
    public function pull(int|string $key): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pull($ref, $key, $this->reindex());
    }

    /**
     * @template TDefault
     * @param TKey $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function pullOr(int|string $key, mixed $default): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pullOr($ref, $key, $default, $this->reindex());
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function pullOrNull(int|string $key): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pullOrNull($ref, $key, $this->reindex());
    }

    /**
     * @param iterable<TKey> $keys
     * Keys or indexes to be pulled.
     * @param array<int, TKey>|null &$missed
     * [Optional][Reference] `$keys` that did not exist.
     * @return static
     */
    public function pullMany(iterable $keys, ?array &$missed = null): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::pullMany($ref, $keys, $this->reindex(), $missed));
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        $ref = &$this->getItemsAsRef();
        return Arr::remove($ref, $value, $limit, $this->reindex());
    }

    /**
     * @return TValue
     */
    public function shift(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::shift($ref);
    }

    /**
     * @return TValue|null
     */
    public function shiftOrNull(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::shiftOrNull($ref);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function shiftMany(int $amount): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::shiftMany($ref, $amount));
    }

    /**
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $iterable
     * @return MapMutable<TNewKey, TNewValue>
     */
    protected function newMap(iterable $iterable): MapMutable
    {
        return new MapMutable($iterable);
    }

    /**
     * @template TNewValue
     * @param iterable<int, TNewValue> $iterable
     * @return VecMutable<TNewValue>
     */
    protected function newVec(iterable $iterable): VecMutable
    {
        return new VecMutable($iterable);
    }
}
