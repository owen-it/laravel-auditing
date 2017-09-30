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

    /*
     | Indicate the models (relating model) that you want to retain related relations for. For each,
     | indicate the 'property' of that relating model that returns returns related objects. 'Yields' is the
     | type of object returned.
     |
     | This results in $AuditObj's with a type of 'related' and a call to $AuditObj->getRelatingAudit.
     |
     */
    'relation_hierarchy' => [
        //\App\Models\Property::class => [
        //    [
        //        'property' => 'accessListProperties',
        //        'yields' => \App\Models\AccessListProperty::class,
        //    ],
        //    [
        //        'property' => 'client',
        //        'yields' => \App\Models\Client::class,
        //    ]
        //],
        //\App\Models\User::class => [
        //    [
        //        'property' => 'accessListUsers',
        //        'yields' => \App\Models\AccessListUser::class,
        //    ]
        //],
        //\App\Models\AccessList::class => [
        //    [
        //        'property' => 'accessListUsers',
        //        'yields' => \App\Models\AccessListUser::class,
        //    ],
        //    [
        //        'property' => 'accessListProperties',
        //        'yields' => \App\Models\AccessListProperty::class,
        //        'relator'=> 'property'
        //    ],
        //],
        //\App\\Models\PropertyGroup::class => [
        //    [
        //        'property' => 'propertyGroupProperty',
        //        'yields' => \App\Models\PropertyGroupProperty::class,
        //        'relator'=> 'propertyGroup'
        //    ],
        //]
    ]
];
