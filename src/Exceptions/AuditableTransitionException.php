<?php

namespace OwenIt\Auditing\Exceptions;

use Throwable;

class AuditableTransitionException extends AuditingException
{
    /**
     * Attribute incompatibilities.
     *
     * @var array<string>
     */
    protected $incompatibilities = [];

    /**
     * @param  array<string>  $incompatibilities
     */
    public function __construct(string $message = '', array $incompatibilities = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->incompatibilities = $incompatibilities;
    }

    /**
     * Get the attribute incompatibilities.
     *
     * @return array<string>
     */
    public function getIncompatibilities(): array
    {
        return $this->incompatibilities;
    }
}
