<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Ramsey\Uuid\Uuid;

trait Auditable
{
    use DatabaseAudits, Auditor;

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
     * @var array
     */
    protected $typeAuditable = null;

    /**
     * Init auditing.
     */
    public static function bootAuditable()
    {
        static::saving(function ($model) {
            $model->prepareAudit();
        });

        static::created(function ($model) {
            $model->auditCreation();
        });

        static::saved(function ($model) {
            $model->auditUpdate();
        });

        static::deleted(function ($model) {
            $model->prepareAudit();
            $model->auditDeletion();
        });
    }

    /**
     * Prepare audit model.
     *
     * @return void
     */
    public function prepareAudit()
    {
        // If auditing is enabled
        if ($this->isAuditEnabled()) {
            $this->originalData = $this->original;

            $this->updatedData = $this->attributes;

            foreach ($this->updatedData as $key => $val) {
                if (gettype($val) == 'object' && !method_exists($val, '__toString')) {
                    unset($this->originalData[$key]);

                    unset($this->updatedData[$key]);

                    array_push($this->dontKeep, $key);
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

            // Get changed data
            $this->dirtyData = $this->getDirty();

            // Tells whether the record exists in the database
            $this->updating = $this->exists;
        }
    }

    /**
     * Audit creation.
     *
     * @return void
     */
    public function auditCreation()
    {
        // If auditing is enabled
        if ($this->isTypeAuditable('created')) {
            $this->typeAuditing = 'created';

            foreach ($this->updatedData as $key => $value) {
                if ($this->isAuditing($key)) {
                    $this->newData[$key] = $value;
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
        // If auditing is enabled and object updated
        if (($this->isTypeAuditable('saved') || $this->isTypeAuditable('updated')) && $this->updating) {
            $this->typeAuditing = 'updated';

            $changesToTecord = $this->changedAuditingFields();

            if (empty($changesToTecord)) {
                return;
            }

            foreach ($changesToTecord as $key => $change) {
                $this->oldData[$key] = array_get($this->originalData, $key);

                $this->newData[$key] = array_get($this->updatedData, $key);
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
        // If auditing is enabled
        if ($this->isTypeAuditable('deleted') && $this->isAuditing('deleted_at')) {
            $this->typeAuditing = 'deleted';

            foreach ($this->updatedData as $key => $value) {
                if ($this->isAuditing($key)) {
                    $this->old[$key] = $value;
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
        // Auditable data
        return [
            'id'             => (string) Uuid::uuid4(),
            'old'            => $this->oldData,
            'new'            => $this->newData,
            'type'           => $this->typeAuditing,
            'auditable_id'   => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            'user_id'        => $this->getLoggedInUserId(),
            'route'          => $this->getCurrentRoute(),
            'ip_address'     => $this->getIpAddress(),
            'created_at'     => $this->freshTimestamp(),
            'updated_at'     => $this->freshTimestamp(),
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
    protected function getCurrentRoute()
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

        foreach ($this->dirtyData as $key => $value) {
            if ($this->isAuditing($key) && !is_array($value)) {
                // Check whether the current value is difetente the original value
                if (!isset($this->originalData[$key]) ||
                    $this->originalData[$key] != $this->updatedData[$key]) {
                    $changesToTecord[$key] = $value;
                }
            } else {
                unset($this->updatedData[$key]);

                unset($this->originalData[$key]);
            }
        }

        return $changesToTecord;
    }

    /**
     * Is Auditing?
     *
     * @param $key
     *
     * @return bool
     */
    private function isAuditing($key)
    {
        // Checks if the field is in the collection of auditable
        if (isset($this->doKeep) && in_array($key, $this->doKeep)) {
            return true;
        }

        // Checks if the field is in the collection of non-auditable
        if (isset($this->dontKeep) && in_array($key, $this->dontKeep)) {
            return false;
        }

        // Checks whether the auditable list is clean
        return empty($this->doKeep);
    }

    /**
     * Is Auditing enabled?
     *
     * @return bool
     */
    private function isAuditEnabled()
    {
        // Check that the model has audit enabled and also check that we aren't
        // running in cosole or that we want to log console too.
        if ((!isset($this->auditEnabled) || $this->auditEnabled)
            && (!App::runningInConsole() || Config::get('auditing.audit_console'))) {
            return true;
        }

        return false;
    }

    /**
     * Verify is type auditable.
     *
     * @param $key
     *
     * @return bool
     */
    public function isTypeAuditable($key)
    {
        // Verify if auditable enabled
        if (!$this->isAuditEnabled()) {
            return false;
        }

        // Get the types auditing
        $auditableTypes = isset($this->auditableTypes) ? $this->auditableTypes
                          : ['created', 'updated', 'deleted', 'saved', 'restored'];

        // Checks if the type is in the collection of type auditable
        if (in_array($key, $auditableTypes)) {
            return true;
        }

        return false;
    }
}
