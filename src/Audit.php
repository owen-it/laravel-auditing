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

        if ($this->user) {
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

    /**
     * @return return all audits that were related to $this
     */
    public function getRelatedAudits()
    {
        if($this->event == 'related')
        {
            return null;
        }
        $audit_class = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);
        $RelatedAuditObjArr = $audit_class::where('relation_id', '=', $this->relation_id)
                                           ->where('event', '=', 'related')->get();
        return $RelatedAuditObjArr;
    }

    /**
     * Get the relating Audit
     *
     * @return mixed
     */
    public function getRelatingAudit()
    {
        $audit_class = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);
        if($this->event !== 'related')
        {
            return null;
        }
        /** @var \OwenIt\Auditing\Models\Audit $RelatingAuditObj */
        $RelatingAuditObj = $audit_class::where('relation_id', '=', $this->relation_id)
                                           ->where('event', '!=', 'related')->get()->first();
        $RelatingAuditObj->setIsRelating(true);
        return $RelatingAuditObj;
    }

    public   $is_relating = false;

    /**
     * @return bool
     */
    public function isRelating(): bool
    {
        return $this->is_relating;
    }

    /**
     * @param bool $is_relating
     */
    public function setIsRelating($is_relating)
    {
        $this->is_relating = $is_relating;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'auditable_id' => $this->auditable_id,
            'auditable_type' => $this->auditable_type,
            'created_at' => $this->created_at,
            'event' => $this->event,
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'new_values' => $this->new_values,
            'old_values' => $this->old_values,
            'relation_id' => $this->relation_id,
            'updated_at' => $this->updated_at,
            'url' => $this->url,
            'user_agent' => $this->user_agent,
            'user_id' => $this->user_id,
           'related_audits' => $this->event !== 'related' && ! $this->isRelating() && $this->getRelatedAudits() ? $this->getRelatedAudits()->toArray() : null,
            'relating_audit' => $this->event == 'related' && ! $this->isRelating() && $this->getRelatingAudit() ? $this->getRelatingAudit()->toArray() : null,
        ];
    }
}
