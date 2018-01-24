<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2018
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\UserResolver;

class User extends Model implements Auditable, UserResolver
{
    use \OwenIt\Auditing\Auditable;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_admin' => 'bool',
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolveId()
    {
        return static::count() > 0 ? 1 : null;
    }

    /**
     * Uppercase first name character accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getFirstNameAttribute(string $value): string
    {
        return ucfirst($value);
    }
}
