

<img src="http://owen.com.br/imagens_para_web/auditing.png" style="width: 100%" alt="Revisionable" />

<a href="https://packagist.org/packages/owen-it/laravel-auditing">
    <img src="http://img.shields.io/github/tag/owen-it/laravel-auditing.svg?style=flat" style="vertical-align: text-top">
</a>
<a href="https://packagist.org/packages/owen-it/laravel-auditing">
    <img src="http://img.shields.io/packagist/dt/owen-it/laravel-auditing.svg?style=flat" style="vertical-align: text-top">
</a>

É sempre importante ter o histórico de alterações dos registros no sistema. O Auditing faz exatamente isso de forma simples e prática, bantando você extende-lo na model que gostaria de registrar o log de alterações. 

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
'OwenIt\Auditing\AuditingServiceProvider'
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

Observe que também trabalha com models namespaced.

Você também pode desativar o log após um numero "X" de registros, basta definir `$historyLimit` para a quantidade de registro no log que você deseja manter antes de parar de registrar o log.

```php
namespace MyApp\Models;

class Pessoa extends Eloquent {
    use OwenIt\Auditing\AuditingTrait;

    protected $revisionEnabled = true;
    protected $historyLimit = 500; //Para o registro de log após 500 registros.
}
```

<a name="contributing"></a>
## Contribuindo

Contribuições são bem-vindas; para manter as coisas organizadas, todos os bugs e solicitações devem ser abertas na aba issues do github para o projeto principal, no [owen-it/laravel-auditing/issues](https://github.com/owen-it/laravel-auditing/issues)

Todos os pedidos de pull devem ser feitas para o branch develop, para que possam ser testados antes de serem incorporados pela branch master.

<a name="faq"></a>
## Tendo problemas?

Se você está tendo problemas com o uso deste pacote, existe probabilidade de alguém já ter enfrentado o mesmo problema. Você pode procurar respostas comuns para os seus problemas em:

* [Github Issues](https://github.com/owen-it/laravel-auditing/issues?page=1&state=closed)

