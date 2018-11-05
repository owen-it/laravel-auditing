<?php

namespace OwenIt\Auditing\Contracts;

interface AttributeRedactor extends AttributeModifier
{
    /**
     * Redact an attribute value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function redact($value): string;
}
