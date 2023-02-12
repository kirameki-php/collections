<?php declare(strict_types=1);

namespace SouthPointe\Collections\Exceptions;

use SouthPointe\Collections\Utils\Arr;
use SouthPointe\Core\Exceptions\LogicException;
use Throwable;
use function is_string;

class MissingKeyException extends LogicException
{
    /**
     * @param array<int, array-key> $missingKeys
     * @param iterable<string, mixed>|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        array $missingKeys,
        ?iterable $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $missingFormatted = Arr::map($missingKeys, fn($k) => is_string($k) ? "'{$k}'" : $k);
        $missingJoined = Arr::join($missingFormatted, ', ', '[', ']');
        $message = "Array keys: {$missingJoined} did not exist.";

        parent::__construct($message, $context, $code, $previous);
    }
}
