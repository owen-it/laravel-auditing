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
	protected $auditEnabled = true;

	/**
	 * @var array
	 */
	protected $auditableTypes = ['created', 'saved', 'deleted'];

	/**
	 * Init auditing
	 */
	public static function boot()
	{
		parent::boot();

		static::saving(function ($model)
		{
			$model->prepareAudit();
		});

		static::created(function($model)
		{
			if($model->isTypeAuditable('created'))
				$model->auditCreation();
		});

		static::saved(function ($model)
		{
			if($model->isTypeAuditable('saved'))
				$model->auditUpdate();
		});

		static::deleted(function($model)
		{
			if($model->isTypeAuditable('deleted')){
				$model->prepareAudit();
				$model->auditDeletion();
			}
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
	 * @param int $limit
	 * @param string $order
	 * @return mixed
	 */
	public function logHistory($limit = 100, $order = 'desc')
	{
		return static::classLogHistory($limit, $order);
	}

	/**
	 * Prepare audit model
	 */
	public function prepareAudit()
	{
		if (!isset($this->auditEnabled) || $this->auditEnabled) {

			$this->originalData = $this->original;
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

			// Get changed data
			$this->dirtyData = $this->getDirty();
			// Tells whether the record exists in the database
			$this->updating = $this->exists;
		}
	}

	/**
	 * Audit creation
	 */
	public function auditCreation()
	{
		if ((!isset($this->auditEnabled) || $this->auditEnabled))
		{
			return $this->audit([
				'old_value'  => null,
				'new_value'  => $this->updatedData,
				'owner_type' => get_class($this),
				'owner_id'   => $this->getKey(),
				'user_id'    => $this->getUserId(),
				'type'       => 'created',
				'created_at' => new \DateTime(),
				'updated_at' => new \DateTime(),
			]);
		}
	}

	/**
	 * Adudit update
	 */
	public function auditUpdate()
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
				$log = ['type' => 'updated'];
				foreach ($changes_to_record as $key => $change)
				{
					$log['old_value'][$key] = array_get($this->originalData, $key);
					$log['new_value'][$key] = array_get($this->updatedData, $key);
				}

				$this->audit($log);
			}
		}
	}

	/**
	 * Audit deletion
	 */
	public function auditDeletion()
	{
		if ((!isset($this->auditEnabled) || $this->auditEnabled)
			&& $this->isAuditing('deleted_at'))
		{
			return $this->audit([
				'old_value'  => $this->updatedData,
				'new_value'  => null,
				'owner_type' => get_class($this),
				'owner_id'   => $this->getKey(),
				'user_id'    => $this->getUserId(),
				'type'       => 'deleted',
				'created_at' => new \DateTime(),
				'updated_at' => new \DateTime(),
			]);
		}
	}

	/**
	 * Audit model
	 */
	public function audit(array $log)
	{
		$logAuditing = [
			'old_value'  => json_encode($log['old_value']),
			'new_value'  => json_encode($log['new_value']),
			'owner_type' => get_class($this),
			'owner_id'   => $this->getKey(),
			'user_id'    => $this->getUserId(),
			'type'       => $log['type'],
			'created_at' => new \DateTime(),
			'updated_at' => new \DateTime(),
		];

		return Log::insert($logAuditing);
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
				// Check whether the current value is difetente the original value
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
	 * Idenfiable name
	 *
	 * @return mixed
	 */
	public function identifiableName()
	{
		return $this->getKey();
	}

	/**
	 * Verify is type auditable
	 *
	 * @param $key
	 * @return bool
	 */
	public function isTypeAuditable($key)
	{
		if (in_array($key, $this->auditableTypes)) {
			return true;
		}

		return;
	}

}
