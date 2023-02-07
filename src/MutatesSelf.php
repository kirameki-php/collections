<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use SouthPointe\Collections\Utils\Arr;
use SouthPointe\Core\Exceptions\InvalidArgumentException;
use function gettype;
use function SouthPointe\Core\is_not_array_key;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 */
trait MutatesSelf
{
    /**
     * @var bool
     */
    protected bool $isList;

    /**
     * @return array<TKey, TValue>
     */
    abstract protected function &getItemsAsRef(): array;

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    abstract public function instantiate(iterable $iterable): static;

    /**
     * @param int|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $ref = &$this->getItemsAsRef();

        if ($offset === null) {
            $ref[] = $value;
            return;
        }

        if (is_not_array_key($offset)) {
            throw new InvalidArgumentException('Expected: $offset\'s type to be int|string. Got: ' . gettype($offset), [
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
        unset($ref[$offset]);
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
        return Arr::pull($ref, $key, $this->isList);
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
        return Arr::pullOr($ref, $key, $default, $this->isList);
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function pullOrNull(int|string $key): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pullOrNull($ref, $key, $this->isList);
    }

    /**
     * @param iterable<TKey> $keys
     * @return static
     */
    public function pullMany(iterable $keys): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::pullMany($ref, $keys, $this->isList));
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        $ref = &$this->getItemsAsRef();
        return Arr::remove($ref, $value, $limit, $this->isList);
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
}
