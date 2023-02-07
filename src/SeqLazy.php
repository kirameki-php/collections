<?php declare(strict_types=1);

namespace SouthPointe\Collections;

use Closure;
use Generator;
use SouthPointe\Collections\Utils\Iter;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Seq<TKey, TValue>
 */
class SeqLazy extends Seq
{
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
     * @inheritDoc
     */
    public function dropFirst(int $amount): static
    {
        return $this->instantiate(Iter::dropFirst($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function each(Closure $callback): static
    {
        return self::fromClosure(function() use ($callback) {
            foreach ($this as $key => $item) {
                $callback($item, $key);
                yield $key => $item;
            }
        });
    }

    /**
     * @return Seq<TKey, TValue>
     */
    public function eager(): Seq
    {
        return new Seq($this);
    }

    /**
     * @inheritDoc
     */
    public function filter(Closure $condition): static
    {
        return $this->instantiate(Iter::filter($this, $condition));
    }

    /**
     * @inheritDoc
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return $this->newSeqLazy(Iter::keys($this));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newSeqLazy(Iter::map($this, $callback));
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
     * @inheritDoc
     */
    public function takeFirst(int $amount): static
    {
        return $this->instantiate(Iter::takeFirst($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::takeUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::takeWhile($this, $condition));
    }

    /**
     * @return static<int, TValue>
     */
    public function values(): self
    {
        return new static(Iter::values($this));
    }

    /**
     * @template TNewKey of array-key|class-string
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $iterable
     * @return self<TNewKey, TNewValue>
     */
    public function newSeqLazy(iterable $iterable): self
    {
        return new self($iterable);
    }
}
