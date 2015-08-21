<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Log;

class Auditing extends Model
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
    protected $dontKeep = [];

    /**
     * @var array
     */
    protected $doKeep = [];

    /**
     * @var array
     */
    protected $dirtyData = [];

    /**
     * @var bool
     */
    public $auditEnabled = true;

    /**
     * Init auditing
     */
    public static function boot()
    {
        parent::boot();

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

    /**
     * Get list of logs
     * @return mixed
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'owner');
    }

    /**
     * Generates a list of the last $limit revisions made to any objects
     * of the class it is being called from.
     *
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public static function classLogHistory($limit = 100, $order = 'desc')
    {
        return Log::where('owner_type', get_called_class())
            ->orderBy('updated_at', $order)->limit($limit)->get();
    }

    /**
     * Listener pre save
     */
    public function preSave()
    {
        if (!isset($this->auditEnabled) || $this->auditEnabled) {

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

    /**
     * Listener pos save
     */
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

        if (((!isset($this->auditEnabled) || $this->auditEnabled) && $this->updating) && (!$LimitReached || $LogCleanup))
        {
            $changes_to_record = $this->changedAuditingFields();
            if(count($changes_to_record))
            {
                $fC = [];
                foreach ($changes_to_record as $key => $change)
                {
                    $fC['old_value'][$key] = array_get($this->originalData, $key);
                    $fC['new_value'][$key] = array_get($this->updatedData, $key);
                }

                $logAuditing = [
                    'old_value'  => json_encode($fC['old_value']),
                    'new_value'  => json_encode($fC['new_value']),
                    'owner_type' => get_class($this),
                    'owner_id' => $this->getKey(),
                    'user_id'    => $this->getUserId(),
                    'created_at' => new \DateTime(),
                    'updated_at' => new \DateTime(),
                ];

                $log = new Log();
                \DB::table($log->getTable())->insert($logAuditing);
            }
        }
    }

    /**
     * Get user id
     *
     * @return null
     */
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

    /**
     * Fields Changed
     * @return array
     */
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

    /**
     * Is Auditing?
     *
     * @param $key
     * @return bool
     */
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

    /**
     * Idenfiable name
     *
     * @return mixed
     */
    public function identifiableName()
    {
        return $this->getKey();
    }

}
