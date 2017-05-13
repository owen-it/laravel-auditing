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
