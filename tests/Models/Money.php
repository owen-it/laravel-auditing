<?php

namespace OwenIt\Auditing\Tests\Models;

use NumberFormatter;

final class Money
{
    /**
     * Formatted value.
     */
    public string $formatted;

    /**
     * Create a new money instance.
     */
    public function __construct(
        public string $amount,
        public string $currency,
    ) {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $this->formatted = $formatter->formatCurrency($this->amount, $this->currency);
    }
}

