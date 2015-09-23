

<img src="http://owen.com.br/imagens_para_web/auditing.png" style="width: 100%" alt="laravel-auditing" />

[![Latest Stable Version](https://poser.pugx.org/owen-it/laravel-auditing/version)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Total Downloads](https://poser.pugx.org/owen-it/laravel-auditing/downloads)](https://packagist.org/packages/owen-it/laravel-auditing)
[![Latest Unstable Version](https://poser.pugx.org/owen-it/laravel-auditing/v/unstable)](//packagist.org/packages/owen-it/laravel-auditing)
[![License](https://poser.pugx.org/owen-it/laravel-auditing/license.svg)](https://packagist.org/packages/owen-it/laravel-auditing)

É sempre importante ter o histórico de alterações dos registros no sistema. O Auditing faz exatamente isso de forma simples e prática, bastando você extende-lo na model que gostaria de registrar o log de alterações. 

> Auditing é baseado no package  [revisionable](https://packagist.org/packages/VentureCraft/revisionable)

## Installation

Auditing is installed with [composer](http://getcomposer.org/doc/00-intro.md), the details are  [here](https://packagist.org/packages/owen-it/laravel-auditing).

Execute o seguinte comando para obter a versão mais recente do pacote:

```
composer require owen-it/laravel-auditing
```

Em seu arquivo `config/app.php` adicione `OwenIt\Auditing\AuditingServiceProvider::class` no final da lista de `providers`:

```php
'providers' => [
    ...
    OwenIt\Auditing\AuditingServiceProvider::class,
],
```

> Não deixe de registrar o provider, pois ele é requisito para as proximas etapas.

Publique as configurações usando o comando a seguir:

```
php artisan vendor:publish
```

Agora vc precisa executar a migration para criar a tabela 'logs' na sua base de dados, é nesta tabela que serão registrados os logs.

```
php artisan migrate
```


## Docs
* [Dreams (Exemplo)](#dreams)
* [Implementação](#intro)
* [Configuração](#config)
* [Consultando o Log](#consulta)
* [Apresentando Log](#apresentacao)
* [Contribuindo](#contributing)
* [Tendo problemas?](#faq)


<a name="dreams"></a>
## Dreams (Exemplo)
Dreams é uma api  desenvolvida para servir como exemplo ou direcionamento para desenvolvedores que utilizam laravel-auditing. Você pode acessar a aplicação [aqui](https://dreams-.herokuapp.com). O back-end (api) foi desenvolvido em laravel 5.1 e o front-end (app) em AngularJs, os detalhe estãos logo abaixo:
* [Link para aplicação](https://dreams-.herokuapp.com) 
* [Código fonte api-dreams](https://github.com/owen-it/api-dreams)
* [Código fonte app-dreams](https://github.com/owen-it/app-dreams)

<a name="intro"></a>
## Implementação

### Implementação baseada em Trait

Para registrar o log de alterações, simplesmente adicione a trait `OwnerIt\Auditing\AuditingTrait` no model que deseja auditar, exemplo:

```php
// app/Models/Pessoa.php

namespace App\Models;

use OwenIt\Auditing\AuditingTrait;

class Pessoa extends Eloquent 
{
    use AuditingTrait;
    ...
}
```

> Nota: Traits exigem PHP >= 5.4

### Implementação baseada em Legacy class

Para manter o log das alterações do seu model usando Legacy class, você pode estender a class `OwnerIt\Auditing\Auditing`, exemplo:

```php
// app/Models/Pessoa.php

namespace App\Models;

use OwenIt\Auditing\Auditing;

class Pessoa extends Auditing 
{
    ...    
}
```
> Nota: Observe que também trabalha com models namespaced.

<a name="config"></a>
### Configurações

As configurações do comportamento do Auditing são realizadas com a declaração de atributos na model. Veja os exemplos abaixo: 

* Desativar o log após um numero "X": `$historyLimit = 500`
* Desativar/ativar o log(Auditoria): `$auditEnabled = false`
* Desativar o log para campos específicos: `$dontKeep = ['campo1', 'campo2']`

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model 
{
    use OwenIt\Auditing\AuditingTrait;

    protected $auditEnabled  = false;      // Desativa o registro de log nesta model.
    protected $historyLimit = 500;         // Desativa o registro de log após 500 registros.
    protected $dontKeep = ['cpf', 'nome']; // Informe os campos que NÃO deseja registrar no log.
    protected $auditableTypes = ['created', 'saved', 'deleted']; // Informe quais ações deseja auditar
}
```

<a name="consulta"></a>
## Consultando o Log

```php
namespace App\Http\Controllers;

use App\Pessoa;

class MyAppController extends BaseController 
{

    public function index()
    {
        $pessoa = Pessoa::find(1); // Obtem pessoa
        $pessoa->logs; // Obtém todos os logs 
        $pessoa->logs->first(); // Obtém o primeiro log registrado
        $pessoa->logs->last();  // Obtém primeiro log registrado
        $pessoa->logs->find(2); // Seleciona log
    }

    ...
}
```

Obtendo logs com usuário responsável pela alteração e o model auditado
```php
use OwenIt\Auditing\Log;

$logs = Log::with(['owner', 'user'])->get();

```
> Nota: Lembre-se de definir corretamente o model do usuário no arquivo ``` config/auth.php ```
>```php
> ...
> 'model' => App\User::class,
> ... 
>```

<a name="apresentacao"></a>
## Apresentando log

É possível definir mensagens personalizadas para apresentação dos logs. Essas mensagens podem ser definidas tanto para o modelo como para campos especificos. A parte dinâmica da mensagem pode ser feita através de campos segmentados por ponto encapsulados por chaves `{objeto.campo}`. 

Defina as mensagens para o modelo:
```php
namespace App;

use OwenIt\Auditing\Auditing;

class Pessoa extends Auditing 
{
    ...

	public static $logCustomMessage = '{user.nome} atualizou os dados de {old.nome}';
	public static $logCustomFields = [
	    'nome' => 'Antes {old.nome} | Depois {new.nome}',
	    'cpf'  => 'Antes {old.cpf}  | Depois {new.cpf}' 
	];
	
	...
}
```
Obtendo registros de logs:
```php
    
    // app\Http\Controllers\MyAppController.php 
    ...
    public function auditing()
    {
    	$pessoa = Pessoa::find(1); // Obtem pessoa
    	return View::make('auditing', ['logs' => $pessoa->logs]); // Obtendo logs
    }
    ...
    
```
Apresentando registros de log:
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
Resposta:
<ol>
  <li>Jhon Doe atualizou os dados de Rafael      
    <ul>
      <li>Antes Rafael | Depois Rafael França</li>
      <li>Antes 00000000000 | Depois 11122233396 </li>
    </ul>
  </li>                
  <li>...</li>
</ol>

<a name="contributing"></a>
## Contribuindo

Contribuições são bem-vindas; para manter as coisas organizadas, todos os bugs e solicitações devem ser abertas na aba issues do github para o projeto principal, no [owen-it/laravel-auditing/issues](https://github.com/owen-it/laravel-auditing/issues)

Todos os pedidos de pull devem ser feitas para o branch develop, para que possam ser testados antes de serem incorporados pela branch master.

<a name="faq"></a>
## Tendo problemas?

Se você está tendo problemas com o uso deste pacote, existe probabilidade de alguém já ter enfrentado o mesmo problema. Você pode procurar respostas comuns para os seus problemas em:

* [Github Issues](https://github.com/owen-it/laravel-auditing/issues?page=1&state=closed)

### Licença

O pacote laravel-auditoria é software open-source licenciado sob a [licença MIT](http://opensource.org/licenses/MIT)

