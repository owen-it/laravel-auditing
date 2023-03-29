<?php

namespace OwenIt\Auditing\Contracts;

interface Audit
{
    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable();

    /**
     * Get the auditable model to which this Audit belongs.
     *
     * @return mixed
     */
    public function auditable();

    /**
     * User responsible for the changes.
     *
     * @return mixed
     */
    public function user();

    /**
     * Audit data resolver.
     */
    public function resolveData(): array;

    /**
     * Get an Audit data value.
     *
     *
     * @return mixed
     */
    public function getDataValue(string $key);

    /**
     * Get the Audit metadata.
     *
     *
     * @return array|string
     */
    public function getMetadata(bool $json = false, int $options = 0, int $depth = 512);

    /**
     * Get the Auditable modified attributes.
     *
     *
     * @return array|string
     */
    public function getModified(bool $json = false, int $options = 0, int $depth = 512);
}
