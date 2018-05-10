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

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Audit
{
    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Get the auditable model to which this Audit belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function auditable(): MorphTo;

    /**
     * User responsible for the changes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user(): MorphTo;

    /**
     * Audit data resolver.
     *
     * @return array
     */
    public function resolveData(): array;

    /**
     * Get an Audit data value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getDataValue(string $key);

    /**
     * Get the Audit metadata.
     *
     * @param bool $json
     * @param int  $options
     * @param int  $depth
     *
     * @return array|string
     */
    public function getMetadata(bool $json = false, int $options = 0, int $depth = 512);

    /**
     * Get the Auditable modified attributes.
     *
     * @param bool $json
     * @param int  $options
     * @param int  $depth
     *
     * @return array|string
     */
    public function getModified(bool $json = false, int $options = 0, int $depth = 512);
}
