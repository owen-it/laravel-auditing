<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Relations\BelongsToMany;
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
     * @var string
     */
    protected $auditType = '';

    /**
     * @var string
     */
    protected $auditUserId = '';

    /**
     * @var string
     */
    protected $auditCurrentRoute = '';

    /**
     * @var string
     */
    protected $auditIpAddress = '';

    /**
     * @var int
     */
    protected $relatedKey;

    /**
     * @var string
     */
    protected $relatedClass;

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
                unset($this->originalData[$attribute]);

                unset($this->updatedData[$attribute]);

                array_push($this->dontKeep, $attribute);
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

        // Prepare general audit data
        $this->prepareGeneralAuditData();

        // Tells whether the record exists in the database
        $this->updating = $this->exists;
    }

    /**
     * Prepare the general audit data
     */
    public function prepareGeneralAuditData()
    {
        // Get user id
        $this->auditUserId = $this->getLoggedInUserId();

        // Get curruent route
        $this->auditCurrentRoute = $this->getCurrentRoute();

        // Get ip address
        $this->auditIpAddress = $this->getIpAddress();
    }

    /**
     * Audit creation.
     *
     * @return void
     */
    public function auditCreation()
    {
        // Checks if an auditable type
        if ($this->isTypeAuditable('created')) {
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
        if ($this->isTypeAuditable('updated') && $this->updating) {
            $changesToRecord = $this->changedAuditingFields();

            if (empty($changesToRecord)) {
                return;
            }

            $this->oldData = [];

            $this->newData = [];

            foreach ($changesToRecord as $attribute => $change) {
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
        // Checks if an auditable type
        if ($this->isTypeAuditable('deleted') && $this->isAttributeAuditable('deleted_at')) {
            foreach ($this->updatedData as $attribute => $value) {
                if ($this->isAttributeAuditable($attribute)) {
                    $this->oldData[$attribute] = $value;
                }
            }

            $this->audit();
        }
    }

    /**
     * Audit attaching a relationship
     *
     * @param array $relationParams
     */
    public function auditAttachedRelation(array $relationParams)
    {
        if ($this->isTypeAuditable('attached')) {
            // Prepare the data
            $this->newData = $relationParams['newData'];
            $this->setRelatedKey($relationParams['relationId']);
            $this->setRelatedClass(get_class($this->{$relationParams['relationName']}()->getRelated()));

            // Audit
            $this->audit();
        }
    }

    /**
     * Audit updating relationships
     *
     * @param array $relationParams
     */
    public function auditUpdatedRelation(array $relationParams)
    {
        if ($this->isTypeAuditable('updatedRelation')) {
            // Prepare the data
            $this->oldData = $relationParams['oldData'];
            $this->newData = $relationParams['newData'];
            $this->setRelatedKey($relationParams['relationId']);
            $this->setRelatedClass(get_class($this->{$relationParams['relationName']}()->getRelated()));

            // Audit
            $this->audit();
        }
    }

    /**
     * Audit detaching a relationship
     *
     * @param array $relationParams
     */
    public function auditDetachedRelation(array $relationParams)
    {
        if ($this->isTypeAuditable('detached')) {
            // Prepare the data
            $this->oldData = $relationParams['oldData'];
            $this->setRelatedKey($relationParams['relationId']);
            $this->setRelatedClass(get_class($this->{$relationParams['relationName']}()->getRelated()));

            // Audit
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
        return $this->transformAudit([
            'id'             => (string) Uuid::uuid4(),
            'old'            => $this->cleanHiddenAuditAttributes($this->oldData),
            'new'            => $this->cleanHiddenAuditAttributes($this->newData),
            'type'           => $this->auditType,
            'auditable_id'   => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            'related_id'     => $this->getRelatedKey(),
            'related_type'   => $this->getRelatedClass(),
            'user_id'        => $this->auditUserId,
            'route'          => $this->auditCurrentRoute,
            'ip_address'     => $this->auditIpAddress,
            'created_at'     => $this->freshTimestamp(),
        ]);
    }

    /**
     * Allows transforming the audit data array
     * before it is passed into the database.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data)
    {
        return $data;
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

        foreach ($this->dirtyData as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute) && !is_array($value)) {
                // Check whether the current value is different the original value
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
     *  Determine whether a type is auditable.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isTypeAuditable($type)
    {
        // Checks if the type is in the collection of type auditable
        if (in_array($type, $this->getAuditableTypes())) {
            $this->setAuditType($type);

            return true;
        }

        return false;
    }

    /**
     * Set audit type.
     *
     * @param string $type;
     */
    public function setAuditType($type)
    {
        $this->auditType = $type;
    }

    /**
     * Get the auditable types.
     *
     * @return array
     */
    public function getAuditableTypes()
    {
        if (isset($this->auditableTypes)) {
            return $this->auditableTypes;
        }

        return [
                'created', 'updated', 'deleted',
                'saved', 'restored',
                'attached', 'updatedRelation', 'detached'
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
     * Define a many-to-many relationship.
     * This is basically the Laravel functionality replacing the BelongsToMany
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // Get foreign key and other key for later use
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $otherKey = $otherKey ?: $instance->getForeignKey();

        // Get the original relationship
        $belongsToMany = parent::belongsToMany($related, $table, $foreignKey, $otherKey, $relation);

        // Create the overridden relationship
        return new BelongsToMany(
            $instance->newQuery(),
            $this,
            $belongsToMany->getTable(),
            $foreignKey,
            $otherKey,
            $belongsToMany->getRelationName()
        );
    }

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved',
                'restoring', 'restored',
                'attached', 'updatedRelation', 'detached'
            ],
            $this->observables
        );
    }

    /**
     * Get the key of the related model
     *
     * @return string
     */
    protected function getRelatedKey()
    {
        return $this->relatedKey;
    }

    /**
     * Set the key of the related model
     *
     * @param string $relatedKey
     */
    protected function setRelatedKey($relatedKey)
    {
        $this->relatedKey = $relatedKey;
    }

    /**
     * Get the class of the related model
     *
     * @return string
     */
    protected function getRelatedClass()
    {
        return $this->relatedClass;
    }

    /**
     * Set the class of the related model
     *
     * @param string $relatedClass
     */
    protected function setRelatedClass($relatedClass)
    {
        $this->relatedClass = $relatedClass;
    }

    /**
     * Register a relation attached model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function attached($callback, $priority = 0)
    {
        static::registerModelEvent("attached", $callback, $priority);
    }

    /**
     * Register a relation attached model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function updatedRelation($callback, $priority = 0)
    {
        static::registerModelEvent("updatedRelation", $callback, $priority);
    }

    /**
     * Register a relation detached model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function detached($callback, $priority = 0)
    {
        static::registerModelEvent("detached", $callback, $priority);
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
}
