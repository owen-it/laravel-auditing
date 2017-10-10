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

return [

    /*
    |--------------------------------------------------------------------------
    | Audit implementation
    |--------------------------------------------------------------------------
    |
    | Define which Audit model implementation should be used.
    |
    */

    'implementation' => OwenIt\Auditing\Models\Audit::class,

    /*
    |--------------------------------------------------------------------------
    | User Keys, Model & Resolver
    |--------------------------------------------------------------------------
    |
    | Define the User primary and foreign keys, Eloquent model and ID resolver
    | class.
    |
    */

    'user' => [
        'primary_key' => 'id',
        'foreign_key' => 'user_id',
        'model'       => App\User::class,
        'resolver'    => App\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The default audit driver used to keep track of changes.
    |
    */

    'default' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Audit Drivers
    |--------------------------------------------------------------------------
    |
    | Available audit drivers and respective configurations.
    |
    | Config options for the filesystem driver:
    | disk (string) - The name of any filesystem disk in the app. Usage of remote disks (AWS, Rackspace, etc) is discouraged, as it introduces substantial additional http request overheads to the remote disk
    | dir (string) - The directory on the disk where the audit csv files will be saved
    | filename (string) - The filename of the audit file. If logging_type is different from 'single', this filename is ignored as it's being dynamically generated
    | logging_type (string) - Defines how the audit files are being separated. One of 'single', 'daily' or 'hourly'.
    */
    'drivers' => [
        'database' => [
            'table'      => 'audits',
            'connection' => null,
        ],
        'filesystem' => [
            'disk'         => 'local',
            'dir'          => 'audit/',
            'filename'     => 'audit.csv',
            'logging_type' => 'single'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Console?
    |--------------------------------------------------------------------------
    |
    | Whether we should audit console events (eg. php artisan db:seed).
    |
    */

    'console' => false,
];
