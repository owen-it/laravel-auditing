<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Contracts\AttributeEncoder;
use OwenIt\Auditing\Contracts\AttributeRedactor;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;
use OwenIt\Auditing\Contracts\Resolver;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Exceptions\AuditableTransitionException;
use OwenIt\Auditing\Exceptions\AuditingException;

// @phpstan-ignore trait.unused
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
     *
     * @var null|array
     */
    public $auditCustomOld = null;

    /**
     * Property may set custom event data to register
     *
     * @var null|array
     */
    public $auditCustomNew = null;

    /**
     * If this is a custom event (as opposed to an eloquent event
     *
     * @var bool
     */
    public $isCustomEvent = false;

    /**
     * @var array Preloaded data to be used by resolvers
     */
    public $preloadedResolverData = [];

    /**
     * Auditable boot logic.
     *
     * @return void
     */
    public static function bootAuditable()
    {
        if (App::getFacadeRoot() && static::isAuditingEnabled()) {
            static::observe(new AuditableObserver);
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
        if (! $this->getAuditTimestamps()) {
            if ($this->getCreatedAtColumn()) {
                $this->excludedAttributes[] = $this->getCreatedAtColumn();
            }
            if ($this->getUpdatedAtColumn()) {
                $this->excludedAttributes[] = $this->getUpdatedAtColumn();
            }
            if (in_array(SoftDeletes::class, class_uses_recursive(get_class($this)))) {
                $this->excludedAttributes[] = $this->getDeletedAtColumn();
            }
        }

        // Valid attributes are all those that made it out of the exclusion array
        $attributes = Arr::except($this->attributes, $this->excludedAttributes);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (
                (is_array($value) && ! Config::get('audit.allowed_array_values', false)) ||
                (is_object($value) &&
                    ! method_exists($value, '__toString') &&
                    ! ($value instanceof \UnitEnum))
            ) {
                $this->excludedAttributes[] = $attribute;
            }
        }
    }

    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? Config::get('audit.exclude', []);
    }

    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * Get the old/new attributes of a retrieved event.
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
            $this->auditCustomNew,
        ];
    }

    /**
     * Get the old/new attributes of an updated event.
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
        if (static::$auditingDisabled || Models\Audit::$auditingGloballyDisabled) {
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
     * @param  mixed  $value
     * @return mixed
     *
     * @throws AuditingException
     */
    protected function modifyAttributeValue(string $attribute, $value)
    {
        $attributeModifiers = $this->getAttributeModifiers();

        if (! array_key_exists($attribute, $attributeModifiers)) {
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
        if (! $this->readyForAuditing()) {
            throw new AuditingException('A valid audit event has not been set');
        }

        $attributeGetter = $this->resolveAttributeGetter($this->auditEvent);

        if (! method_exists($this, $attributeGetter)) {
            throw new AuditingException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $attributeGetter
            ));
        }

        $this->resolveAuditExclusions();

        [$old, $new] = $this->$attributeGetter();

        if ($this->getAttributeModifiers() && ! $this->isCustomEvent) {
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
            'old_values' => $old,
            'new_values' => $new,
            'event' => $this->auditEvent,
            'auditable_id' => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            $morphPrefix.'_id' => $user ? $user->getAuthIdentifier() : null,
            $morphPrefix.'_type' => $user ? $user->getMorphClass() : null,
            'tags' => empty($tags) ? null : $tags,
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
     *
     * @throws AuditingException
     */
    protected function resolveUser()
    {
        if (! empty($this->preloadedResolverData['user'] ?? null)) {
            return $this->preloadedResolverData['user'];
        }

        $userResolver = Config::get('audit.user.resolver');

        if (is_subclass_of($userResolver, \OwenIt\Auditing\Contracts\UserResolver::class)) {
            return call_user_func([$userResolver, 'resolve'], $this);
        }

        throw new AuditingException('Invalid UserResolver implementation');
    }

    protected function runResolvers(): array
    {
        $resolved = [];
        $resolvers = Config::get('audit.resolvers', []);
        if (empty($resolvers) && Config::has('audit.resolver')) {
            throw new AuditingException(
                'The config file audit.php is not updated. Please see https://laravel-auditing.com/guide/upgrading.html'
            );
        }

        foreach ($resolvers as $name => $implementation) {
            if (empty($implementation)) {
                continue;
            }

            if (! is_subclass_of($implementation, Resolver::class)) {
                throw new AuditingException('Invalid Resolver implementation for: '.$name);
            }
            $resolved[$name] = call_user_func([$implementation, 'resolve'], $this);
        }

        return $resolved;
    }

    public function preloadResolverData()
    {
        $this->preloadedResolverData = $this->runResolvers();

        $user = $this->resolveUser();
        if (! empty($user)) {
            $this->preloadedResolverData['user'] = $user;
        }

        return $this;
    }

    /**
     * Determine if an attribute is eligible for auditing.
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
     * @param  string  $event
     */
    protected function isEventAuditable($event): bool
    {
        return is_string($this->resolveAttributeGetter($event));
    }

    /**
     * Attribute getter method resolver.
     *
     * @param  string  $event
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
     * Is Auditing disabled.
     */
    public static function isAuditingDisabled(): bool
    {
        return static::$auditingDisabled || Models\Audit::$auditingGloballyDisabled;
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
     * Execute a callback while auditing is disabled.
     *
     *
     * @return mixed
     */
    public static function withoutAuditing(callable $callback, bool $globally = false)
    {
        $auditingDisabled = static::$auditingDisabled;

        static::disableAuditing();
        Models\Audit::$auditingGloballyDisabled = $globally;

        try {
            return $callback();
        } finally {
            Models\Audit::$auditingGloballyDisabled = false;
            static::$auditingDisabled = $auditingDisabled;
        }
    }

    /**
     * Determine whether auditing is enabled.
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
                'Expected Auditable id (%s)%s, got (%s)%s instead',
                gettype($this->getKey()),
                $this->getKey(),
                gettype($audit->auditable_id),
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
     * @param  mixed  $id
     * @param  bool  $touch
     * @param  array  $columns
     * @param  \Closure|null  $callback
     * @return void
     *
     * @throws AuditingException
     */
    public function auditAttach(string $relationName, $id, array $attributes = [], $touch = true, $columns = ['*'], $callback = null)
    {
        $this->validateRelationshipMethodExistence($relationName, 'attach');

        $relationCall = $this->{$relationName}();

        if ($callback instanceof \Closure) {
            $this->applyClosureToRelationship($relationCall, $callback);
        }

        $old = $relationCall->get($columns);
        $relationCall->attach($id, $attributes, $touch);
        $new = $relationCall->get($columns);

        $this->dispatchRelationAuditEvent($relationName, 'attach', $old, $new);
    }

    /**
     * @param  mixed  $ids
     * @param  bool  $touch
     * @param  array  $columns
     * @param  \Closure|null  $callback
     * @return int
     *
     * @throws AuditingException
     */
    public function auditDetach(string $relationName, $ids = null, $touch = true, $columns = ['*'], $callback = null)
    {
        $this->validateRelationshipMethodExistence($relationName, 'detach');

        $relationCall = $this->{$relationName}();

        if ($callback instanceof \Closure) {
            $this->applyClosureToRelationship($relationCall, $callback);
        }

        $old = $relationCall->get($columns);
        
        $pivotClass = $relationCall->getPivotClass();
        
        if ($pivotClass !== Pivot::class && is_a($pivotClass, ContractsAuditable::class, true)) {
            $results = $pivotClass::withoutAuditing(function () use ($relationCall, $ids, $touch) {
                return $relationCall->detach($ids, $touch);
            });
        } else {
            $results = $relationCall->detach($ids, $touch);
        }
        
        $new = $relationCall->get($columns);

        $this->dispatchRelationAuditEvent($relationName, 'detach', $old, $new);

        return empty($results) ? 0 : $results;
    }

    /**
     * @param  Collection|Model|array  $ids
     * @param  bool  $detaching
     * @param  array  $columns
     * @param  \Closure|null  $callback
     * @return array
     *
     * @throws AuditingException
     */
    public function auditSync(string $relationName, $ids, $detaching = true, $columns = ['*'], $callback = null)
    {
        $this->validateRelationshipMethodExistence($relationName, 'sync');

        $relationCall = $this->{$relationName}();

        if ($callback instanceof \Closure) {
            $this->applyClosureToRelationship($relationCall, $callback);
        }

        $old = $relationCall->get($columns);
        
        $pivotClass = $relationCall->getPivotClass();
        
        if ($pivotClass !== Pivot::class && is_a($pivotClass, ContractsAuditable::class, true)) {
            $changes = $pivotClass::withoutAuditing(function () use ($relationCall, $ids, $detaching) {
                return $relationCall->sync($ids, $detaching);
            });
        } else {
            $changes = $relationCall->sync($ids, $detaching);
        }

        if (collect($changes)->flatten()->isEmpty()) {
            $old = $new = collect([]);
        } else {
            $new = $relationCall->get($columns);
        }

        $this->dispatchRelationAuditEvent($relationName, 'sync', $old, $new);

        return $changes;
    }

    /**
     * @param  Collection|Model|array  $ids
     * @param  array  $columns
     * @param  \Closure|null  $callback
     * @return array
     *
     * @throws AuditingException
     */
    public function auditSyncWithoutDetaching(string $relationName, $ids, $columns = ['*'], $callback = null)
    {
        $this->validateRelationshipMethodExistence($relationName, 'syncWithoutDetaching');

        return $this->auditSync($relationName, $ids, false, $columns, $callback);
    }

    /**
     * @param  Collection|Model|array  $ids
     * @param  array  $columns
     * @param  \Closure|null  $callback
     * @return array
     */
    public function auditSyncWithPivotValues(string $relationName, $ids, array $values, bool $detaching = true, $columns = ['*'], $callback = null)
    {
        $this->validateRelationshipMethodExistence($relationName, 'syncWithPivotValues');

        if ($ids instanceof Model) {
            $ids = $ids->getKey();
        } elseif ($ids instanceof \Illuminate\Database\Eloquent\Collection) {
            $ids = $ids->isEmpty() ? [] : $ids->pluck($ids->first()->getKeyName())->toArray();
        } elseif ($ids instanceof Collection) {
            $ids = $ids->toArray();
        }

        return $this->auditSync($relationName, collect(Arr::wrap($ids))->mapWithKeys(function ($id) use ($values) {
            return [$id => $values];
        }), $detaching, $columns, $callback);
    }

    /**
     * @param  string  $relationName
     * @param  string  $event
     * @param  Collection  $old
     * @param  Collection  $new
     * @return void
     */
    private function dispatchRelationAuditEvent($relationName, $event, $old, $new)
    {
        $this->auditCustomOld[$relationName] = $old->diff($new)->toArray();
        $this->auditCustomNew[$relationName] = $new->diff($old)->toArray();

        if (
            empty($this->auditCustomOld[$relationName]) &&
            empty($this->auditCustomNew[$relationName])
        ) {
            $this->auditCustomOld = $this->auditCustomNew = [];
        }

        $this->auditEvent = $event;
        $this->isCustomEvent = true;
        Event::dispatch(new AuditCustom($this));
        $this->auditCustomOld = $this->auditCustomNew = [];
        $this->isCustomEvent = false;
    }

    private function validateRelationshipMethodExistence(string $relationName, string $methodName): void
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), $methodName)) {
            throw new AuditingException("Relationship $relationName was not found or does not support method $methodName");
        }
    }

    private function applyClosureToRelationship(BelongsToMany $relation, \Closure $closure): void
    {
        try {
            $closure($relation);
        } catch (\Throwable $exception) {
            throw new AuditingException("Invalid Closure for {$relation->getRelationName()} Relationship");
        }
    }
}
