<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use Countable;
use JsonSerializable;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\TypeMismatchException;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Random\Randomizer;
use function assert;
use function count;
use function gettype;
use function is_array;
use function is_int;
use function is_null;
use function sprintf;
use const PHP_INT_MAX;

/**
 * @template TValue
 * @extends Enumerator<int, TValue>
 * @implements ArrayAccess<int, TValue>
 * @phpstan-consistent-constructor
 */
class Vec extends Enumerator implements ArrayAccess, Countable, JsonSerializable
{
    /**
     * @use MutatesSelf<int, TValue>
     */
    use MutatesSelf {
        offsetExists as traitOffsetExists;
        offsetGet as traitOffsetGet;
        offsetSet as traitOffsetSet;
        offsetUnset as traitOffsetUnset;
    }

    /**
     * @param iterable<int, mixed> $items
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
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        return Arr::from($this);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->ensureOffsetIsIndex($offset);
        return self::traitOffsetExists($offset);
    }

    /**
     * @param int $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->ensureOffsetIsIndex($offset);
        return self::traitOffsetGet($offset);
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

    /**
     * @param TValue ...$value
     * @return static
     */
    public function append(mixed ...$value): static
    {
        return $this->instantiate(Arr::append($this, ...$value));
    }

    /**
     * @inheritDoc
     * @template TMapValue
     * @param Closure(TValue, int): TMapValue $callback
     * @return self<TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newVec(Iter::map($this, $callback));
    }

    /**
     * @param int $size
     * @param TValue $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->instantiate(Arr::pad($this, $size, $value));
    }

    /**
     * @param mixed ...$value
     * @return static
     */
    public function prepend(mixed ...$value): static
    {
        return $this->instantiate(Arr::prepend($this, ...$value));
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
    public function sampleIndexes(int $amount, bool $replace = false, ?Randomizer $randomizer = null): self
    {
        return $this->newVec(Arr::sampleKeys($this, $amount, $replace, $randomizer));
    }

    /**
     * @return bool
     */
    protected function reindex(): bool
    {
        return true;
    }

    /**
     * @return array<int, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        assert(is_array($this->items));
        return $this->items;
    }

    protected function ensureOffsetIsIndex(mixed $offset): ?int
    {
        if (is_int($offset) || is_null($offset)) {
            return $offset;
        }

        $type = gettype($offset);
        throw new InvalidKeyException("Expected: \$offset's type to be int|null. Got: {$type}", [
            'this' => $this,
            'offset' => $offset,
            'type' => $type,
        ]);
    }
}
