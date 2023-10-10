<?php

namespace OwenIt\Auditing\Tests\Models;

use NumberFormatter;

final class Money
{
    /** Formatted value. */
    public string $formatted;
    /** Value */
    public string $amount;
    /** Format */
    public string $currency;

    /**
     * Create a new money instance.
     */
    public function __construct(string $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $this->formatted = $formatter->formatCurrency($this->amount, $this->currency);
    }
}

