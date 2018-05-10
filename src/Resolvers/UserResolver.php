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
use Illuminate\Support\Facades\Config;

class UserResolver implements \OwenIt\Auditing\Contracts\UserResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        $guards = Config::get('audit.user.guards', [
            'web',
            'api',
        ]);

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }
    }
}
