<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Contracts\AttributeEncoder;
use OwenIt\Auditing\Contracts\AttributeRedactor;
use OwenIt\Auditing\Contracts\Resolver;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Exceptions\AuditableTransitionException;
use OwenIt\Auditing\Exceptions\AuditingException;

trait Auditable
{


    /**
     * Auditable attributes excluded from the Audit.
     *
     * @var array
     */
    protected $excludedAttributes = [];

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
        if (!self::$auditingDisabled && static::isAuditingEnabled()) {
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
     * Resolve the Auditable attributes to exclude from the Audit.
     *
     * @return void
     */
    protected function resolveAuditExclusions()
    {
        $this->excludedAttributes = $this->getAuditExclude();

        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->getAuditStrict()) {
            // Hidden attributes
            $this->excludedAttributes = array_merge($this->excludedAttributes, $this->hidden);

            // Non visible attributes
            if ($this->visible) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);

                $this->excludedAttributes = array_merge($this->excludedAttributes, $invisible);
            }
        }

        // Exclude Timestamps
        if (!$this->getAuditTimestamps()) {
            array_push($this->excludedAttributes, $this->getCreatedAtColumn(), $this->getUpdatedAtColumn());

            if (in_array(SoftDeletes::class, class_uses_recursive(get_class($this)))) {
                $this->excludedAttributes[] = $this->getDeletedAtColumn();
            }
        }

        // Valid attributes are all those that made it out of the exclusion array
        $attributes = Arr::except($this->attributes, $this->excludedAttributes);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (is_array($value) || (is_object($value) && !method_exists($value, '__toString'))) {
                $this->excludedAttributes[] = $attribute;
            }
        }
    }

    /**
     * @return array
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? Config::get('audit.exclude', []);
    }

    /**
     * @return array
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * Get the old/new attributes of a retrieved event.
     *
     * @return array
     */
    protected function getRetrievedEventAttributes(): array
    {
        // This is a read event with no attribute changes,
        // only metadata will be stored in the Audit

        return [
            [],
            [],
        ];
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    protected function getCreatedEventAttributes(): array
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return [
            [],
            $new,
        ];
    }

    protected function getCustomEventAttributes(): array
    {
        return [
            $this->auditCustomOld,
            $this->auditCustomNew
        ];
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return array
     */
    protected function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return [
            $old,
            $new,
        ];
    }

    /**
     * Get the old/new attributes of a deleted event.
     *
     * @return array
     */
    protected function getDeletedEventAttributes(): array
    {
        $old = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = $value;
            }
        }

        return [
            $old,
            [],
        ];
    }

    /**
     * Get the old/new attributes of a restored event.
     *
     * @return array
     */
    protected function getRestoredEventAttributes(): array
    {
        // A restored event is just a deleted event in reverse
        return array_reverse($this->getDeletedEventAttributes());
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
     * {@inheritdoc}
     */
    public function toAudit(): array
    {
        if (!$this->readyForAuditing()) {
            throw new AuditingException('A valid audit event has not been set');
        }

        $attributeGetter = $this->resolveAttributeGetter($this->auditEvent);

        if (!method_exists($this, $attributeGetter)) {
            throw new AuditingException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $attributeGetter
            ));
        }

        $this->resolveAuditExclusions();

        list($old, $new) = $this->$attributeGetter();

        if ($this->getAttributeModifiers() && !$this->isCustomEvent) {
            foreach ($old as $attribute => $value) {
                $old[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }

            foreach ($new as $attribute => $value) {
                $new[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }
        }

        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        $tags = implode(',', $this->generateTags());

        $user = $this->resolveUser();

        return $this->transformAudit(array_merge([
            'old_values'           => $old,
            'new_values'           => $new,
            'event'                => $this->auditEvent,
            'auditable_id'         => $this->getKey(),
            'auditable_type'       => $this->getMorphClass(),
            $morphPrefix . '_id'   => $user ? $user->getAuthIdentifier() : null,
            $morphPrefix . '_type' => $user ? $user->getMorphClass() : null,
            'tags'                 => empty($tags) ? null : $tags,
        ], $this->runResolvers()));
    }

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        return $data;
    }

    /**
     * Resolve the User.
     *
     * @return mixed|null
     * @throws AuditingException
     *
     */
    protected function resolveUser()
    {
        $userResolver = Config::get('audit.user.resolver');

        if (is_subclass_of($userResolver, \OwenIt\Auditing\Contracts\UserResolver::class)) {
            return call_user_func([$userResolver, 'resolve']);
        }

        throw new AuditingException('Invalid UserResolver implementation');
    }

    protected function runResolvers(): array
    {
        $resolved = [];
        foreach (Config::get('audit.resolvers', []) as $name => $implementation) {
            if (empty($implementation)) {
                continue;
            }

            if (!is_subclass_of($implementation, Resolver::class)) {
                throw new AuditingException('Invalid Resolver implementation for: ' . $name);
            }
            $resolved[$name] = call_user_func([$implementation, 'resolve'], $this);
        }
        return $resolved;
    }

    /**
     * Determine if an attribute is eligible for auditing.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isAttributeAuditable(string $attribute): bool
    {
        // The attribute should not be audited
        if (in_array($attribute, $this->excludedAttributes, true)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        $include = $this->getAuditInclude();

        return empty($include) || in_array($attribute, $include, true);
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
     * Attribute getter method resolver.
     *
     * @param string $event
     *
     * @return string|null
     */
    protected function resolveAttributeGetter($event)
    {
        if (empty($event)) {
            return;
        }

        if ($this->isCustomEvent) {
            return 'getCustomEventAttributes';
        }

        foreach ($this->getAuditEvents() as $key => $value) {
            $auditableEvent = is_int($key) ? $value : $key;

            $auditableEventRegex = sprintf('/%s/', preg_replace('/\*+/', '.*', $auditableEvent));

            if (preg_match($auditableEventRegex, $event)) {
                return is_int($key) ? sprintf('get%sEventAttributes', ucfirst($event)) : $value;
            }
        }
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
    public function getAuditTimestamps(): bool
    {
        return $this->auditTimestamps ?? Config::get('audit.timestamps', false);
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

    /**
     * {@inheritdoc}
     */
    public function transitionTo(Contracts\Audit $audit, bool $old = false): Contracts\Auditable
    {
        // The Audit must be for an Auditable model of this type
        if ($this->getMorphClass() !== $audit->auditable_type) {
            throw new AuditableTransitionException(sprintf(
                'Expected Auditable type %s, got %s instead',
                $this->getMorphClass(),
                $audit->auditable_type
            ));
        }

        // The Audit must be for this specific Auditable model
        if ($this->getKey() !== $audit->auditable_id) {
            throw new AuditableTransitionException(sprintf(
                'Expected Auditable id %s, got %s instead',
                $this->getKey(),
                $audit->auditable_id
            ));
        }

        // Redacted data should not be used when transitioning states
        foreach ($this->getAttributeModifiers() as $attribute => $modifier) {
            if (is_subclass_of($modifier, AttributeRedactor::class)) {
                throw new AuditableTransitionException('Cannot transition states when an AttributeRedactor is set');
            }
        }

        // The attribute compatibility between the Audit and the Auditable model must be met
        $modified = $audit->getModified();

        if ($incompatibilities = array_diff_key($modified, $this->getAttributes())) {
            throw new AuditableTransitionException(sprintf(
                'Incompatibility between [%s:%s] and [%s:%s]',
                $this->getMorphClass(),
                $this->getKey(),
                get_class($audit),
                $audit->getKey()
            ), array_keys($incompatibilities));
        }

        $key = $old ? 'old' : 'new';

        foreach ($modified as $attribute => $value) {
            if (array_key_exists($key, $value)) {
                $this->setAttribute($attribute, $value[$key]);
            }
        }

        return $this;
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
        return empty($results) ? 0 : $results;
    }

    /**
     * @param $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param bool $detaching
     * @return array
     * @throws AuditingException
     */
    public function auditSync($relationName, $ids, $detaching = true)
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'sync')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method sync');
        }

        $this->auditEvent = 'sync';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        $changes = $this->{$relationName}()->sync($ids, $detaching);
        $this->auditCustomNew = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray()
        ];
        Event::dispatch(AuditCustom::class, [$this]);
        return $changes;
    }

    /**
     * @param string $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
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
