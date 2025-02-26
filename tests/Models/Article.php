<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\Casts\Money;
use OwenIt\Auditing\Tests\database\factories\ArticleFactory;

class Article extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected static string $factory = ArticleFactory::class;

    protected $laravel_version;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'reviewed' => 'bool',
        'config' => 'json',
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

    public $customClosure;

    public function __construct(array $attributes = [])
    {
        if (class_exists(\Illuminate\Database\Eloquent\Casts\AsArrayObject::class)) {
            $this->casts['config'] = \Illuminate\Database\Eloquent\Casts\AsArrayObject::class;
        }

        $this->customClosure = function () {};

        parent::__construct($attributes);
    }

    public function users()
    {
        return $this->morphToMany(User::class, 'model', 'model_has_users');
    }

    public function categories()
    {
        return $this->morphToMany(Category::class, 'model', 'model_has_categories');
    }

    /**
     * Uppercase Title accessor.
     */
    public function getTitleAttribute(string $value): string
    {
        return strtoupper($value);
    }

    /**
     * Uppercase Content accessor.
     */
    public function content(): Attribute
    {
        return new Attribute(
            function ($value) {
                return $value;
            },
            function ($value) {
                return ucwords($value);
            }
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
