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
