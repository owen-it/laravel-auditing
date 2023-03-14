<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\Resolver as ResolverContract;
use OwenIt\Auditing\Contracts\UserResolver as UserResolverContract;
use OwenIt\Auditing\Exceptions\AuditingException;

trait GathersDataToAudit
{
    /**
     * {@inheritdoc}
     */
    public function toAudit(): array
    {
        if (!$this->readyForAuditing()) {
            throw new AuditingException('A valid audit event has not been set');
        }

        list($old, $new) = $this->getAuditAttributes();

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

        if (is_subclass_of($userResolver, UserResolverContract::class)) {
            return call_user_func([$userResolver, 'resolve']);
        }

        throw new AuditingException('Invalid UserResolver implementation');
    }


    protected function runResolvers(): array
    {
        $resolved = [];
        $resolvers = Config::get('audit.resolvers', []);

        foreach ($resolvers as $name => $implementation) {
            if (empty($implementation)) {
                continue;
            }

            if (!is_subclass_of($implementation, ResolverContract::class)) {
                throw new AuditingException('Invalid Resolver implementation for: ' . $name);
            }
            $resolved[$name] = call_user_func([$implementation, 'resolve'], $this);
        }

        return $resolved;
    }

    /**
     * Gets old and new attributes to write as the audit record.
     * First finds the appropriate getter based on event
     * Then get the array of old attributes and array of new attributes
     * @return array
     * @throws AuditingException
     * @see
     */
    protected function getAuditAttributes(): array
    {
        $attributeGetter = $this->resolveAttributeGetter($this->auditEvent);

        if (!method_exists($this, $attributeGetter)) {
            throw new AuditingException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $attributeGetter
            ));
        }

        list($old, $new) = $this->$attributeGetter();

        return [$old, $new];
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
        if (in_array($attribute, $this->resolveAuditExclusions(), true)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        $include = $this->getAuditInclude();

        return empty($include) || in_array($attribute, $include, true);
    }


    /**
     * Attribute getter method resolver.
     *
     * @param string $event
     *
     * @return string|null
     * @uses self::getDeletedEventAttributes()
     * @uses self::getCreatedEventAttributes()
     * @uses self::getUpdatedEventAttributes()
     * @uses self::getRestoredEventAttributes()
     * @uses self::getCustomEventAttributes()
     * @uses self::getRetrievedEventAttributes()
     */
    protected function resolveAttributeGetter($event)
    {
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

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute getters
    |--------------------------------------------------------------------------
    |
    | Find attributes to add to audit record based on event
    | Getters returns array with two arrays. Old and new
    |
    */

    /**
     * Get the old/new attributes of a retrieved event.
     *
     * @return array<array>
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
     * @return array<array>
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

    /**
     * Get new and old as specified by custom event
     * @return array<array>
     */
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
     * @return array<array>
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
}
