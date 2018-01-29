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

namespace OwenIt\Auditing\Exceptions;

use Throwable;

class AuditableTransitionException extends AuditingException
{
    /**
     * Attribute incompatibilities.
     *
     * @var array
     */
    protected $incompatibilities = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($message = '', array $incompatibilities = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->incompatibilities = $incompatibilities;
    }

    /**
     * Get the attribute incompatibilities.
     *
     * @return array
     */
    public function getIncompatibilities(): array
    {
        return $this->incompatibilities;
    }
}
