<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Generator;
use IteratorAggregate;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Dumper\Config;
use Traversable;
use function dump;
use function is_iterable;
use function iterator_to_array;

/**
 * @phpstan-consistent-constructor
 *
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Enumerator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        protected iterable $items = [],
    )
    {
    }

    /**
     * @template TNewKey as array-key
     * @template TNewValue
     * @param Closure(): Generator<TNewKey, TNewValue> $closure
     * @return self<TNewKey, TNewValue>
     */
    public static function fromClosure(Closure $closure): self
    {
        $generator = $closure();
        return new static($generator);
    }

    /**
     * @param Config|null $config
     * @return $this
     */
    public function dump(?Config $config = null): static
    {
        dump($this, $config);
        return $this;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @param int<1, max> $size
     * @return self<int, static>
     */
    public function chunk(int $size): self
    {
        return self::fromClosure(function() use ($size) {
            foreach (Iter::chunk($this, $size) as $key => $chunk) {
                yield $key => $this->instantiate($chunk);
            }
        });
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return $this->instantiate(Iter::dropFirst($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): mixed $callback
     * @return static
     */
    public function each(Closure $callback): static
    {
        return self::fromClosure(function() use ($callback) {
            yield from Iter::each($this, $callback);
        });
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function equals(mixed $items): bool
    {
        if (is_iterable($items)) {
            /** @var iterable<array-key, mixed> $items */
            return $this->toArray() === Arr::from($items);
        }
        return false;
    }

    /**
     * Creates a Generator that will send the key/value to the generator if the condition is **true**.
     *
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->instantiate(Iter::filter($this, $condition));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function instantiate(mixed $items): static
    {
        return new static($items);
    }

    /**
     * @param int|null $steps
     * @return void
     */
    public function iterate(int $steps = null): void
    {
        if ($steps === null) {
            iterator_to_array($this);
            return;
        }

        if ($steps === 0) {
            return;
        }

        $count = 0;
        foreach ($this as $_) {
            $count++;
            if ($steps === $count) {
                break;
            }
        }
    }

    /**
     * Creates a Generator that will send the key to the generator as value.
     *
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self(Iter::keys($this));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return new self(Iter::map($this, $callback));
    }

    /**
     * Passes $this to the given callback and returns the result.
     *
     * @template TPipe
     * @param Closure($this): TPipe $callback
     * @return TPipe
     */
    public function pipe(Closure $callback)
    {
        return $callback($this);
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->instantiate(Iter::repeat($this, $times));
    }

    /**
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return static
     */
    public function replace(mixed $search, mixed $replacement, int &$count = 0): static
    {
        return $this->instantiate(Iter::replace($this, $search, $replacement, $count));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function takeFirst(int $amount): static
    {
        return $this->instantiate(Iter::takeFirst($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::takeUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::takeWhile($this, $condition));
    }

    /**
     * @param Closure(static): mixed $callback
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return Arr::from($this);
    }

    /**
     * @return Seq<TKey, TValue>
     */
    public function toSeq(): Seq
    {
        return new Seq($this);
    }

    /**
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self(Iter::values($this));
    }
}
