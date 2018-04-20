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

namespace OwenIt\Auditing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \OwenIt\Auditing\Contracts\AuditDriver auditDriver(\OwenIt\Auditing\Contracts\Auditable $model);
 * @method static void execute(\OwenIt\Auditing\Contracts\Auditable $model);
 */
class Auditor extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \OwenIt\Auditing\Contracts\Auditor::class;
    }
}
