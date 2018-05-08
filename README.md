# Morphable Laravel Auditing
This fork was diverted of the original project to allow for the usecase where a project contains multiple different user models.

## Changes
- The config contains a new array for guards. The auditable package looks for the first active guard.
- The original 'UserSolver' has been renamed to 'UserIdResolve'.
- A new UserSolver and UserClassResolver have been added.

## Official Documentation
The package documentation can be found on the [official website](http://laravel-auditing.com) or at the [documentation repository](https://github.com/owen-it/laravel-auditing-doc/blob/master/documentation.md).

## Original Credits
- [Antério Vieira](https://github.com/anteriovieira)
- [Quetzy Garcia](https://github.com/quetzyg)
- [Raphael França](https://github.com/raphaelfranca)
- [All Contributors](https://github.com/owen-it/laravel-auditing/graphs/contributors)

## License
The **Laravel Auditing** package is open source software licensed under the [MIT LICENSE](LICENSE.md).
