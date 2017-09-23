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
    public function audits()
    {
        return $this->morphMany(
            Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class),
            'auditable'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function relatedAudits()
    {
        $xxxx=  $this->morphMany(
            Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class),
            'auditable'
        );

        return $xxxx;
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
    public function readyForAuditing()
    {
        return $this->isEventAuditable($this->auditEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function toAudit($uuid = null, $is_a_related_object=false)
    {
        if ( ! $uuid && ! $this->readyForAuditing())
        {
            throw new RuntimeException('A valid audit event has not been set');
        }

        if ($uuid && $is_a_related_object)
        {
            $method = 'auditUpdatedAttributes';
        }
        else
        {
            $method = 'audit' . Str::studly($this->auditEvent) . 'Attributes';
        }

        if ( ! method_exists($this, $method))
        {
            throw new RuntimeException(
                sprintf(
                    'Unable to handle "%s" event, %s() method missing',
                    $this->auditEvent,
                $method
            ));
        }

        $this->updateAuditExclusions();

        $old = [];
        $new = [];

        $this->{$method}($old, $new);

        $foreignKey = Config::get('audit.user.foreign_key', 'user_id');

        return $this->transformAudit(
            [
                'old_values'     => $old,
                'new_values'     => $new,
                'event'          => $is_a_related_object ? 'related' : $this->auditEvent,
                'auditable_id'   => $this->getKey(),
                'auditable_type' => $this->getMorphClass(),
                'user_id'        => $this->resolveUserId(),
                $foreignKey      => $this->resolveUserId(),
                'url'            => $this->resolveUrl(),
                'ip_address'     => $this->resolveIpAddress(),
                'user_agent'     => $this->resolveUserAgent(),
                'relation_id'    => $uuid,
                'is_related'     => $is_a_related_object,
                'created_at'     => $this->freshTimestamp(),
            ]
        );
    }

    //public function generateRelatedRelations()
    //{
    //    $related_relations_arr = [];
    //    $broad_relationship_types_arr = ['BelongsTo', 'HasMany', 'BelongsToMany', 'HasOne'];
    //
    //    if (!property_exists($this, 'auditIncludeRelated') || !$this->auditIncludeRelated) {
    //        return $related_relations_arr;
    //    }
    //    foreach ($broad_relationship_types_arr as $broad_relationship) {
    //        $broad_relationship_method = 'get'.ucfirst($broad_relationship).'Arr';
    //        if (!method_exists($this, $broad_relationship_method)) {
    //            continue;
    //        }
    //        $related_relations_arr[$broad_relationship] = [];
    //        $relationship_arr = $this->$broad_relationship_method();
    //        foreach ($relationship_arr as $relationship) {
    //            if (!method_exists($this, $relationship)) {
    //                continue;
    //            }
    //            $RelationshipObjArr = $this->$relationship;
    //            if (!is_iterable($RelationshipObjArr)) {
    //                $RelationshipObjArr = [$RelationshipObjArr];
    //            }
    //            foreach ($RelationshipObjArr as $RelationshipObj) {
    //                $related_relations_iten_arr = [
    //
    //                    'type'               => $broad_relationship,
    //                    'source_relation'    => get_class($this),
    //                    'source_relation_id' => $this->{$this->primaryKey},
    //                ];
    //                if ($RelationshipObj == null) {
    //                    /*
    //                     * this can happen when a foreign key constraint referances the self-same table.
    //                     */
    //                } else {
    //                    $related_relations_iten_arr['target_relation'] = get_class($RelationshipObj);
    //                    $related_relations_iten_arr['target_relation_id'] = $RelationshipObj->{$this->primaryKey};
    //                }
    //                $related_relations_arr[$broad_relationship][] = $related_relations_iten_arr;
    //            }
    //        }
    //    }
    //
    //    return $related_relations_arr;
    //}
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
        $userResolver = Config::get('audit.user.resolver');

        if (is_callable($userResolver)) {
            return $userResolver();
        }

        if (is_subclass_of($userResolver, UserResolver::class)) {
            return call_user_func([$userResolver, 'resolveId']);
        }

        throw new UnexpectedValueException('Invalid User resolver, callable or UserResolver FQCN expected');
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
     * Resolve the current IP address.
     *
     * @return string
     */
    protected function resolveIpAddress()
    {
        return Request::ip();
    }

    /**
     * Resolve the current User Agent.
     *
     * @return string
     */
    protected function resolveUserAgent()
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
    protected function isAttributeAuditable($attribute)
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
    protected function isEventAuditable($event)
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
    public function getAuditInclude()
    {
        return isset($this->auditInclude) ? (array) $this->auditInclude : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditExclude()
    {
        return isset($this->auditExclude) ? (array) $this->auditExclude : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditStrict()
    {
        return isset($this->auditStrict) ? (bool) $this->auditStrict : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditTimestamps()
    {
        return isset($this->auditTimestamps) ? (bool) $this->auditTimestamps : false;
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
    public function getAuditThreshold()
    {
        return isset($this->auditThreshold) ? $this->auditThreshold : 0;
    }
}
