<?php

/*
 * This file is part of laravel-auditing.
 *
 * @author Antério Vieira <anteriovieira@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Model
    |--------------------------------------------------------------------------
    |
    | When using the "Eloquent" authentication driver, we need to know which
    | Eloquent model should be used to retrieve your users. Of course, it
    | is often just the "User" model but you may use whatever you like.
    |
    */

    'model' => App\User::class,

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Here is the the database connection for the auditing log.
    |
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Queue Auditable Models
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that audit your models
    | are queued. When this is set to "true" then all models auditable will get
    | queued for better performance.
    |
    */

    'queue' => false,

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    |
    | Here is the the table associated with the auditing model.
    |
    */

    'table' => 'audits',

    /*
    |--------------------------------------------------------------------------
    | Audit console
    |--------------------------------------------------------------------------
    |
    | Whether we should audit queries run through console (eg. php artisan db:seed).
    |
    */

    'audit_console' => false,

    /*
    |--------------------------------------------------------------------------
    | Default Auditor
    |--------------------------------------------------------------------------
    |
    | The default auditor used to audit the eloquent model.
    |
    */

    'default_auditor' => 'database',
];
