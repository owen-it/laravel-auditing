<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Concerns\CanTransition;
use OwenIt\Auditing\Concerns\DeterminesAttributesToAudit;
use OwenIt\Auditing\Concerns\GathersDataToAudit;
use OwenIt\Auditing\Contracts\AttributeEncoder;
use OwenIt\Auditing\Contracts\AttributeRedactor;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Exceptions\AuditingException;

trait Auditable
{
    use DeterminesAttributesToAudit;
    use GathersDataToAudit;
    use CanTransition;

    /**
     * Audit event name.
     *
     * @var string
     */
    public $auditEvent;

    /**
     * Is auditing disabled?
     *
     * @var bool
     */
    public static $auditingDisabled = false;

    /**
     * Property may set custom event data to register
     * @var null|array
     */
    public $auditCustomOld = null;

    /**
     * Property may set custom event data to register
     * @var null|array
     */
    public $auditCustomNew = null;

    /**
     * If this is a custom event (as opposed to an eloquent event
     * @var bool
     */
    public $isCustomEvent = false;

    /**
     * Auditable boot logic.
     *
     * @return void
     */
    public static function bootAuditable()
    {
        if (static::isAuditingEnabled()) {
            static::observe(new AuditableObserver());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(
            Config::get('audit.implementation', Models\Audit::class),
            'auditable'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function readyForAuditing(): bool
    {
        if (static::$auditingDisabled) {
            return false;
        }

        if ($this->isCustomEvent) {
            return true;
        }

        return $this->isEventAuditable($this->auditEvent);
    }

    /**
     * Modify attribute value.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return mixed
     * @throws AuditingException
     *
     */
    protected function modifyAttributeValue(string $attribute, $value)
    {
        $attributeModifiers = $this->getAttributeModifiers();

        if (!array_key_exists($attribute, $attributeModifiers)) {
            return $value;
        }

        $attributeModifier = $attributeModifiers[$attribute];

        if (is_subclass_of($attributeModifier, AttributeRedactor::class)) {
            return call_user_func([$attributeModifier, 'redact'], $value);
        }

        if (is_subclass_of($attributeModifier, AttributeEncoder::class)) {
            return call_user_func([$attributeModifier, 'encode'], $value);
        }

        throw new AuditingException(sprintf('Invalid AttributeModifier implementation: %s', $attributeModifier));
    }

    /**
     * Determine whether an event is auditable.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function isEventAuditable($event): bool
    {
        return is_string($this->resolveAttributeGetter($event));
    }

    /**
     * {@inheritdoc}
     */
    public function setAuditEvent(string $event): Contracts\Auditable
    {
        $this->auditEvent = $this->isEventAuditable($event) ? $event : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvent()
    {
        return $this->auditEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvents(): array
    {
        return $this->auditEvents ?? Config::get('audit.events', [
            'created',
            'updated',
            'deleted',
            'restored',
        ]);
    }

    /**
     * Disable Auditing.
     *
     * @return void
     */
    public static function disableAuditing()
    {
        static::$auditingDisabled = true;
    }

    /**
     * Enable Auditing.
     *
     * @return void
     */
    public static function enableAuditing()
    {
        static::$auditingDisabled = false;
    }

    /**
     * Determine whether auditing is enabled.
     *
     * @return bool
     */
    public static function isAuditingEnabled(): bool
    {
        if (App::runningInConsole()) {
            return Config::get('audit.enabled', true) && Config::get('audit.console', false);
        }

        return Config::get('audit.enabled', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditStrict(): bool
    {
        return $this->auditStrict ?? Config::get('audit.strict', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditDriver()
    {
        return $this->auditDriver ?? Config::get('audit.driver', 'database');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditThreshold(): int
    {
        return $this->auditThreshold ?? Config::get('audit.threshold', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeModifiers(): array
    {
        return $this->attributeModifiers ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function generateTags(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Pivot help methods
    |--------------------------------------------------------------------------
    |
    | Methods for auditing pivot actions
    |
    */

    /**
     * @param string $relationName
     * @param mixed $id
     * @param array $attributes
     * @param bool $touch
     * @return void
     * @throws AuditingException
     */
    public function auditAttach(string $relationName, $id, array $attributes = [], $touch = true, $columns = ['name'])
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'attach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method attach');
        }
        $this->auditEvent = 'attach';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        $this->{$relationName}()->attach($id, $attributes, $touch);
        $this->auditCustomNew = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;
    }

    /**
     * @param string $relationName
     * @param mixed $ids
     * @param bool $touch
     * @return int
     * @throws AuditingException
     */
    public function auditDetach(string $relationName, $ids = null, $touch = true)
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'detach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method detach');
        }

        $this->auditEvent = 'detach';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        $results = $this->{$relationName}()->detach($ids, $touch);
        $this->auditCustomNew = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;

        return empty($results) ? 0 : $results;
    }

    /**
     * @param $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param bool $detaching
     * @param bool $skipUnchanged
     * @return array
     * @throws AuditingException
     */
    public function auditSync($relationName, $ids, $detaching = true)
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'sync')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method sync');
        }

        $this->auditEvent = 'sync';

        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];

        $changes = $this->{$relationName}()->sync($ids, $detaching);

        if (collect($changes)->flatten()->isEmpty()) {
            $this->auditCustomOld = [];
            $this->auditCustomNew = [];
        } else {
            $this->auditCustomNew = [
                $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
            ];
        }

        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;

        return $changes;
    }

    /**
     * @param string $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param bool $skipUnchanged
     * @return array
     * @throws AuditingException
     */
    public function auditSyncWithoutDetaching(string $relationName, $ids)
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'syncWithoutDetaching')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method syncWithoutDetaching');
        }

        return $this->auditSync($relationName, $ids, false);
    }
}
