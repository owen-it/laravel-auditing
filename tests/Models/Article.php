<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\Casts\Money;

class Article extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $laravel_version;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'reviewed' => 'bool',
        'config'   => 'json',
        'published_at' => 'datetime',
        'price' => Money::class,
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'published_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'reviewed',
    ];

    public function __construct(array $attributes = [])
    {
        if (class_exists(\Illuminate\Database\Eloquent\Casts\AsArrayObject::class)) {
            $this->casts['config'] = \Illuminate\Database\Eloquent\Casts\AsArrayObject::class;
        }

        parent::__construct($attributes);
    }

    public function categories()
    {
        return $this->morphToMany(Category::class, 'model', 'model_has_categories');
    }

    /**
     * Uppercase Title accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getTitleAttribute(string $value): string
    {
        return strtoupper($value);
    }

    /**
     * Uppercase Content accessor.
     *
     * @return Attribute
     */
    public function content(): Attribute
    {
        return new Attribute(
            function ($value) { return $value; },
            function ($value) { return ucwords($value); }
        );
    }

    public static function contentMutate($value)
    {
        if (! method_exists(self::class, 'hasAttributeMutator')) {
            return $value;
        }

        return ucwords($value);
    }
}
