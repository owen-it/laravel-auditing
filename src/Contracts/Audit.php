<?php

namespace OwenIt\Auditing\Contracts;

interface Audit
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|static
     */
    public function newEloquentBuilder($query);
    
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
