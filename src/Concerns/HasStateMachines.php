<?php

namespace Iotron\StateMachine\Concerns;

use Illuminate\Database\Eloquent\Model;
use Iotron\StateMachine\Models\PendingTransition;
use Iotron\StateMachine\Models\Transition;
use Iotron\StateMachine\StateMachines\State;

/**
 * Trait HasStateMachines
 *
 * Add this trait to any Eloquent model that uses state machines.
 * Define the $stateMachines property mapping fields to StateMachine classes.
 *
 * @property array $stateMachines
 */
trait HasStateMachines
{
    /**
     * Cache State instances per field to avoid rebuilding each call.
     */
    protected array $stateInstances = [];

    public static function bootHasStateMachines(): void
    {
        static::creating(fn (Model $model) => $model->initStateMachines());
        static::created(fn (Model $model) => $model->recordInitialStates());
    }

    /**
     * Dynamically resolve $model->fieldName() calls to State instances
     * for any field registered in $stateMachines.
     */
    public function __call($method, $parameters)
    {
        if (isset($this->stateMachines[$method])) {
            return $this->getStateFor($method);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Set default state values on creating.
     */
    public function initStateMachines(): void
    {
        collect($this->stateMachines)
            ->each(function ($stateMachineClass, $field) {
                $stateMachine = new $stateMachineClass($field, $this);
                $defaultState = $stateMachine->defaultState();

                // Normalize BackedEnum default state
                if ($defaultState instanceof \BackedEnum) {
                    $defaultState = $defaultState->value;
                }

                $this->{$field} = $this->{$field} ?? $defaultState;
            });
    }

    /**
     * Record initial state history after model creation.
     */
    public function recordInitialStates(): void
    {
        collect($this->stateMachines)
            ->each(function ($_, $field) {
                $currentState = $this->getOriginal($field) ?? $this->{$field};
                $stateMachine = $this->getStateFor($field)->stateMachine();

                if ($currentState === null || ! $stateMachine->recordHistory()) {
                    return;
                }

                // Normalize enum to string
                if ($currentState instanceof \BackedEnum) {
                    $currentState = $currentState->value;
                }

                $responsible = $this->resolveResponsibleSafely();

                $changedAttributes = $this->getChangedAttributes();

                $this->recordState($field, null, $currentState, [], $responsible, $changedAttributes);
            });
    }

    /**
     * Get changed attributes in old/new format for history tracking.
     */
    public function getChangedAttributes(): array
    {
        return collect($this->getDirty())
            ->mapWithKeys(fn ($_, $attribute) => [
                $attribute => [
                    'new' => data_get($this->getAttributes(), $attribute),
                    'old' => data_get($this->getOriginal(), $attribute),
                ],
            ])
            ->toArray();
    }

    // ──────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────

    /**
     * State history / transitions relationship.
     */
    public function stateHistory()
    {
        return $this->morphMany(Transition::class, 'model');
    }

    /**
     * Alias: $model->transitions() points to the same relationship as stateHistory().
     * The relationship name used for eager loading depends on config.
     */
    public function transitions()
    {
        return $this->stateHistory();
    }

    /**
     * Pending transitions relationship.
     */
    public function pendingTransitions()
    {
        return $this->morphMany(PendingTransition::class, 'model');
    }

    // ──────────────────────────────────────────────
    //  Record helpers
    // ──────────────────────────────────────────────

    public function recordState(string $field, ?string $from, ?string $to, array $customProperties = [], ?Model $responsible = null, array $changedAttributes = []): void
    {
        $transition = Transition::make([
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'custom_properties' => $customProperties,
            'changed_attributes' => $changedAttributes,
        ]);

        if ($responsible !== null) {
            $transition->responsible()->associate($responsible);
        }

        $this->stateHistory()->save($transition);
    }

    public function recordPendingTransition(string $field, ?string $from, ?string $to, $when, array $customProperties = [], ?Model $responsible = null): PendingTransition
    {
        $pendingTransition = PendingTransition::make([
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'transition_at' => $when,
            'custom_properties' => $customProperties,
        ]);

        if ($responsible !== null) {
            $pendingTransition->responsible()->associate($responsible);
        }

        return $this->pendingTransitions()->save($pendingTransition);
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────

    /**
     * Get the cached State proxy for a given field.
     */
    protected function getStateFor(string $field): State
    {
        if (! isset($this->stateInstances[$field]) || $this->stateValueChanged($field)) {
            $sm = new $this->stateMachines[$field]($field, $this);
            $this->stateInstances[$field] = new State($this->{$field}, $sm);
        }

        return $this->stateInstances[$field];
    }

    /**
     * Check if the model's field value has changed from the cached State instance.
     */
    protected function stateValueChanged(string $field): bool
    {
        if (! isset($this->stateInstances[$field])) {
            return true;
        }

        $currentValue = $this->{$field};
        if ($currentValue instanceof \BackedEnum) {
            $currentValue = $currentValue->value;
        }

        return $this->stateInstances[$field]->state() !== $currentValue;
    }

    /**
     * Safely resolve responsible user for initial state recording.
     */
    protected function resolveResponsibleSafely(): ?Model
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        try {
            return auth()->user();
        } catch (\Throwable) {
            return null;
        }
    }
}
