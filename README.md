

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

Add o seguinte `require` no arquivo composer.json do seu projeto:

```php
"owen-it/laravel-auditing": "1.*",
```

Execute o composer update para realizar o download do package:

```
php composer update
```

Registre o provider em "config/app.php":

```php
'providers' => [
    ...
    OwenIt\Auditing\AuditingServiceProvider::class,
],
```

> Não deixe de registrar o provider, pois ele é requisito para as proximas etapas.

Publique os arquivos referentes a migrations:

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

Para qualquer model que você quiser manter um log de alterações, basta incluir o namespace auditing e usar o `AuditingTrait` na sua model, ex:

```php
namespace MyApp\Models;

class Pessoa extends Eloquent {
    use \OwenIt\Auditing\AuditingTrait;

    public static function boot()
    {
        parent::boot();
    }
}
```

> Traits exigem PHP >= 5.4

### Implementação baseada em Legacy class

Para qualquer model que você quiser manter um log de alterações, basta incluir o namespace auditing e estender auditing em vez de eloquente, ex:

```php
use \OwenIt\Auditing\AuditingTrait;

namespace MyApp\Models;

class Pessoa extends Auditing { }
```
> Observe que também trabalha com models namespaced.

<a name="config"></a>
### Configurações

As configurações do comportamento do Auditing são realizadas com a declaração de atributos diretamente na na model. Veja os exemplos abaixo: 

* Você também pode desativar o log após um numero "X" com `$historyLimit = 500`
* Você pode desativar/ativar o log com `$auditEnabled = false`
* Você pode desativar o log de campos específicos com `$dontKeep = ['campo1', 'campo2']`

```php
namespace MyApp\Models;

class Pessoa extends Eloquent {
    use OwenIt\Auditing\AuditingTrait;

    protected $auditEnabled  = false;      //Desativa o registro de log nesta model.
    protected $historyLimit = 500;         //Desativa o registro de log após 500 registros.
    protected $dontKeep = ['cpf', 'nome']; //Informe os campos que deseja NÃO registrar no log.
}
```

<a name="consulta"></a>
## Consultando o Log

Para consutar o log, basta referenciar o método `logs()` na classe instanciada. Veja os exemplos abaixo: 

```php
$pessoa = \MyApp\Pessoa;
$pessoa->logs; //Busca todos os logs da model pessoa
```

```php
$pessoa = \MyApp\Pessoa;
$pessoa->logs->first(); //Pega o primeiro log da model pessoa
```

```php
$pessoa = \MyApp\Pessoa;
$pessoa->logs->last(); //Pega o último log da model pessoa
```

```php
$pessoa = \MyApp\Pessoa;
$pessoa->logs->find(2); //Pega um registro de log especifico da model pessoa
```

```php
$pessoa = \MyApp\Pessoa;
$log = $pessoa->logs->first();
$log->new_value; ou $log->new; //Pega os valores alterados do log
```

```php
use OwenIt\Auditing\Log;

$log = Log::find(1);
$log->owner; //Buscar o registro dono do log
```

```php
use OwenIt\Auditing\Log;

$log = Log::find(1);
$log->historyOf; ou $log->historyOf(); //Buscar registro responsavel pela história de logs
```


<a name="contributing"></a>
## Contribuindo

Contribuições são bem-vindas; para manter as coisas organizadas, todos os bugs e solicitações devem ser abertas na aba issues do github para o projeto principal, no [owen-it/laravel-auditing/issues](https://github.com/owen-it/laravel-auditing/issues)

Todos os pedidos de pull devem ser feitas para o branch develop, para que possam ser testados antes de serem incorporados pela branch master.

<a name="faq"></a>
## Tendo problemas?

Se você está tendo problemas com o uso deste pacote, existe probabilidade de alguém já ter enfrentado o mesmo problema. Você pode procurar respostas comuns para os seus problemas em:

* [Github Issues](https://github.com/owen-it/laravel-auditing/issues?page=1&state=closed)

