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

class UserResolver implements \OwenIt\Auditing\Contracts\UserResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return \Config::get('audit.morphable', false) ? static::resolveMorphable() : static::resolveSingle();
    }

    /**
     * Resolves the user when not morphable
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private static function resolveSingle()
    {
        return Auth::check() ? Auth::user() : null;
    }

    /**
     * Resolves the user when morphable
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private static function resolveMorphable()
    {
        $guards = \Config::get('audit.guards', ['web']);

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        if (Auth::check()) {
            return Auth::user();
        }

        return null;
    }
}
