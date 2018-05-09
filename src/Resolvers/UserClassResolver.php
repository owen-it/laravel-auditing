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

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Exceptions\AuditingException;

class UserClassResolver implements \OwenIt\Auditing\Contracts\UserClassResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return static::resolveUser() ? static::resolveUser()->getMorphClass() : null;
    }

    protected static function resolveUser()
    {
        $userResolver = \Config::get('audit.resolver.user');

        if (is_subclass_of($userResolver, \OwenIt\Auditing\Contracts\UserResolver::class)) {
            return call_user_func([$userResolver, 'resolve']);
        }

        throw new AuditingException('Invalid UserResolver implementation');
    }
}
