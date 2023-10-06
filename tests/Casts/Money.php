<?php

namespace OwenIt\Auditing\Tests\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Tests\Models\Money as MoneyValueObject;

class Money implements CastsAttributes
{
    /**
     * {@inheritdoc}
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): MoneyValueObject
    {
        return new MoneyValueObject($value, 'USD');
    }

    /**
     * {@inheritdoc}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
