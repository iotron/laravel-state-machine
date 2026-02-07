# Laravel State Machine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iotron/laravel-state-machine.svg?style=flat-square)](https://packagist.org/packages/iotron/laravel-state-machine)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iotron/laravel-state-machine/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iotron/laravel-state-machine/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/iotron/laravel-state-machine.svg?style=flat-square)](https://packagist.org/packages/iotron/laravel-state-machine)

A robust, enum-aware state machine for Laravel Eloquent models. Define allowed transitions, validate before state changes, track full history, and prevent N+1 queries — all with native PHP BackedEnum support and zero dependencies beyond Laravel.

## Features

- **Native BackedEnum support** — use enums everywhere, normalized internally
- **N+1 query prevention** — eager-load history, get zero-query lookups in loops
- **Transaction-safe transitions** — model save + history recording are atomic
- **Lifecycle events** — `TransitionStarted`, `TransitionCompleted`, `TransitionFailed`
- **Validation hooks** — block invalid transitions with Laravel Validator
- **Before/after hooks** — run closures on specific state changes
- **Pending transitions** — schedule future state changes with jobs
- **History tracking** — full audit trail with custom properties and changed attributes
- **Safe auth resolution** — no crashes in queue/CLI contexts
- **Artisan generator** — `php artisan make:state-machine`

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require iotron/laravel-state-machine
```

Publish the config file:

```bash
php artisan vendor:publish --tag=state-machine-config
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=state-machine-migrations
php artisan migrate
```

> **Migrating from `asantibanez/laravel-eloquent-state-machines`?** See the [Migration Guide](#migrating-from-asantibanezlaravel-eloquent-state-machines) below — no database changes needed.

## Quick Start

### 1. Create a State Machine

```bash
php artisan make:state-machine OrderStatusStateMachine
```

Define your transitions and default state:

```php
namespace App\StateMachines;

use App\Enums\OrderStatus;
use Iotron\StateMachine\StateMachines\StateMachine;

class OrderStatusStateMachine extends StateMachine
{
    public function transitions(): array
    {
        return [
            'pending'    => ['confirmed', 'cancelled'],
            'confirmed'  => ['dispatched', 'cancelled'],
            'dispatched' => ['delivered'],
        ];
    }

    public function defaultState(): ?string
    {
        return OrderStatus::PENDING->value;
    }
}
```

### 2. Add the Trait to Your Model

```php
use Iotron\StateMachine\Concerns\HasStateMachines;

class Order extends Model
{
    use HasStateMachines;

    public $stateMachines = [
        'status' => OrderStatusStateMachine::class,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class, // native enum cast works!
        ];
    }
}
```

### 3. Use It

```php
$order = Order::create();

// Query state
$order->status()->is(OrderStatus::PENDING);       // true
$order->status()->canBe(OrderStatus::CONFIRMED);   // true
$order->status()->canBe(OrderStatus::DELIVERED);   // false

// Transition
$order->status()->transitionTo(OrderStatus::CONFIRMED);

// History
$order->status()->was(OrderStatus::PENDING);        // true
$order->status()->timesWas(OrderStatus::PENDING);    // 1
$order->status()->whenWas(OrderStatus::CONFIRMED);   // Carbon
$order->status()->snapshotWhen(OrderStatus::CONFIRMED); // Transition model

// Custom properties
$order->status()->transitionTo('dispatched', ['tracking' => 'ABC123']);
$order->status()->getCustomProperty('tracking'); // 'ABC123'
```

## Configuration

```php
// config/state-machine.php
return [
    'tables' => [
        'transitions'         => 'state_histories',    // history table name
        'pending_transitions' => 'pending_transitions',
    ],
    'record_changed_attributes'    => true,  // capture dirty attributes on transition
    'cancel_pending_on_transition' => true,  // auto-cancel pending when transitioning
];
```

## Defining State Machines

### Transitions

The `transitions()` method returns a map of `from => [allowed targets]`:

```php
public function transitions(): array
{
    return [
        'draft'     => ['pending', 'cancelled'],
        'pending'   => ['approved', 'rejected'],
        'approved'  => ['published'],
        // Wildcard support
        '*'         => ['archived'],  // any state can go to archived
    ];
}
```

### Default State

Set the initial state for new models:

```php
public function defaultState(): ?string
{
    return 'draft';
    // or: return MyEnum::DRAFT->value;
}
```

### Record History

Control whether transitions are tracked (default: `true`):

```php
public function recordHistory(): bool
{
    return true;
}
```

### Validation

Return a `Validator` to block transitions that don't meet requirements:

```php
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

public function validatorForTransition($from, $to, $model): ?Validator
{
    if ($to === 'published') {
        $validator = ValidatorFacade::make([], []);

        if (! $model->title) {
            $validator->after(fn ($v) => $v->errors()->add(
                'title', 'A title is required before publishing.'
            ));
        }

        return $validator;
    }

    return null; // no validation for other transitions
}
```

If the validator fails, a `ValidationException` is thrown and the model stays unchanged.

### Before/After Hooks

Run closures when entering or leaving specific states. All hooks receive `($from, $to, $model)`:

```php
public function beforeTransitionHooks(): array
{
    return [
        'published' => [ // keyed by the FROM state
            function (string $from, string $to, Model $model) {
                // Runs before leaving 'published'
            },
        ],
    ];
}

public function afterTransitionHooks(): array
{
    return [
        'confirmed' => [ // keyed by the TO state
            function (string $from, string $to, Model $model) {
                $model->update(['confirmed_at' => now()]);
            },
        ],
    ];
}
```

## State Proxy API

Calling `$model->status()` returns a `State` proxy with these methods:

| Method | Returns | Description |
|--------|---------|-------------|
| `state()` | `string` | Current state (normalized to string) |
| `is($state)` | `bool` | Check if current state matches |
| `isNot($state)` | `bool` | Check if current state doesn't match |
| `canBe($state)` | `bool` | Check if transition is allowed |
| `transitionTo($state, $props, $responsible)` | `void` | Execute transition |
| `postponeTransitionTo($state, $when, ...)` | `?PendingTransition` | Schedule future transition |
| `was($state)` | `bool` | Ever been in this state? |
| `timesWas($state)` | `int` | Count times in this state |
| `whenWas($state)` | `?Carbon` | When last entered this state |
| `snapshotWhen($state)` | `?Transition` | Transition record for a state |
| `snapshotsWhen($state)` | `Collection` | All records for a state |
| `history()` | `Builder` | Query builder for this field's history |
| `latest()` | `?Transition` | Most recent transition to current state |
| `getCustomProperty($key)` | `mixed` | Custom property from latest transition |
| `responsible()` | `?Model` | User who triggered latest transition |
| `allCustomProperties()` | `array` | All custom properties from latest |
| `pendingTransitions()` | `Builder` | Query pending transitions |
| `hasPendingTransitions()` | `bool` | Any pending transitions? |

All methods accept both strings and `BackedEnum` values.

## N+1 Prevention

When you eager-load `stateHistory`, all history lookups use the in-memory collection — zero extra queries:

```php
// 2 queries total: models + stateHistory
$orders = Order::with('stateHistory')->get();

// 0 additional queries for any number of models
foreach ($orders as $order) {
    $order->status()->was(OrderStatus::PENDING);
    $order->status()->timesWas(OrderStatus::CONFIRMED);
    $order->status()->snapshotWhen(OrderStatus::DISPATCHED);
}
```

Without eager loading, each call falls back to a database query automatically.

## Events

Three events fire during transitions for app-wide listening:

| Event | When | Payload |
|-------|------|---------|
| `TransitionStarted` | Before hooks fire | `$model`, `$field`, `$from`, `$to` |
| `TransitionCompleted` | After everything succeeds | `$model`, `$field`, `$from`, `$to` |
| `TransitionFailed` | On any exception | `$model`, `$field`, `$from`, `$to`, `$exception` |

```php
// In a listener or EventServiceProvider
use Iotron\StateMachine\Events\TransitionCompleted;

Event::listen(TransitionCompleted::class, function (TransitionCompleted $event) {
    if ($event->field === 'status' && $event->to === 'published') {
        // Send notification, dispatch job, etc.
    }
});
```

## Pending Transitions

Schedule transitions to execute in the future:

```php
$order->status()->postponeTransitionTo('dispatched', Carbon::tomorrow());
```

Add the dispatcher job to your scheduler:

```php
// bootstrap/app.php or routes/console.php
use Iotron\StateMachine\Jobs\DispatchPendingTransitions;

Schedule::job(new DispatchPendingTransitions)->everyMinute();
```

The job processes pending transitions in chunks and dispatches each as a separate queued job for reliability.

## Transition Model

The `Transition` model (stored in the `state_histories` table by default) includes useful scopes:

```php
use Iotron\StateMachine\Models\Transition;

// Query scopes
Transition::forField('status')->to('published')->get();
Transition::withTransition('pending', 'published')->get();
Transition::withCustomProperty('reason', '=', 'approved')->get();
Transition::withResponsible($user)->get();

// Instance methods
$transition->getCustomProperty('key');
$transition->allCustomProperties();
$transition->changedAttributesNames();
$transition->changedAttributeOldValue('title');
$transition->changedAttributeNewValue('title');
```

## Migrating from asantibanez/laravel-eloquent-state-machines

This package is a drop-in replacement. No database migration needed — it reads the same `state_histories` table by default.

### Step 1: Install

```bash
composer require iotron/laravel-state-machine
```

### Step 2: Update model imports

```diff
- use Asantibanez\LaravelEloquentStateMachines\Traits\HasStateMachines;
+ use Iotron\StateMachine\Concerns\HasStateMachines;
```

### Step 3: Update state machine base class

```diff
- use Asantibanez\LaravelEloquentStateMachines\StateMachines\StateMachine;
+ use Iotron\StateMachine\StateMachines\StateMachine;
```

### Step 4: Update hook signatures

The old package used `($from, $model)` / `($to, $model)`. This package uses a consistent `($from, $to, $model)` for both before and after hooks:

```diff
  public function afterTransitionHooks(): array
  {
      return [
          'confirmed' => [
-             function ($from, $model) {
+             function ($from, $to, $model) {
                  $model->update(['confirmed_at' => now()]);
              },
          ],
      ];
  }
```

### Step 5: Remove old packages

```bash
composer remove asantibanez/laravel-eloquent-state-machines javoscript/laravel-macroable-models
```

### Step 6: Enable native enum casts

You can now use Laravel's native enum cast — no more workarounds:

```php
protected function casts(): array
{
    return [
        'status' => OrderStatus::class, // just works!
    ];
}
```

### What's different?

| Feature | Old Package | This Package |
|---------|------------|--------------|
| Enum support | Manual workarounds | Native BackedEnum |
| N+1 prevention | Not built-in | Built-in via eager loading |
| Transaction safety | No wrapping | `DB::transaction()` |
| Hook arguments | Inconsistent `($to, $model)` / `($from, $model)` | Consistent `($from, $to, $model)` |
| Events | None | 3 lifecycle events |
| Auth in queues | Crashes | Safe fallback |
| Dependencies | Requires `laravel-macroable-models` | Zero external deps |
| Method resolution | Static macros via reflection | Native `__call()` |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
