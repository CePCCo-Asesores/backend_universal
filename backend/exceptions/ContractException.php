<?php
declare(strict_types=1);

namespace Exceptions;

abstract class ContractException extends \RuntimeException
{
    /** @var array<string,mixed> */
    protected array $context;

    /**
     * @param string $message
     * @param array<string,mixed> $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /** @return array<string,mixed> */
    public function getContext(): array
    {
        return $this->context;
    }
}
