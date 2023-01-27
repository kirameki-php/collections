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
class LazySeq extends Seq
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
        return self::fromClosure(function () use ($size) {
            foreach (Iter::chunk($this, $size) as $key => $chunk) {
                yield $key => $this->newInstance($chunk);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function dropFirst(int $amount): static
    {
        return $this->newInstance(Iter::dropFirst($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::dropUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::dropWhile($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function each(Closure $callback): static
    {
        return self::fromClosure(function () use ($callback) {
            foreach ($this as $key => $item) {
                $callback($item, $key);
                yield $key => $item;
            }
        });
    }

    /**
     * @return Vec<TValue>
     */
    public function eager(): Vec
    {
        return new Vec($this->values()->toArray());
    }

    /**
     * @inheritDoc
     */
    public function filter(Closure $condition): static
    {
        return $this->newInstance(Iter::filter($this, $condition));
    }

    /**
     * @return LazySeq<int, TKey>
     */
    public function keys(): self
    {
        return new static(Iter::keys($this));
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return LazySeq<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return new static(Iter::map($this, $callback));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Iter::repeat($this, $times));
    }

    /**
     * @inheritDoc
     */
    public function takeFirst(int $amount): static
    {
        return $this->newInstance(Iter::takeFirst($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::takeUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::takeWhile($this, $condition));
    }

    /**
     * @return static<int, TValue>
     */
    public function values(): self
    {
        return new static(Iter::values($this));
    }
}
