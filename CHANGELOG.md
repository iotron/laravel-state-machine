# Changelog

All notable changes to `laravel-state-machine` will be documented in this file.

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
