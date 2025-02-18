<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\database\factories\UserFactory;

class User extends Model implements Auditable, Authenticatable
{
    use HasFactory;
    use \Illuminate\Auth\Authenticatable;
    use \OwenIt\Auditing\Auditable;

    protected static string $factory = UserFactory::class;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_admin' => 'bool',
    ];

    /**
     * Uppercase first name character accessor.
     */
    public function getFirstNameAttribute(string $value): string
    {
        return ucfirst($value);
    }
    
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')->using(GroupMember::class)->withPivot('id','role');
    }
}
