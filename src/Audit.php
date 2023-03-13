<?php

namespace OwenIt\Auditing;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        return $this->morphTo(__FUNCTION__, $morphPrefix . '_type', $morphPrefix . '_id');
    }

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
    public function resolveData(): array
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        // Metadata
        $this->data = [
            'audit_id'         => $this->id,
            'audit_event'      => $this->event,
            'audit_tags'       => $this->tags,
            'audit_created_at' => $this->serializeDate($this->{$this->getCreatedAtColumn()}),
            'audit_updated_at' => $this->serializeDate($this->{$this->getUpdatedAtColumn()}),
            'user_id'          => $this->getAttribute($morphPrefix . '_id'),
            'user_type'        => $this->getAttribute($morphPrefix . '_type'),
        ];

        // add resolvers data to metadata
        $resolverData = [];
        foreach (array_keys(Config::get('audit.resolvers', [])) as $name) {
            $resolverData['audit_' . $name] = $this->$name;
        }
        $this->data = array_merge($this->data, $resolverData);

        if ($this->user) {
            foreach ($this->user->getArrayableAttributes() as $attribute => $value) {
                $this->data['user_' . $attribute] = $value;
            }
        }

        $this->metadata = array_keys($this->data);

        // Modified Auditable attributes
        foreach ($this->new_values as $key => $value) {
            $this->data['new_' . $key] = $value;
        }

        foreach ($this->old_values as $key => $value) {
            $this->data['old_' . $key] = $value;
        }

        $this->modified = array_diff_key(array_keys($this->data), $this->metadata);

        return $this->data;
    }

    /**
     * Get the formatted value of an Eloquent model.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getFormattedValue(Model $model, string $key, $value)
    {
        // Apply defined get mutator
        if ($model->hasGetMutator($key)) {
            return $model->mutateAttribute($key, $value);
        }

        if (array_key_exists(
            $key,
            $model->getCasts()
        ) && $model->getCasts()[$key] == 'Illuminate\Database\Eloquent\Casts\AsArrayObject') {
            $arrayObject = new \Illuminate\Database\Eloquent\Casts\ArrayObject(json_decode($value, true));
            return $arrayObject;
        }

        // Cast to native PHP type
        if ($model->hasCast($key)) {
            if ($model->getCastType($key) == 'datetime' ) {
                $value = $this->castDatetimeUTC($model, $value);
            }

            return $model->castAttribute($key, $value);
        }

        // Honour DateTime attribute
        if ($value !== null && in_array($key, $model->getDates(), true)) {
            return $model->asDateTime($this->castDatetimeUTC($model, $value));
        }

        return $value;
    }

    private function castDatetimeUTC($model, $value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value, Date::now('UTC')->getTimezone())->startOfDay());
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d H:i:s', $value, Date::now('UTC')->getTimezone()));
        }

        try {
            return Date::createFromFormat($model->getDateFormat(), $value, Date::now('UTC')->getTimezone());
        } catch (InvalidArgumentException $e) {
            return $value;
        }
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
        if ($this->user && Str::startsWith($key, 'user_')) {
            return $this->getFormattedValue($this->user, substr($key, 5), $value);
        }

        // Auditable value
        if ($this->auditable && Str::startsWith($key, ['new_', 'old_'])) {
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
     * @param string $attribute
     * @param mixed $value
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
            $metadata[$key] = $value;

            if ($value instanceof DateTimeInterface) {
                $metadata[$key] = !is_null($this->auditable) ? $this->auditable->serializeDate($value) : $this->serializeDate($value);
            }
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
            $modified[$attribute][$state] = $value;

            if ($value instanceof DateTimeInterface) {
                $modified[$attribute][$state] = !is_null($this->auditable) ? $this->auditable->serializeDate($value) : $this->serializeDate($value);
            }
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
