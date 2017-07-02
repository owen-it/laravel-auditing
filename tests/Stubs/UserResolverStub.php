<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests\Stubs;

use OwenIt\Auditing\Contracts\UserResolver;

class UserResolverStub implements UserResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolveId()
    {
        return rand(1, 256);
    }
}
