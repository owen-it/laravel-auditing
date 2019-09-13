## v9.0.0 (2019-03-02)
### Added
- Illuminate 5.8 support

## v8.0.4 (2018-11-20)
### Changed
- Make the `auditable()` and `user()` method return types loose

## v8.0.3 (2018-10-25)
### Fixed
- Cast `auditable_id` property by default to avoid `transitionTo()` errors ([#432](https://github.com/owen-it/laravel-auditing/issues/432))

## v8.0.2 (2018-10-02)
### Fixed
- Lumen compatibility issue ([#447](https://github.com/owen-it/laravel-auditing/issues/447))

## v8.0.1 (2018-08-28)
### Added
- Illuminate 5.7 support

## v8.0.0 (2018-08-13)
### Added
- `AttributeModifier` and `AttributeEncoder` interfaces ([#437](https://github.com/owen-it/laravel-auditing/pull/437))

### Changed
- `AttributeRedactor` replaces `AuditRedactor`

### Removed
- `audit.redact` boolean configuration entry
- Unnecessary `Artisan` commands for publishing the configuration/migration

## v7.0.1 (2018-06-29)
### Fixed
- Illuminate 5.2 compatibility issues ([#431](https://github.com/owen-it/laravel-auditing/pull/431))

## v7.0.0 (2018-05-12)
### Added
- Audit Multi User feature ([#421](https://github.com/owen-it/laravel-auditing/pull/421))

## v6.1.1 (2018-04-27)
### Fixed
- Audit presentation issue when using `trans()` or `@lang()` ([#418](https://github.com/owen-it/laravel-auditing/issues/418))

## v6.1.0 (2018-04-23)
### Added
- Audit redactor feature ([#395](https://github.com/owen-it/laravel-auditing/issues/395))

### Changed
- Minor optimisations
- Increase test coverage to 100%

### Fixed
- `deleted_at` attribute exclusion from the `Audit`
- `InvalidArgumentExceptionTrailing` when using a different `$dateFormat` ([#409](https://github.com/owen-it/laravel-auditing/pull/409))

## v6.0.2 (2018-04-02)
### Changed
- Minor optimisations

### Fixed
- Illuminate 5.2/5.3 incompatibility issue ([#401](https://github.com/owen-it/laravel-auditing/issues/401))

## v6.0.1 (2018-02-15)
### Added
- Ability to quickly enable/disable auditing ([#387](https://github.com/owen-it/laravel-auditing/issues/387))

## v6.0.0 (2018-02-09)
### Added
- Resolver classes & interfaces for _IP Address_, _URL_, _User Agent_ and _User_ ([#369](https://github.com/owen-it/laravel-auditing/issues/369))
- Laravel 5.6 support
- Scrutinizer CI integration

### Changed
- Rename UserResolver method to `resolve()`
- Updated the configuration file structure to accommodate the new resolvers
- Refactor the `prune()` method from the Database driver
- Increase test coverage
- Updated dev dependencies

### Fixed
- Hardcode the default AuditDriver value in the Auditor to avoid chicken/egg situation

## v5.0.4 (2018-02-06)
### Fixed
- Issue with Auditable resolveUserAgent() method ([#372](https://github.com/owen-it/laravel-auditing/issues/372))

## v5.0.3 (2017-12-28)
### Fixed
- Lumen installation issue ([#364](https://github.com/owen-it/laravel-auditing/issues/364))

## v5.0.2 (2017-12-09)
### Fixed
- Bump the minimum PHP version required to 7.0.13 ([#354](https://github.com/owen-it/laravel-auditing/issues/354))
- Take MorphMap into account ([#357](https://github.com/owen-it/laravel-auditing/issues/357))

## v5.0.1 (2017-11-30)
### Fixed
- Typo in the migration stub ([#356](https://github.com/owen-it/laravel-auditing/pull/356))

## v5.0.0 (2017-11-28)
### Added
- Custom exceptions
- Ability to tag audits ([#283](https://github.com/owen-it/laravel-auditing/issues/283))
- New `transitionTo()` and `getAuditEvent()` methods to the `Auditable` contract
- Support for the `retrieved` Eloquent event, added in v5.5 ([#343](https://github.com/owen-it/laravel-auditing/issues/343))

### Changed
- Use PHP 7 features (scalar type/return type declarations, null coalescing operator)
- Improved testing
- Honour `DateTime` attributes, when resolving `Audit` data
- Rename `getAuditableEvents()` to `getAuditEvents()`
- Allow setting global `Audit` events ([#342](https://github.com/owen-it/laravel-auditing/pull/342)), strict, threshold and timestamps in the configuration file

### Removed
- PHP 5.x support
- `Closure` / `callable` support for User id resolver

## v4.1.4 (2017-10-29)
### Added
- Ability to define custom methods to handle events ([#324](https://github.com/owen-it/laravel-auditing/pull/324))

## v4.1.3 (2017-09-17)
### Added
- CONTRIBUTING document

### Changed
- Updated the URL column type from VARCHAR to TEXT in the migration stub

### Fixed
- Set the default value of the resolver to a FQCN, instead of a Closure ([#290](https://github.com/owen-it/laravel-auditing/issues/290))

## v4.1.2 (2017-08-03)
### Changed
- [GitHub] Updated issue template

### Fixed
- [Audit] Simplify User relation check ([#282](https://github.com/owen-it/laravel-auditing/issues/282))

## v4.1.1 (2017-07-22)
### Changed
- [Audit] Improve test coverage

### Fixed
- [composer] stricter dependency version support ([#269](https://github.com/owen-it/laravel-auditing/pull/269))
- [Audit] Make sure the User relation is set before fetching attributes ([#276](https://github.com/owen-it/laravel-auditing/pull/276))

## v4.1.0 (2017-07-09)
### Added
- Implemented Audit contract, enabling classes to extend other model types ([#211](https://github.com/owen-it/laravel-auditing/issues/211))
- The `updated_at` attribute is now part of the Audit model. Don't forget to update your `audits` table!
- Added Laravel 5.5 Auto-Discovery support

### Fixed
- Allow the User primary and foreign key to be specified in the configuration ([#251](https://github.com/owen-it/laravel-auditing/issues/251))

## v4.0.7 (2017-06-04)
### Added
- GitHub issue template file

### Fixed
- Properly fixed issue ([#233](https://github.com/owen-it/laravel-auditing/issues/233))

## v4.0.6 (2017-05-21)
### Fixed
- Calling a member function on null ([#244](https://github.com/owen-it/laravel-auditing/issues/244))

## v4.0.5 (2017-05-03)
### Fixed
- Removed problematic ORDER BY from the audits() relation method in the Auditable trait

## v4.0.4 (2017-05-01)
### Added
- Log the user agent string ([#224](https://github.com/owen-it/laravel-auditing/issues/224))

### Changed
- Updated migration stub to use the DB driver ([#220](https://github.com/owen-it/laravel-auditing/issues/220))

### Fixed
- Wrong class name for custom audit drivers ([#226](https://github.com/owen-it/laravel-auditing/issues/226))
- Use standards compliant SQL ([#225](https://github.com/owen-it/laravel-auditing/issues/225))
- Prevent creating an updated audit when restoring a model ([#233](https://github.com/owen-it/laravel-auditing/issues/233))

## v4.0.3 (2017-03-21)
### Added
- Changelog file

### Fixed
- Removal count in Database driver ([#215](https://github.com/owen-it/laravel-auditing/issues/215))

## v4.0.2 (2017-03-18)
### Added
- `OwenIt\Auditing\Contracts\UserResolver` interface
- More `Auditable` tests

### Fixed
- Non auditable events cause a `RuntimeException` to be thrown ([#212](https://github.com/owen-it/laravel-auditing/issues/212))
- `Callable` values prevent the configuration from being cached ([#213](https://github.com/owen-it/laravel-auditing/issues/213))

## v4.0.1 (2017-03-15)
### Added
- Dynamic attribute getters
- More `Auditable` tests

### Fixed
- Trait attributes can't be overridden by class implementing `Auditable` ([#205](https://github.com/owen-it/laravel-auditing/issues/205))
- Branch alias

## v4.0.0 (2017-03-11)
### Changed
- Cleaner codebase
- Better test coverage
- `Auditable` attribute mutators and casts will be honoured

### Fixed
- Only modified attributes are stored in the `Audit`
- Lumen support

### Removed
- Queue support
- `Auditable` model custom messages/fields
