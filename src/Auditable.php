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

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\UserResolver;
use RuntimeException;
use UnexpectedValueException;

trait Auditable
{
    /**
     *  Auditable attribute exclusions.
     *
     * @var array
     */
    protected $auditableExclusions = [];

    /**
     * Audit event name.
     *
     * @var string
     */
    protected $auditEvent;

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
     * Update excluded audit attributes.
     *
     * @return void
     */
    protected function updateAuditExclusions()
    {
        $this->auditableExclusions = $this->getAuditExclude();

        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->getAuditStrict()) {
            // Hidden attributes
            $this->auditableExclusions = array_merge($this->auditableExclusions, $this->hidden);

            // Non visible attributes
            if (!empty($this->visible)) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);

                $this->auditableExclusions = array_merge($this->auditableExclusions, $invisible);
            }
        }

        // Exclude Timestamps
        if (!$this->getAuditTimestamps()) {
            array_push($this->auditableExclusions, static::CREATED_AT, static::UPDATED_AT);

            if (defined('static::DELETED_AT')) {
                $this->auditableExclusions[] = static::DELETED_AT;
            }
        }

        // Valid attributes are all those that made it out of the exclusion array
        $attributes = array_except($this->attributes, $this->auditableExclusions);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (is_object($value) && !method_exists($value, '__toString') || is_array($value)) {
                $this->auditableExclusions[] = $attribute;
            }
        }
    }

    /**
     * Set the old/new attributes corresponding to a created event.
     *
     * @param array $old
     * @param array $new
     *
     * @return void
     */
    protected function auditCreatedAttributes(array &$old, array &$new)
    {
        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }
    }

    /**
     * Set the old/new attributes corresponding to an updated event.
     *
     * @param array $old
     * @param array $new
     *
     * @return void
     */
    protected function auditUpdatedAttributes(array &$old, array &$new)
    {
        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = array_get($this->original, $attribute);
                $new[$attribute] = array_get($this->attributes, $attribute);
            }
        }
    }

    /**
     * Set the old/new attributes corresponding to a deleted event.
     *
     * @param array $old
     * @param array $new
     *
     * @return void
     */
    protected function auditDeletedAttributes(array &$old, array &$new)
    {
        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = $value;
            }
        }
    }

    /**
     * Set the old/new attributes corresponding to a restored event.
     *
     * @param array $old
     * @param array $new
     *
     * @return void
     */
    protected function auditRestoredAttributes(array &$old, array &$new)
    {
        // Apply the same logic as the deleted event,
        // but with the old/new arguments swapped
        $this->auditDeletedAttributes($new, $old);
    }

    /**
     * {@inheritdoc}
     */
    public function readyForAuditing(): bool
    {
        return $this->isEventAuditable($this->auditEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function toAudit(): array
    {
        if (!$this->readyForAuditing()) {
            throw new RuntimeException('A valid audit event has not been set');
        }

        $eventHandler = $this->resolveEventHandlerMethod($this->auditEvent);

        if (!method_exists($this, $eventHandler)) {
            throw new RuntimeException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $eventHandler
            ));
        }

        $this->updateAuditExclusions();

        $old = [];
        $new = [];

        $this->{$eventHandler}($old, $new);

        $foreignKey = Config::get('audit.user.foreign_key', 'user_id');

        return $this->transformAudit([
            'old_values'     => $old,
            'new_values'     => $new,
            'event'          => $this->auditEvent,
            'auditable_id'   => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            $foreignKey      => $this->resolveUserId(),
            'url'            => $this->resolveUrl(),
            'ip_address'     => $this->resolveIpAddress(),
            'user_agent'     => $this->resolveUserAgent(),
            'tags'           => implode(',', $this->generateTags()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        return $data;
    }

    /**
     * Resolve the ID of the logged User.
     *
     * @throws UnexpectedValueException
     *
     * @return mixed|null
     */
    protected function resolveUserId()
    {
        $userResolver = Config::get('audit.user.resolver');

        if (is_subclass_of($userResolver, UserResolver::class)) {
            return call_user_func([$userResolver, 'resolveId']);
        }

        throw new UnexpectedValueException('Invalid User resolver, UserResolver FQCN expected');
    }

    /**
     * Resolve the current request URL if available.
     *
     * @return string
     */
    protected function resolveUrl(): string
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }

    /**
     * Resolve the current IP address.
     *
     * @return string
     */
    protected function resolveIpAddress(): string
    {
        return Request::ip();
    }

    /**
     * Resolve the current User Agent.
     *
     * @return string
     */
    protected function resolveUserAgent(): string
    {
        return Request::header('User-Agent');
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
        if (in_array($attribute, $this->auditableExclusions)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        $include = $this->getAuditInclude();

        return in_array($attribute, $include) || empty($include);
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
        return is_string($this->resolveEventHandlerMethod($event));
    }

    /**
     * Event handler method resolver.
     *
     * @param string $event
     *
     * @return string|null
     */
    protected function resolveEventHandlerMethod($event)
    {
        foreach ($this->getAuditableEvents() as $key => $value) {
            $auditableEvent = is_int($key) ? $value : $key;

            $auditableEventRegex = sprintf('/%s/', preg_replace('/\*+/', '.*', $auditableEvent));

            if (preg_match($auditableEventRegex, $event)) {
                return is_int($key) ? sprintf('audit%sAttributes', Str::studly($event)) : $value;
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
     * Get the auditable events.
     *
     * @return array
     */
    public function getAuditableEvents(): array
    {
        if (isset($this->auditableEvents)) {
            return $this->auditableEvents;
        }

        return [
            'created',
            'updated',
            'deleted',
            'restored',
        ];
    }

    /**
     * Determine whether auditing is enabled.
     *
     * @return bool
     */
    public static function isAuditingEnabled(): bool
    {
        if (App::runningInConsole()) {
            return Config::get('audit.console', false);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditInclude(): array
    {
        return isset($this->auditInclude) ? $this->auditInclude : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditExclude(): array
    {
        return isset($this->auditExclude) ? $this->auditExclude : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditStrict(): bool
    {
        return isset($this->auditStrict) ? $this->auditStrict : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditTimestamps(): bool
    {
        return isset($this->auditTimestamps) ? $this->auditTimestamps : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditDriver()
    {
        return isset($this->auditDriver) ? $this->auditDriver : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditThreshold(): int
    {
        return isset($this->auditThreshold) ? $this->auditThreshold : 0;
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
    public function transitionThrough(Contracts\Audit $audit, array $exclude = []): bool
    {
        // The Audit must be for this Auditable model of this type
        if (!$this instanceof $audit->auditable_type) {
            throw new RuntimeException(sprintf(
                'Expected Audit for %s, got Audit for %s instead',
                get_class($this),
                $audit->auditable_type
            ));
        }

        // The Audit must be for this specific Auditable model
        if ($this->getKey() !== $audit->auditable_id) {
            throw new RuntimeException(sprintf(
                'Expected Auditable id %s, got %s instead',
                $this->getKey(),
                $audit->auditable_id
            ));
        }

        // Exclude unwanted attributes
        $modified = array_filter($audit->getModified(), function ($value, $key) use ($exclude) {
            return !in_array($key, $exclude);
        }, ARRAY_FILTER_USE_BOTH);

        // The attribute compatibility between the Audit and the Auditable model must be met
        if ($missing = array_diff_key($modified, $this->getAttributes())) {
            throw new RuntimeException(sprintf(
                'Incompatibility between %s [id:%s] and Audit [id:%s]. Missing attributes: [%s]',
                get_class($this),
                $this->getKey(),
                $audit->getKey(),
                implode(', ', array_keys($missing))
            ));
        }

        foreach ($modified as $attribute => $value) {
            $this->setAttribute($attribute, $value['new']);
        }

        return $this->save();
    }
}
