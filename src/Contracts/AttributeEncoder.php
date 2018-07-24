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

namespace OwenIt\Auditing\Contracts;

interface AttributeEncoder extends AttributeModifier
{
    /**
     * Encode an attribute value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function encode($value);

    /**
     * Decode an attribute value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function decode($value);
}
