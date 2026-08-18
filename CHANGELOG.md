# Changelog

All notable changes to `laravel-state-machine` will be documented in this file.

## v1.0.1 - Laravel 13 Support - 2026-08-18

### v1.0.1 - Laravel 13 Support & Transaction Safety

#### What's New

- **Laravel 13 Support**: Full compatibility with Laravel 13
- **Improved Transaction Handling**: State machine transitions now intelligently detect and reuse existing database transactions, preventing unwanted nested savepoints that could cause issues in some database configurations

#### Changes

- Added Laravel 13 to CI matrix and Composer requirements
- Updated workflow to skip PHP 8.2 for Laravel 13 (per framework requirements)
- Enhanced `StateMachine` to check `DB::transactionLevel()` before creating new transactions
- Tightened `State` class typing with proper `Collection` imports
- Improved test suite with:
  - Carbon imports for better time handling
  - Simplified Hook fixture references
  - New `TransactionBoundaryTest` to verify correct transaction boundary behavior
  - Enhanced enum support and history tracking tests
  

#### Why This Matters

This release ensures that state machine transitions play nicely with your application's existing transaction boundaries. If your code already wraps transitions in a database transaction, the state machine will now execute within that transaction instead of creating a nested one—resulting in cleaner, more predictable behavior.

#### Compatibility

- **Laravel**: 11.x, 12.x, 13.x
- **PHP**: 8.2, 8.3, 8.4
- **Database**: MySQL, PostgreSQL, SQLite

#### Credits

Drop-in replacement for [asantibanez/laravel-eloquent-state-machines](https://github.com/asantibanez/laravel-eloquent-state-machines)

## v1.0.0 - 2026-02-07

### Initial Release

A robust, enum-aware state machine for Laravel Eloquent models.

#### Features

- Native PHP BackedEnum support with automatic normalization
- N+1 query prevention via eager-loaded relationship detection
- Transaction-safe transitions with `DB::transaction()`
- Lifecycle events: `TransitionStarted`, `TransitionCompleted`, `TransitionFailed`
- Consistent hook signatures: `fn($from, $to, $model)`
- Safe auth resolution for queue/CLI contexts
- Pending transitions with scheduled execution jobs
- Artisan `make:state-machine` generator command
- 46 tests, 92 assertions

#### Requirements

- PHP 8.2+
- Laravel 11 or 12

#### Installation

```bash
composer require iotron/laravel-state-machine


```
See the [README](https://github.com/iotron/laravel-state-machine#readme) for full documentation.

## 1.0.0 - 2026-02-07

- Initial release
- Native BackedEnum support with automatic normalization
- N+1 query prevention via eager-loaded relationship detection
- Transaction-safe transitions with DB::transaction()
- Lifecycle events: TransitionStarted, TransitionCompleted, TransitionFailed
- Consistent hook signatures: fn($from, $to, $model)
- Safe auth resolution for queue/CLI contexts
- Pending transitions with scheduled execution jobs
- Artisan make:state-machine generator command
- Full test suite (46 tests, 92 assertions)
