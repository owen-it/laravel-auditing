

<img src="http://owen.com.br/imagens_para_web/auditing.png" style="width: 100%" alt="Revisionable" />

<a href="https://packagist.org/packages/owen-it/laravel-auditing">
    <img src="http://img.shields.io/github/tag/owen-it/laravel-auditing.svg?style=flat" style="vertical-align: text-top">
</a>
<a href="https://packagist.org/packages/owen-it/laravel-auditing">
    <img src="http://img.shields.io/packagist/dt/owen-it/laravel-auditing.svg?style=flat" style="vertical-align: text-top">
</a>

É sempre importante ter o histórico de alterações dos registros no sistema. O Auditing faz exatamente isso de forma simples e prática, bastando você extende-lo na model que gostaria de registrar o log de alterações. 

> Auditing é baseado no package  [revisionable](https://packagist.org/packages/VentureCraft/revisionable)

## Instalação

Auditing é instalado via [composer](http://getcomposer.org/doc/00-intro.md), os detalhes estão em [packagist, aqui.](https://packagist.org/packages/owen-it/laravel-auditing)

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

Publique migrations usando o comando a seguir:

```
php artisan vendor:publish
```

Agora vc precisa executar a migration para criar a tabela 'logs' na sua base de dados, é nesta tabela que serão registrados os logs.

```
php artisan migrate
```


## Docs

* [Implementação](#intro)
* [Configuração](#config)
* [Consultando o Log](#consulta)
* [Contribuindo](#contributing)
* [Tendo problemas?](#faq)

<a name="intro"></a>
## Implementação

### Implementação baseada em Trait

Para manter o log das alterações do seu model, simplesmente adicione a trait `OwnerIt\Auditing\AuditingTrait`, exemplo:

```php
namespace MyApp\Models;

use OwenIt\Auditing\AuditingTrait;

class Pessoa extends Eloquent 
{
    use AuditingTrait;
    ...
}
```

> Traits exigem PHP >= 5.4

### Implementação baseada em Legacy class

Para manter o log das alterações do seu model usando Legacy class, você pode estender a class `OwnerIt\Auditing\Auditing`, exemplo:

```php
namespace MyApp\Models;

use OwenIt\Auditing\Auditing;

class Pessoa extends Auditing 
{
    ...    
}
```
> Observe que também trabalha com models namespaced.

<a name="config"></a>
### Configurações

As configurações do comportamento do Auditing são realizadas com a declaração de atributos na model. Veja os exemplos abaixo: 

* Desativar o log após um numero "X": `$historyLimit = 500`
* Desativar/ativar o log(Auditoria): `$auditEnabled = false`
* Desativar o log para campos específicos: `$dontKeep = ['campo1', 'campo2']`

```php
namespace MyApp\Models;

class Pessoa extends Eloquent 
{
    use OwenIt\Auditing\AuditingTrait;

    protected $auditEnabled  = false;      //Desativa o registro de log nesta model.
    protected $historyLimit = 500;         //Desativa o registro de log após 500 registros.
    protected $dontKeep = ['cpf', 'nome']; //Informe os campos que deseja NÃO registrar no log.
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
        $pessoa = Pessoa::find(1);
        ...
    }

    ...
}
```
Localizando todos os logs
```php
$pessoa->logs; 
```
Localiza o primeiro registro de log criado
```php
$pessoa->logs->first(); 
```
Localiza o último registro de log criado
```php
$pessoa->logs->last(); 
```
Selecionando registro de log
```php
$pessoa->logs->find(2); 
```
Exibindo valores registrados
```php
$log = $pessoa->logs->first();
$newValue = $log->new_value; ou $log->new;
$oldValue = $log->old_value; ou $log->old;
```
Localizando dono do log
```php
use OwenIt\Auditing\Log;

$log = Log::find(1);
$owner = $log->owner;
```

<a name="contributing"></a>
## Contribuindo

Contribuições são bem-vindas; para manter as coisas organizadas, todos os bugs e solicitações devem ser abertas na aba issues do github para o projeto principal, no [owen-it/laravel-auditing/issues](https://github.com/owen-it/laravel-auditing/issues)

Todos os pedidos de pull devem ser feitas para o branch develop, para que possam ser testados antes de serem incorporados pela branch master.

<a name="faq"></a>
## Tendo problemas?

Se você está tendo problemas com o uso deste pacote, existe probabilidade de alguém já ter enfrentado o mesmo problema. Você pode procurar respostas comuns para os seus problemas em:

* [Github Issues](https://github.com/owen-it/laravel-auditing/issues?page=1&state=closed)

