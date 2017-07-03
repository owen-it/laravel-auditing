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

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\Config;

trait Audit
{
    /**
     * Audit data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The Audit attributes that belong to the metadata.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The Auditable attributes that were modified.
     *
     * @var array
     */
    protected $modified = [];

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return static::resolveConnection(Config::get('audit.drivers.database.connection'));
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return Config::get('audit.drivers.database.table', parent::getTable());
    }

    /**
     * {@inheritdoc}
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        return $this->belongsTo(
            Config::get('audit.user.model'),
            Config::get('audit.user.foreign_key', 'user_id'),
            Config::get('audit.user.primary_key', 'id')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolveData()
    {
        // Metadata
        $this->data = [
            'audit_id'         => $this->id,
            'audit_event'      => $this->event,
            'audit_url'        => $this->url,
            'audit_ip_address' => $this->ip_address,
            'audit_user_agent' => $this->user_agent,
            'audit_created_at' => $this->serializeDate($this->created_at),
            'audit_updated_at' => $this->serializeDate($this->updated_at),
            'user_id'          => $this->getAttribute(Config::get('audit.user.foreign_key', 'user_id')),
        ];

        if ($this->relationLoaded('user')) {
            foreach ($this->user->attributesToArray() as $attribute => $value) {
                $this->data['user_'.$attribute] = $value;
            }
        }

        $this->metadata = array_keys($this->data);

        // Modified Auditable attributes
        foreach ($this->new_values as $key => $value) {
            $this->data['new_'.$key] = $value;
        }

        foreach ($this->old_values as $key => $value) {
            $this->data['old_'.$key] = $value;
        }

        $this->modified = array_diff_key(array_keys($this->data), $this->metadata);

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataValue($key)
    {
        if (!array_key_exists($key, $this->data)) {
            return;
        }

        $value = $this->data[$key];

        // Apply a mutator or a cast the Auditable model may have defined
        if ($this->auditable && starts_with($key, ['new_', 'old_'])) {
            $originalKey = substr($key, 4);

            if ($this->auditable->hasGetMutator($originalKey)) {
                return $this->auditable->mutateAttribute($originalKey, $value);
            }

            if ($this->auditable->hasCast($originalKey)) {
                return $this->auditable->castAttribute($originalKey, $value);
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($json = false, $options = 0, $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $metadata = [];

        foreach ($this->metadata as $key) {
            $metadata[$key] = $this->getDataValue($key);
        }

        return $json ? json_encode($metadata, $options, $depth) : $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getModified($json = false, $options = 0, $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $modified = [];

        foreach ($this->modified as $key) {
            $attribute = substr($key, 4);
            $state = substr($key, 0, 3);

            $modified[$attribute][$state] = $this->getDataValue($key);
        }

        return $json ? json_encode($modified, $options, $depth) : $modified;
    }
}
