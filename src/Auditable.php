<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Dispatcher;
use OwenIt\Auditing\Models\Audit as AuditModel;
use OwenIt\Auditing\Observers\Audit as AuditObserver;
use Ramsey\Uuid\Uuid;

trait Auditable
{
    /**
     * @var array
     */
    private $doKeep = [];

    /**
     * @var array
     */
    private $dontKeep = [];

    /**
     * @var array
     */
    private $originalData = [];

    /**
     * @var array
     */
    private $updatedData = [];

    /**
     * @var bool
     */
    private $updating = false;

    /**
     * @var array
     */
    protected $dirtyData = [];

    /**
     * @var array
     */
    protected $oldData = [];

    /**
     * @var array
     */
    protected $newData = [];

    /**
     * @var string
     */
    protected $auditEvent;

    /**
     * @var string
     */
    protected $auditUserId;

    /**
     * @var string
     */
    protected $auditCurrentUrl;

    /**
     * @var string
     */
    protected $auditIpAddress;

    /**
     * Init auditing.
     */
    public static function bootAuditable()
    {
        if (static::isAuditEnabled()) {
            static::observe(new AuditObserver());
        }
    }

    /**
     * Auditable Model audits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function audits()
    {
        return $this->morphMany(AuditModel::class, 'auditable');
    }

    /**
     * Prepare audit model.
     *
     * @return void
     */
    public function prepareAudit()
    {
        $this->originalData = $this->original;

        $this->updatedData = $this->attributes;

        foreach ($this->updatedData as $attribute => $val) {
            if (gettype($val) == 'object' && !method_exists($val, '__toString')) {
                unset($this->originalData[$attribute], $this->updatedData[$attribute]);

                $this->dontKeep[] = $attribute;
            }
        }

        // Dont keep audit of
        $this->dontKeep = isset($this->dontKeepAuditOf) ?
            array_merge($this->dontKeepAuditOf, $this->dontKeep)
            : $this->dontKeep;

        // Keep audit of
        $this->doKeep = isset($this->keepAuditOf) ?
            array_merge($this->keepAuditOf, $this->doKeep)
            : $this->doKeep;

        // Get User ID
        $this->auditUserId = $this->getLoggedInUserId();

        // Get current URL
        $this->auditCurrentUrl = $this->getCurrentUrl();

        // Get IP address
        $this->auditIpAddress = $this->getIpAddress();

        // Get changed data
        $this->dirtyData = $this->getDirty();

        // Tells whether the record exists in the database
        $this->updating = $this->exists;
    }

    /**
     * Audit creation.
     *
     * @return void
     */
    public function auditCreation()
    {
        // Check if the event is auditable
        if ($this->isEventAuditable('created')) {
            $this->newData = [];

            foreach ($this->updatedData as $attribute => $value) {
                if ($this->isAttributeAuditable($attribute)) {
                    $this->newData[$attribute] = $value;
                }
            }

            $this->audit();
        }
    }

    /**
     * Audit updated.
     *
     * @return void
     */
    public function auditUpdate()
    {
        if ($this->isEventAuditable('updated') && $this->updating) {
            $changesToTecord = $this->changedAuditingFields();

            if (empty($changesToTecord)) {
                return;
            }

            $this->oldData = [];

            $this->newData = [];

            foreach ($changesToTecord as $attribute => $change) {
                $this->oldData[$attribute] = array_get($this->originalData, $attribute);

                $this->newData[$attribute] = array_get($this->updatedData, $attribute);
            }

            $this->audit();
        }
    }

    /**
     * Audit deletion.
     *
     * @return void
     */
    public function auditDeletion()
    {
        // Checks if an the event is auditable
        if ($this->isEventAuditable('deleted') && $this->isAttributeAuditable('deleted_at')) {
            foreach ($this->updatedData as $attribute => $value) {
                if ($this->isAttributeAuditable($attribute)) {
                    $this->oldData[$attribute] = $value;
                }
            }

            $this->audit();
        }
    }

    /**
     * Audit model.
     *
     * @return array
     */
    public function toAudit()
    {
        return [
            'id'             => (string) Uuid::uuid4(),
            'old'            => $this->cleanHiddenAuditAttributes($this->oldData),
            'new'            => $this->cleanHiddenAuditAttributes($this->newData),
            'event'          => $this->auditEvent,
            'auditable_id'   => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            'user_id'        => $this->auditUserId,
            'url'            => $this->auditCurrentUrl,
            'ip_address'     => $this->auditIpAddress,
            'created_at'     => $this->freshTimestamp(),
        ];
    }

    /**
     * Get user id.
     *
     * @return null
     */
    protected function getLoggedInUserId()
    {
        try {
            if (Auth::check()) {
                return Auth::user()->getAuthIdentifier();
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Get the current request's route if available.
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }

    /**
     * Get IP Address.
     *
     * @return mixed
     */
    public function getIpAddress()
    {
        return Request::ip();
    }

    /**
     * Fields Changed.
     *
     * @return array
     */
    private function changedAuditingFields()
    {
        $changesToTecord = [];

        foreach ($this->dirtyData as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute) && !is_array($value)) {
                // Check whether the current value is difetente the original value
                if (!isset($this->originalData[$attribute]) ||
                    $this->originalData[$attribute] != $this->updatedData[$attribute]) {
                    $changesToTecord[$attribute] = $value;
                }
            } else {
                unset($this->updatedData[$attribute]);

                unset($this->originalData[$attribute]);
            }
        }

        return $changesToTecord;
    }

    /**
     * Determine whether a attribute is auditable for audit manipulation.
     *
     * @param $attribute
     *
     * @return bool
     */
    private function isAttributeAuditable($attribute)
    {
        // Checks if the field is in the collection of auditable
        if (isset($this->doKeep) && in_array($attribute, $this->doKeep)) {
            return true;
        }

        // Checks if the field is in the collection of non-auditable
        if (isset($this->dontKeep) && in_array($attribute, $this->dontKeep)) {
            return false;
        }

        // Checks whether the auditable list is clean
        return empty($this->doKeep);
    }

    /**
     * Determine whether an event is auditable.
     *
     * @param string $event
     *
     * @return bool
     */
    public function isEventAuditable($event)
    {
        if (!in_array($event, $this->getAuditableEvents())) {
            return false;
        }

        $this->setAuditEvent($event);

        return true;
    }

    /**
     * Set audit event.
     *
     * @param string $event;
     */
    public function setAuditEvent($event)
    {
        $this->auditEvent = $event;
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
            'saved',
            'restored',
        ];
    }

    /**
     * Whether to clean the attributes which are hidden or not visible.
     *
     * @return bool
     */
    public function isAuditRespectsHidden()
    {
        return isset($this->auditRespectsHidden) && $this->auditRespectsHidden;
    }

    /**
     * Remove the value of attributes which are hidden or not visible on the model.
     *
     * @param $attributes
     *
     * @return array
     */
    public function cleanHiddenAuditAttributes(array $attributes)
    {
        if ($this->isAuditRespectsHidden()) {

            // Get hidden and visible attributes from the model
            $hidden = $this->getHidden();
            $visible = $this->getVisible();

            // If visible is set, set to null any attributes which are not in visible
            if (count($visible) > 0) {
                foreach ($attributes as $attribute => &$value) {
                    if (!in_array($attribute, $visible)) {
                        $value = null;
                    }
                }
            }

            unset($value);

            // If hidden is set, set to null any attributes which are in hidden
            if (count($hidden) > 0) {
                foreach ($hidden as $attribute) {
                    if (array_key_exists($attribute, $attributes)) {
                        $attributes[$attribute] = null;
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Determine whether audit enabled.
     *
     * @return bool
     */
    public static function isAuditEnabled()
    {
        if (App::runningInConsole() && !Config::get('auditing.audit_console')) {
            return false;
        }

        return true;
    }

    /**
     * Get the Auditors.
     *
     * @return array
     */
    public function getAuditors()
    {
        return isset($this->auditors) ? $this->auditors : Config::get('auditing.default_auditor');
    }

    /**
     * Audit the model auditable.
     *
     * @return void
     */
    public function audit()
    {
        app(Dispatcher::class)->audit($this);
    }

    /**
     * {@inheritdoc}
     */
    public function clearOlderAudits()
    {
        $auditsHistoryCount = $this->audits()->count();

        $auditsHistoryOlder = $auditsHistoryCount - $this->auditLimit;

        if (isset($this->auditLimit) && $auditsHistoryOlder > 0) {
            $this->audits()->orderBy('created_at', 'asc')
                ->limit($auditsHistoryOlder)->delete();
        }
    }

    /**
     * Identifiable name.
     *
     * @return mixed
     */
    public function identifiableName()
    {
        return $this->getKey();
    }
}
