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

namespace OwenIt\Auditing;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\AttributeEncoder;

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
    public function getConnectionName()
    {
        return Config::get('audit.drivers.database.connection');
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return Config::get('audit.drivers.database.table', parent::getTable());
    }

    /**
     * {@inheritdoc}
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function resolveData(): array
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        // Metadata
        $this->data = [
            'audit_id'         => $this->id,
            'audit_event'      => $this->event,
            'audit_url'        => $this->url,
            'audit_ip_address' => $this->ip_address,
            'audit_user_agent' => $this->user_agent,
            'audit_tags'       => $this->tags,
            'audit_created_at' => $this->serializeDate($this->created_at),
            'audit_updated_at' => $this->serializeDate($this->updated_at),
            'user_id'          => $this->getAttribute($morphPrefix.'_id'),
            'user_type'        => $this->getAttribute($morphPrefix.'_type'),
        ];

        if ($this->user) {
            foreach ($this->user->getArrayableAttributes() as $attribute => $value) {
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
     * Get the formatted value of an Eloquent model.
     *
     * @param Model  $model
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function getFormattedValue(Model $model, string $key, $value)
    {
        // Apply defined get mutator
        if ($model->hasGetMutator($key)) {
            return $model->mutateAttribute($key, $value);
        }

        // Cast to native PHP type
        if ($model->hasCast($key)) {
            return $model->castAttribute($key, $value);
        }

        // Honour DateTime attribute
        if ($value !== null && in_array($key, $model->getDates(), true)) {
            return $model->asDateTime($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataValue(string $key)
    {
        if (!array_key_exists($key, $this->data)) {
            return;
        }

        $value = $this->data[$key];

        // User value
        if ($this->user && starts_with($key, 'user_')) {
            return $this->getFormattedValue($this->user, substr($key, 5), $value);
        }

        // Auditable value
        if ($this->auditable && starts_with($key, ['new_', 'old_'])) {
            $attribute = substr($key, 4);

            return $this->getFormattedValue(
                $this->auditable,
                $attribute,
                $this->decodeAttributeValue($this->auditable, $attribute, $value)
            );
        }

        return $value;
    }

    /**
     * Decode attribute value.
     *
     * @param Contracts\Auditable $auditable
     * @param string              $attribute
     * @param mixed               $value
     *
     * @return mixed
     */
    protected function decodeAttributeValue(Contracts\Auditable $auditable, string $attribute, $value)
    {
        $attributeModifiers = $auditable->getAttributeModifiers();

        if (!array_key_exists($attribute, $attributeModifiers)) {
            return $value;
        }

        $attributeDecoder = $attributeModifiers[$attribute];

        if (is_subclass_of($attributeDecoder, AttributeEncoder::class)) {
            return call_user_func([$attributeDecoder, 'decode'], $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(bool $json = false, int $options = 0, int $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $metadata = [];

        foreach ($this->metadata as $key) {
            $value = $this->getDataValue($key);

            $metadata[$key] = $value instanceof DateTimeInterface
                ? $this->serializeDate($value)
                : $value;
        }

        return $json ? json_encode($metadata, $options, $depth) : $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getModified(bool $json = false, int $options = 0, int $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $modified = [];

        foreach ($this->modified as $key) {
            $attribute = substr($key, 4);
            $state = substr($key, 0, 3);

            $value = $this->getDataValue($key);

            $modified[$attribute][$state] = $value instanceof DateTimeInterface
                ? $this->serializeDate($value)
                : $value;
        }

        return $json ? json_encode($modified, $options, $depth) : $modified;
    }

    /**
     * Get the Audit tags as an array.
     *
     * @return array
     */
    public function getTags(): array
    {
        return preg_split('/,/', $this->tags, null, PREG_SPLIT_NO_EMPTY);
    }
}
