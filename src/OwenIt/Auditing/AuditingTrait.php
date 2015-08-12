<?php

namespace OwenIt\Auditing;

use OwenIt\Auditing\Log;

trait AuditingTrait
{
    /**
     * @var array
     */
    private $originalData = [];

    /**
     * @var array
     */
    private $updatedData = [];

    /**
     * @var boolean
     */
    private $updating = false;

    /**
     * @var array
     */
    private $dontKeep = [];

    /**
     * @var array
     */
    private $doKeep = [];

    protected $dirtyData = [];

    public static function boot()
    {
        parent::boot();

        if (!method_exists(get_called_class(), 'bootTraits')) {
            static::bootAuditingTrait();
        }
    }

    public static function bootAuditingTrait()
    {
        // Adiciona um ouvite para quando for chamado
        // a função de salvar
        static::saving(function ($model) {
            $model->preSave();
        });

        // Adiciona um ouvinte para ser executado
        // após os dados serem salvos
        static::saved(function ($model) {
            $model->postSave();
        });
    }

    public function preSave()
    {
        if (!isset($this->logEnabled) || $this->logEnabled) {

            // Pega dados originais anteriores
            $this->originalData = $this->original;
            // Pega dados atuais
            $this->updatedData = $this->attributes;

            foreach ($this->updatedData as $key => $val) {
                if (gettype($val) == 'object' && !method_exists($val, '__toString')) {
                    unset($this->originalData[$key]);
                    unset($this->updatedData[$key]);
                    array_push($this->dontKeep, $key);
                }
            }

            $this->dontKeep = isset($this->dontKeepLogOf) ?
                $this->dontKeepLogOf + $this->dontKeep
                : $this->dontKeep;

            $this->doKeep = isset($this->keepLogOf) ?
                $this->keepLogOf + $this->doKeep
                : $this->doKeep;

            unset($this->attributes['dontKeepLogOf']);
            unset($this->attributes['keepLogOf']);

            // Pega dados alterados
            $this->dirtyData = $this->getDirty();
            // Informa que o registro não existe no banco
            $this->updating = $this->exists;
        }
    }

    public function postSave()
    {

        if (isset($this->historyLimit) && $this->logHistory()->count() >= $this->historyLimit) {
            $LimitReached = true;
        } else {
            $LimitReached = false;
        }
        if (isset($this->logCleanup)){
            $LogCleanup = $this->LogCleanup;
        }else{
            $LogCleanup = false;
        }

        if (((!isset($this->logEnabled) || $this->logEnabled) && $this->updating) && (!$LimitReached || $LogCleanup))
        {
            $changes_to_record = $this->changedAuditingFields();
            $fieldsChanged = [];
            foreach ($changes_to_record as $key => $change)
            {
                $fieldsChanged['old_value'][$key] = array_get($this->originalData, $key);
                $fieldsChanged['new_value'][$key] = array_get($this->updatedData, $key);
            }

            $logAuditing = [
                'old_value'  => json_encode($fieldsChanged['old_value']),
                'new_value'  => json_encode($fieldsChanged['new_value']),
                'user_id'    => $this->getUserId(),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ];

            if (count($fieldsChanged['old_value']) > 0) {
                $log = new Log();
                \DB::table($log->getTable())->insert($logAuditing);
            }
        }
    }

    private function getUserId()
    {
        try {
            if (class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry')
                || class_exists($class = '\Cartalyst\Sentinel\Laravel\Facades\Sentinel')
            ) {
                return ($class::check()) ? $class::getUser()->id : null;
            } elseif (\Auth::check()) {
                return \Auth::user()->getAuthIdentifier();
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function changedAuditingFields()
    {

        $changes_to_record = array();
        foreach ($this->dirtyData as $key => $value) {
            if ($this->isAuditing($key) && !is_array($value)) {
                // Verifica se o valor atual é difetente do valor original
                if (!isset($this->originalData[$key]) || $this->originalData[$key] != $this->updatedData[$key]) {
                    $changes_to_record[$key] = $value;
                }
            } else {
                unset($this->updatedData[$key]);
                unset($this->originalData[$key]);
            }
        }

        return $changes_to_record;
    }

    private function isAuditing($key)
    {
        // Verifica se o campo esta na coleção de autaveis
        if (isset($this->doKeep) && in_array($key, $this->doKeep)) {
            return true;
        }

        // Verifica se o campo esta na coleção de não auditaveis
        if (isset($this->dontKeep) && in_array($key, $this->dontKeep)) {
            return false;
        }

        // Verifica se a lista de auditaveis esta limpa
        return empty($this->doKeep);
    }

    public function identifiableName()
    {
        return $this->getKey();
    }

    public function disableLogField($field)
    {
        if (!isset($this->dontKeepRevisionOf)) {
            $this->dontKeepRevisionOf = array();
        }
        if (is_array($field)) {
            foreach ($field as $one_field) {
                $this->disableRevisionField($one_field);
            }
        } else {
            $donts = $this->dontKeepRevisionOf;
            $donts[] = $field;
            $this->dontKeepRevisionOf = $donts;
            unset($donts);
        }
    }
}
