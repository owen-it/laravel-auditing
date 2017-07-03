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
        'resolver'    => function () {
            return Auth::check() ? Auth::user()->getAuthIdentifier() : null;
        },
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
    */
    'drivers' => [
        'database' => [
            'table'      => 'audits',
            'connection' => null,
        ],
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
