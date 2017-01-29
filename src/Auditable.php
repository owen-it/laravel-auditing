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

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit as AuditModel;
use RuntimeException;
use UnexpectedValueException;

trait Auditable
{
    /**
     * Attributes to include in the Audit.
     *
     * @var array
     */
    protected $auditInclude = [];

    /**
     * Attributes to exclude from the Audit.
     *
     * @var array
     */
    protected $auditExclude = [];

    /**
     * Should the audit be strict?
     *
     * @var bool
     */
    protected $auditStrict = false;

    /**
     * Should the timestamps be audited?
     *
     * @var bool
     */
    protected $auditTimestamps = false;

    /**
     * Audit driver.
     *
     * @var string
     */
    protected $auditDriver;

    /**
     * Audit threshold.
     *
     * @var int
     */
    protected $auditThreshold = 0;

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
     * Auditable Model audits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function audits()
    {
        return $this->morphMany(AuditModel::class, 'auditable')
            ->orderBy('created_at', 'DESC');
    }

    /**
     * Update excluded audit attributes.
     *
     * @return void
     */
    protected function updateAuditExclusions()
    {
        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->auditStrict) {
            $this->auditExclude = array_merge($this->auditExclude, $this->hidden);

            if (count($this->visible)) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);
                $this->auditExclude = array_merge($this->auditExclude, $invisible);
            }
        }

        if (!$this->auditTimestamps) {
            array_push($this->auditExclude, static::CREATED_AT, static::UPDATED_AT);

            $this->auditExclude[] = defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
        }

        $attributes = array_except($this->attributes, $this->auditExclude);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (is_object($value) && !method_exists($value, '__toString') || is_array($value)) {
                $this->auditExclude[] = $attribute;
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
        // We apply the same logic as the deleted,
        // but the old/new order is swapped
        $this->auditDeletedAttributes($new, $old);
    }

    /**
     * {@inheritdoc}
     */
    public function toAudit()
    {
        if (!$this->isEventAuditable($this->auditEvent)) {
            return [];
        }

        $method = 'audit'.Str::studly($this->auditEvent).'Attributes';

        if (!method_exists($this, $method)) {
            throw new RuntimeException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $method
            ));
        }

        $this->updateAuditExclusions();

        $old = [];
        $new = [];

        $this->{$method}($old, $new);

        return $this->transformAudit([
            'old_values'     => $old,
            'new_values'     => $new,
            'event'          => $this->auditEvent,
            'auditable_id'   => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            'user_id'        => $this->resolveUserId(),
            'url'            => $this->resolveUrl(),
            'ip_address'     => Request::ip(),
            'created_at'     => $this->freshTimestamp(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data)
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
        $resolver = Config::get('audit.user.resolver');

        if (!is_callable($resolver)) {
            throw new UnexpectedValueException('Invalid User resolver type, callable expected');
        }

        return $resolver();
    }

    /**
     * Resolve the current request URL if available.
     *
     * @return string
     */
    protected function resolveUrl()
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }

    /**
     * Determine if an attribute is eligible for auditing.
     *
     * @param string $attribute
     *
     * @return bool
     */
    private function isAttributeAuditable($attribute)
    {
        // The attribute should not be audited
        if (in_array($attribute, $this->auditExclude)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        return in_array($attribute, $this->auditInclude) || empty($this->auditInclude);
    }

    /**
     * Determine whether an event is auditable.
     *
     * @param string $event
     *
     * @return bool
     */
    private function isEventAuditable($event)
    {
        return in_array($event, $this->getAuditableEvents());
    }

    /**
     * {@inheritdoc}
     */
    public function setAuditEvent($event)
    {
        $this->auditEvent = $this->isEventAuditable($event) ? $event : null;

        return $this;
    }

    /**
     * Get the auditable events.
     *
     * @return array
     */
    public function getAuditableEvents()
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
    public static function isAuditingEnabled()
    {
        if (App::runningInConsole()) {
            return (bool) Config::get('audit.console', false);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditDriver()
    {
        return $this->auditDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditThreshold()
    {
        return $this->auditThreshold;
    }
}
