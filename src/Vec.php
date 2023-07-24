<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use JsonSerializable;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Collections\Utils\Range;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Random\Randomizer;
use function gettype;
use function is_array;
use function is_int;
use function is_null;
use const PHP_INT_MAX;

/**
 * @template TValue
 * @extends Enumerator<int, TValue>
 * @implements ArrayAccess<int, TValue>
 * @phpstan-consistent-constructor
 */
class Vec extends Enumerator implements ArrayAccess, JsonSerializable
{
    /**
     * @param iterable<int, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        if (is_array($items) && !Arr::isList($items)) {
            throw new TypeMismatchException('$items must be a list, map given.', [
                'items' => $items,
            ]);
        }

        parent::__construct($items);
    }

    /**
     * @param int $times
     * @return self<int>
     */
    public static function loop(int $times = PHP_INT_MAX): self
    {
        $generator = (function(int $times) {
            $counter = 0;
            while($counter < $times) {
                yield $counter;
                ++$counter;
            }
        })($times);
        return new static(new LazyIterator($generator));
    }

    /**
     * @param int $start
     * @param int $end
     * @param bool $includeEnd
     * @return self<int>
     */
    public static function range(
        int $start,
        int $end,
        bool $includeEnd = true,
    ): self
    {
        return new static(new Range($start, $end, $includeEnd));
    }

    /**
     * @return array<int, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        $items = &$this->items;

        if (is_array($items)) {
            return $items;
        }

        $innerType = get_debug_type($items);
        throw new NotSupportedException("Vec's inner item must be of type array|ArrayAccess, {$innerType} given.", [
            'this' => $this,
            'items' => $items,
        ]);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->ensureOffsetIsIndex($offset);
        $ref = $this->getItemsAsRef();
        return isset($ref[$offset]);
    }

    /**
     * @param int $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->ensureOffsetIsIndex($offset);
        $ref = $this->getItemsAsRef();
        return $ref[$offset];
    }

    /**
     * @param int|string|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        return Arr::from($this);
    }

    /**
     * Append value(s) to the end.
     *
     * @param TValue ...$values
     * Value(s) to be appended.
     * @return static
     */
    public function append(mixed ...$values): static
    {
        return $this->instantiate(Arr::append($this, ...$values));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, int): TMapValue $callback
     * @return self<TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newVec(Iter::map($this, $callback));
    }

    /**
     * @return VecMutable<TValue>
     */
    public function mutable(): VecMutable
    {
        $items = is_object($this->items)
            ? clone $this->items
            : $this->items;
        return new VecMutable($items);
    }

    /**
     * Returns a new instance with a `$value` padded to the left side up to `$length`.
     *
     * @param int $length
     * Apply padding until the array size reaches this length. Must be >= 0.
     * @param TValue $value
     * Value inserted into each padding.
     * @return static
     */
    public function padLeft(int $length, mixed $value): static
    {
        return $this->instantiate(Arr::padLeft($this, $length, $value));
    }

    /**
     * Returns a new instance with a `$value` padded to the right side up to `$length`.
     *
     * @param int $length
     * Apply padding until the array size reaches this length. Must be >= 0.
     * @param TValue $value
     * Value inserted into each padding.
     * @return static
     */
    public function padRight(int $length, mixed $value): static
    {
        return $this->instantiate(Arr::padRight($this, $length, $value));
    }

    /**
     * Returns a new instance with `$value`(s) prepended to the front.
     *
     * @param mixed ...$values
     * Value(s) to be prepended.
     * @return static
     */
    public function prepend(mixed ...$values): static
    {
        return $this->instantiate(Arr::prepend($this, ...$values));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->instantiate(Arr::repeat($this, $times));
    }

    /**
     * @param Randomizer|null $randomizer
     * @return int
     */
    public function sampleIndex(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleKey($this, $randomizer);
    }

    /**
     * @param Randomizer|null $randomizer
     * @return int|null
     */
    public function sampleIndexOrNull(?Randomizer $randomizer = null): ?int
    {
        /** @var int|null needed for some reason by phpstan */
        return Arr::sampleKeyOrNull($this, $randomizer);
    }

    /**
     * @param int $amount
     * @param bool $replace
     * @param Randomizer|null $randomizer
     * @return self<int>
     */
    public function sampleIndexes(
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): self
    {
        return $this->newVec(Arr::sampleKeys($this, $amount, $replace, $randomizer));
    }

    /**
     * Returns the symmetric difference between the collection and `$iterable`.
     * `$iterable` must be of type list.
     * Throws `TypeMismatchException` if map is given.
     *
     * @param iterable<int, TValue> $iterable
     * Iterable to be diffed.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] User defined comparison callback.
     * Return 1 if first argument is greater than the 2nd.
     * Return 0 if first argument is equal to the 2nd.
     * Return -1 if first argument is less than the 2nd.
     * Defaults to **null**.
     * @return static
     */
    public function symDiff(iterable $iterable, Closure $by = null): static
    {
        return $this->instantiate(Arr::symDiff($this, $iterable, $by));
    }

    /**
     * Returns a `Vec` consisting of sub `Vec`s where each sub `Vec` is an aggregate of
     * elements in `$iterables` at each position. The given `$iterables` must all be
     * a list.
     *
     * @param iterable<int, TValue> ...$iterables
     * @return Vec<Vec<TValue|null>>
     */
    public function zip(iterable ...$iterables): self
    {
        if (count($iterables) < 1) {
            throw new InvalidArgumentException('Expected: at least 1 argument. Got: 0.', [
                'this' => $this,
                'iterables' => $iterables,
            ]);
        }

        return $this->newVec(
            array_map(fn($v): self => $this->newVec($v), Arr::zip($this, ...$iterables)),
        );
    }

    /**
     * @inheritDoc
     */
    protected function reindex(): bool
    {
        return true;
    }

    protected function ensureOffsetIsIndex(mixed $offset): ?int
    {
        if (is_int($offset) || is_null($offset)) {
            return $offset;
        }

        $type = gettype($offset);
        throw new InvalidKeyException("Expected: \$offset's type to be int|null. Got: {$type}.", [
            'this' => $this,
            'offset' => $offset,
            'type' => $type,
        ]);
    }
}
