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

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableStub extends Model implements AuditableContract
{
    use Auditable;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'published' => 'bool',
    ];

    /**
     * {@inheritdoc}
     */
    public function resolveIpAddress()
    {
        return '127.0.0.1';
    }

    /**
     * {@inheritdoc}
     */
    public function resolveUserAgent()
    {
        return 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0';
    }

    /**
     * Uppercase Title accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return strtoupper($value);
    }

    protected function auditCustomAttributes(array &$old, array &$new)
    {
    }

    protected function myAttributesMethod(array &$old, array &$new)
    {
    }
}
