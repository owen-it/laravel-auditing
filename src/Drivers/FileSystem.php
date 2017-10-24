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

namespace OwenIt\Auditing\Drivers;

use DateTime;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use League\Flysystem\Adapter\Local as LocalAdapter;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class FileSystem implements AuditDriver
{
    /**
     * @var FilesystemAdapter
     */
    protected $disk = null;

    /**
     * @var FilesystemAdapter
     */
    protected $temporaryLocalDisk = null;

    /**
     * @var string
     */
    protected $dir = null;

    /**
     * @var string
     */
    protected $tempDir = '/audit_temp';

    /**
     * @var string
     */
    protected $filename = null;

    /**
     * @var string
     */
    protected $auditFilepath = null;

    /**
     * @var string One of ['single', 'daily', 'hourly']
     */
    protected $fileLoggingType = null;

    /**
     * FileSystem constructor.
     */
    public function __construct()
    {
        $this->disk = Storage::disk(Config::get('audit.drivers.filesystem.disk', 'local'));
        $this->dir = Config::get('audit.drivers.filesystem.dir', '/');
        $this->filename = Config::get('audit.drivers.filesystem.filename', 'audit.csv');
        $this->fileLoggingType = Config::get('audit.drivers.filesystem.logging_type', 'single');
        $this->auditFilepath = $this->auditFilepath();
        $this->temporaryLocalDisk = Storage::createLocalDriver(['root' => storage_path('app/')]);
    }

    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model)
    {
        if (!$this->disk->exists($this->auditFilepath)) {
            $file = $this->auditFileFromModel($model);

            $this->disk->put($this->auditFilepath, $file);
        } else {
            if ($this->diskIsRemote($this->disk)) {
                $temporaryFilepath = $this->fileToTemporary($this->auditFilepath);

                $updatedAuditContents = $this->appendToFile($this->temporaryLocalDisk->path($temporaryFilepath), $model);

                $this->disk->put($this->auditFilepath, $updatedAuditContents);
            } else {
                $this->appendToFile($this->disk->path($this->auditFilepath), $model);
            }
        }

        $this->cleanUp();
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model)
    {
        return false;
    }

    /**
     * Determine if a disk is a local or a remote disk.
     *
     * @param $disk
     *
     * @return bool
     */
    protected function diskIsRemote(FilesystemAdapter $disk)
    {
        return !($disk->getDriver()->getAdapter() instanceof LocalAdapter);
    }

    /**
     * Get a new csv file as a resource from an Auditable model.
     *
     * @param Auditable $model
     *
     * @return resource
     */
    protected function auditFileFromModel(Auditable $model)
    {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());

        $auditArray = $this->sanitize($this->getAuditFromModel($model));

        $writer->insertOne($this->headerRow($auditArray));
        $writer->insertOne($auditArray);

        $baseContents = 'data://text/csv,'.(string) $writer;

        return @fopen($baseContents, 'r');
    }

    /**
     * Append a record to an existing csv file on the local filesystem.
     *
     * @param $auditFilepath
     * @param $model
     *
     * @return resource
     */
    protected function appendToFile($auditFilepath, Auditable $model)
    {
        $writer = Writer::createFromPath($auditFilepath, 'a+');

        $writer->insertOne($this->sanitize($this->getAuditFromModel($model)));

        $baseContents = 'data://text/csv,'.(string) $writer;

        return @fopen($baseContents, 'r');
    }

    /**
     * Return a randomized csv filename.
     *
     * @return string
     */
    protected function temporaryAuditFilename()
    {
        return str_random().'.csv';
    }

    /**
     * Sanitize audit data before inserting it as a row in a csv file.
     * Currently serializes the old and new values.
     *
     * @param array $audit
     *
     * @return array
     */
    protected function sanitize(array $audit)
    {
        $audit['old_values'] = json_encode($audit['old_values']);
        $audit['new_values'] = json_encode($audit['new_values']);

        return $audit;
    }

    /**
     * Dynamically determine the current audit filepath based on the logging type config setting.
     *
     * @return string
     */
    protected function auditFilepath()
    {
        switch ($this->fileLoggingType) {
            case 'single':
                return $this->dir.$this->filename;

            case 'daily':
                $date = (new \DateTime('now'))->format('Y-m-d');

                return $this->dir."audit-$date.csv";

            case 'hourly':
                $dateTime = (new \DateTime('now'))->format('Y-m-d-H');

                return $this->dir."audit-$dateTime-00-00.csv";

            default:
                throw new \InvalidArgumentException("File logging type {$this->fileLoggingType} unknown. Please use one of 'single', 'daily' or 'hourly'.");
        }
    }

    /**
     * Cleans temporary files.
     */
    protected function cleanUp()
    {
        $this->temporaryLocalDisk->deleteDirectory($this->tempDir);
    }

    /**
     * Move a file from the main audit disk to the local filesystem in the temp dir.
     *
     * @param $auditFilepath
     *
     * @return string
     */
    protected function fileToTemporary($auditFilepath)
    {
        $existingAuditFile = $this->disk->get($auditFilepath);

        $temporaryFilename = $this->temporaryAuditFilename();

        $temporaryFilepath = $this->tempDir.$temporaryFilename;

        $this->temporaryLocalDisk->put($temporaryFilepath, $existingAuditFile);

        return $temporaryFilepath;
    }

    /**
     * Transform an Auditable model into an audit array.
     *
     * @param Auditable $model
     *
     * @return array
     */
    protected function getAuditFromModel(Auditable $model)
    {
        return $this->appendCreatedAt($model->toAudit());
    }

    /**
     * Append a created_at key to the audit array.
     *
     * @param array $audit
     *
     * @return array
     */
    protected function appendCreatedAt(array $audit)
    {
        return array_merge($audit, ['created_at' => (new DateTime('now'))->format('Y-m-d H:i:s')]);
    }

    /**
     * Generate a header row from an audit array, based on the key strings.
     *
     * @param $audit
     *
     * @return array
     */
    protected function headerRow(array $audit)
    {
        return array_map(function ($key) {
            return ucwords(str_replace('_', ' ', $key));
        }, array_keys($audit));
    }
}
