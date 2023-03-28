<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use Countable;
use JsonSerializable;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Random\Randomizer;
use function assert;
use function is_array;
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
    use MutatesSelf;

    /**
     * @param iterable<int, mixed> $items
     */
    public function __construct(iterable $items = [])
    {
        if (is_array($items) && !Arr::isList($items)) {
            throw new InvalidKeyException('$items must only contain integer for key.', [
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
}
