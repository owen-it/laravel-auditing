
[![Latest Stable Version](https://poser.pugx.org/owen-it/laravel-auditing/version)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Total Downloads](https://poser.pugx.org/owen-it/laravel-auditing/downloads)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Latest Unstable Version](https://poser.pugx.org/owen-it/laravel-auditing/v/unstable)](//packagist.org/packages/owen-it/laravel-auditing)
[![License](https://poser.pugx.org/owen-it/laravel-auditing/license.svg)](https://packagist.org/packages/owen-it/laravel-auditing)

Laravel Auditing allows you to record changes to an Eloquent model's set of data by simply adding its trait to your model. Laravel Auditing also provides a simple interface for retreiving an audit trail for a piece of data and allows for a great deal of customization in how that data is provided.

> Auditing is based on the package [revisionable](https://packagist.org/packages/VentureCraft/revisionable)

## Installation

Laravel Auditing can be installed with [Composer](http://getcomposer.org/doc/00-intro.md), more details about this package in Composer can be found [here](https://packagist.org/packages/owen-it/laravel-auditing).

Run the following command to get the latest version package:

```
composer require owen-it/laravel-auditing
```
Open the file ```config/app.php``` and then add the service provider, this step is required.

```php
'providers' => [
    // ...
    OwenIt\Auditing\AuditingServiceProvider::class,
],
```

> Note: This provider is important for the publication of configuration files.

Only after complete the step before, use the following command to publish configuration settings:

```
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider"
```
Finally, execute the migration to create the ```logs``` table in your database. This table is used to save audit the logs.

```
php artisan migrate
```


## Docs
* [Implementation](#implementation)
* [Configuration](#configuration)
* [Getting the Logs](#getting)
* [Customizing log message](#customizing)
* [Examples](#examples)
* [Contributing](#contributing)
* [Having problems?](#faq)
* [license](#license)

<a name="implementation"></a>
## Implementation

### Implementation using ```Trait```

To register the change log, use the trait `OwnerIt\Auditing\AuditingTrait` in the model you want to audit

```php
// app/Team.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\AuditingTrait;

class Team extends Model 
{
    use AuditingTrait;
    //...
}

```

### Base implementation Legacy Class

It is also possible to have your model extend the `OwnerIt\Auditing\Auditing` class to enable auditing. Example:

```php
// app/Team.php
namespace App;

use OwenIt\Auditing\Auditing;

class Team extends Auditing 
{
    //...    
}
```

<a name="configuration"></a>
## Configuration

### Auditing behavior settings
The Auditing behavior settings are carried out with the declaration of attributes in the model. See the examples below:

* Turn off logging after a number of logs: `$historyLimit = 500`
* Disable / enable logging: `$auditEnabled = false`
* Turn off logging for specific fields: `$dontKeepLogOf = ['field1', 'field2']`

> Note: This implementation is optional, you can make these customizations where desired.

```php
// app/Team.php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Team extends Model 
{
    use OwenIt\Auditing\AuditingTrait;
    
    // Disables the log record in this model.
    protected $auditEnabled  = false;
    // Disables the log record after 500 records.
    protected $historyLimit = 500; 
    // Fields you do NOT want to register.
    protected $dontKeepLogOf = ['created_at', 'updated_at'];
    // Tell what actions you want to audit.
    protected $auditableTypes = ['created', 'saved', 'deleted'];
}
```
### Auditing settings
Using the configuration file, you can define:
* The Model used to represent the current user of application.
* A different database connection for audit.
* The table name used for log registers.
    
The configuration file can be found at `config/auditing.php`

```php
// config/auditing.php
return [

    // Authentication Model
    'model' => App\User::class,

    // Database Connection
    'connection' => null,

    // Table Name
    'table' => 'logs',
];
```

<a name="getting"></a>
## Getting the Logs

```php
// app/Http/Controller/MyAppController.php
namespace App\Http\Controllers;

use App\Team;

class MyAppController extends BaseController 
{
    public function index()
    {
        $team = Team::find(1); // Get team
        $team->logs; // Get all logs
        $team->logs->first(); // Get first log
        $team->logs->last();  // Get last log
        $team->logs->find(2); // Selects log
    }
    //...
}
```
Getting logs with user responsible for the change.
```php
use OwenIt\Auditing\Log;

$logs = Log::with(['user'])->get();

```
or
```php
use App\Team;

$logs = Team::logs->with(['user'])->get();

```

> Note: Remember to properly define the user model in the file ``` config/auditing.php ```
>```php
> ...
> 'model' => App\User::class,
> ... 
>```

<a name="customizing"></a>
## Customizing log message

You can define your own log messages for presentation. These messages can be defined for both the model as well as for each one of fields.The dynamic part of the message can be done by targeted fields per dot segmented as`{object.property.property} or {object.property|Default value} or {object.property||callbackMethod}`. 

> Note: This implementation is optional, you can make these customizations where desired.

Set messages to the model
```php
// app/Team.php
namespace App;

use OwenIt\Auditing\Auditing;

class Team extends Auditing 
{
    //...
    public static $logCustomMessage = '{user.name|Anonymous} {type} a team {elapsed_time}'; // with default value
    public static $logCustomFields = [
        'name'  => 'The name was defined as {new.name||getNewName}', // with callback method
        'owner' => [
            'updated' => '{new.owner} owns the team',
            'created' => '{new.owner|No one} was defined as owner'
        ],
    ];
    
    public function getNewName($log)
    {
        return $log->new['name'];
    }
    //...
}
```
Getting change logs 
```php
// app/Http/Controllers/MyAppController.php 
//...
public function auditing()
{
    $logs = Team::find(1)->logs; // Get logs of team
    return view('auditing', compact('logs'));
}
//...
    
```
Featuring log records:
```
    // resources/views/my-app/auditing.blade.php
    ...
    <ol>
        @forelse ($logs as $log)
            <li>
                {{ $log->customMessage }}
                <ul>
                    @forelse ($log->customFields as $custom)
                        <li>{{ $custom }}</li>
                    @empty
                        <li>No details</li>
                    @endforelse
                </ul>
            </li>
        @empty
            <p>No logs</p>
        @endforelse
    </ol>
    ...
    
```
Result:
<ol>
  <li>Antério Vieira created a team 1 day ago   
    <ul>
      <li>The name was defined as gestao</li>
      <li>No one was defined as owner</li>
    </ul>
  </li>
  <li>Rafael França deleted a team 2 day ago   
    <ul>
      <li>No details</li>
    </ul>
  </li>  
  <li>...</li>
</ol>

<a name="examples"></a>
## Examples

##### Spark Auditing
For convenience we decided to use the [spark](https://github.com/laravel/spark) for this example, the demonstration of auditing is simple and self explanatory. [Click here](https://github.com/owen-it/spark-auditing) and see for yourself.

##### Dreams
Dreams is a developed api to serve as an example or direction for developers using laravel-auditing. You can access the application [here](https://dreams-.herokuapp.com). The back-end (api) was developed in laravel 5.1 and the front-end (app) in angularjs, the detail are these:

* [Link for application](https://dreams-.herokuapp.com) 
* [Source code api-dreams](https://github.com/owen-it/api-dreams)
* [Source code app-dreams](https://github.com/owen-it/app-dreams)

<a name="contributing"></a>
## Contributing

Contributions are welcomed; to keep things organized, all bugs and requests should be opened on github issues tab for the main project in the [owen-it/laravel-auditing/issues](https://github.com/owen-it/laravel-auditing/issues).

All pull requests should be made to the branch Develop, so they can be tested before being merged into the master branch.

<a name="faq"></a>
## Having problems?

If you are having problems with the use of this package, there is likely someone has faced the same problem. You can find common answers to their problems:

* [Github Issues](https://github.com/owen-it/laravel-auditing/issues?page=1&state=closed)

<a name="license"></a>
### License

The laravel-audit package is open source software licensed under the [license MIT](http://opensource.org/licenses/MIT)

