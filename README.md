

<img src="http://owen.com.br/imagens_para_web/auditing.png" style="width: 100%" alt="laravel-auditing" />

[![Latest Stable Version](https://poser.pugx.org/owen-it/laravel-auditing/version)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Total Downloads](https://poser.pugx.org/owen-it/laravel-auditing/downloads)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Latest Unstable Version](https://poser.pugx.org/owen-it/laravel-auditing/v/unstable)](//packagist.org/packages/owen-it/laravel-auditing)
[![License](https://poser.pugx.org/owen-it/laravel-auditing/license.svg)](https://packagist.org/packages/owen-it/laravel-auditing)

It is always important to have change history records in the system. The Auditing does just that simple and practical way, you simply extends it in the model you would like to register the change log. 

> Auditing is based on the package [revisionable](https://packagist.org/packages/VentureCraft/revisionable)

## Installation

Auditing is installable via [composer](http://getcomposer.org/doc/00-intro.md), the details are [here](https://packagist.org/packages/owen-it/laravel-auditing).

Run the following command to get the latest version package

```
composer require owen-it/laravel-auditing
```
Open ```config/app.php``` and register the required service provider.

```php
'providers' => [
    // ...
    OwenIt\Auditing\AuditingServiceProvider::class,
],
```

> Note: This provider is important for the publication of configuration files.

Use the following command to publish settings:

```
php artisan vendor:publish
```
Now you need execute the mitration to create the table ```logs``` in your database, this table is used for save logs of altering.

```
php artisan migrate
```


## Docs
* [Dreams (Example)](#example)
* [Implementation](#implementation)
* [Configuration](#configuration)
* [Getting the Logs](#getting)
* [Featuring Log](#featuring)
* [Contributing](#contributing)
* [Having problems?](#faq)
* [license](#license)


<a name="example"></a>
## Dreams (Examle)
Dreams is a developed api to serve as an example or direction for developers using laravel-auditing. You can access the application [here](https://dreams-.herokuapp.com). The back-end (api) was developed in laravel 5.1 and the front-end (app) in angularjs, the detail are these:

* [Link for application](https://dreams-.herokuapp.com) 
* [Source code api-dreams](https://github.com/owen-it/api-dreams)
* [Source code app-dreams](https://github.com/owen-it/app-dreams)

<a name="implementation"></a>
## Implementation

### Implementation using ```Trait```

To register the change log, use the trait `OwnerIt\Auditing\AuditingTrait` in the model you want to audit

```php
// app/Models/People.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\AuditingTrait;

class People extends Model 
{
    use AuditingTrait;
    //...
}

```

> Note: Traits require PHP >= 5.4

### Base implementation Legacy Class

To register the chage log with Legacy class, extend the class `OwnerIt\Auditing\Auditing` in the model you want to audit. Example:

```php
// app/Models/People.php

namespace App\Models;

use OwenIt\Auditing\Auditing;

class People extends Auditing 
{
    //...    
}
```

<a name="configuration"></a>
### Configuration

The Auditing behavior settings are carried out with the declaration of attributes in the model. See the examples below:

* Turn off logging after a number "X": `$historyLimit = 500`
* Disable / enable logging (Audit): `$auditEnabled = false`
* Turn off logging for specific fields: `$dontKeep = ['campo1', 'campo2']`

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class People extends Model 
{
    use OwenIt\Auditing\AuditingTrait;

    protected $auditEnabled  = false;      // Disables the log record in this model.
    protected $historyLimit = 500;         // Disables the log record after 500 records.
    protected $dontKeep = ['cpf', 'nome']; // Enter the fields you want to NOT register with the log.
    protected $auditableTypes = ['created', 'saved', 'deleted']; // Informe quais ações deseja auditar
}
```

<a name="getting"></a>
## Getting the Logs

```php
namespace App\Http\Controllers;

use App\Models\People;

class MyAppController extends BaseController 
{

    public function index()
    {
        $people = People::find(1); // Get people
        $people->logs; // Get all logs
        $people->logs->first(); // Get first log
        $people->logs->last();  // Get last log
        $people->logs->find(2); // Selects log
    }

    ...
}
```
Getting logs with user responsible for the change.
```php
use OwenIt\Auditing\Log;

$logs = Log::with(['user'])->get();

```
or
```php
use App\Models\People;

$logs = People::logs->with(['user'])->get();

```

> Note: Remember to properly define the user model in the file ``` config/auth.php ```
>```php
> ...
> 'model' => App\User::class,
> ... 
>```

<a name="featuring"></a>
## Featuring Log

You it can set custom messages for presentation of logs. These messages can be set for both the model as for specific fields.The dynamic part of the message can be done by targeted fields per dot segmented as`{objeto.field} or {objeto.objeto.field}`. 

Set messages to the model
```php
namespace App;

use OwenIt\Auditing\Auditing;

class People extends Auditing 
{
    ...

	public static $logCustomMessage = '{user.nome} been updated by {old.nome}';
	public static $logCustomFields = [
	    'nome' => 'Before {old.nome} and after {new.nome}',
	    'cpf'  => 'Before {old.cpf}  and after {new.cpf}' 
	];
	
	...
}
```
Getting change logs 
```php
    
    // app\Http\Controllers\MyAppController.php 
    ...
    public function auditing()
    {
    	$people = People::find(1); // Get people
    	return View::make('auditing', ['logs' => $people->logs]); // Get logs
    }
    ...
    
```
Featuring log records:
```php
    // resources/views/my-app/auditing.blade.php
    ...
    <ol>
        @forelse($log as $logs)
            <li>
                {{ $log->customMessage }}
                <ul>
                    @forelse($custom as $log->customFields)
                        <li>$custom</li>
                    @endforelse
                </ul>
            </li>
        @empty
            <p>No logs</p>
        @endforelse
    </ol>
    ...
    
```
Answer:
<ol>
  <li>Jhon Doe been updated by Rafael      
    <ul>
      <li>Before Rafael and after Rafael França</li>
      <li>Before 00000000000 and after 11122233396</li>
    </ul>
  </li>                
  <li>...</li>
</ol>

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

