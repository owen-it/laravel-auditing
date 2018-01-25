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

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \OwenIt\Auditing\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
