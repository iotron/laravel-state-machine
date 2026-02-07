<?php

namespace Iotron\StateMachine\StateMachines;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Iotron\StateMachine\Models\PendingTransition;
use Iotron\StateMachine\Models\Transition;

/**
 * Proxy object returned by $model->status().
 * Delegates to the underlying StateMachine while providing a clean API.
 */
class State
{
    public function __construct(
        public mixed $state,
        public StateMachine $stateMachine,
    ) {
        // Normalize the state to string if it's a BackedEnum
        if ($this->state instanceof BackedEnum) {
            $this->state = $this->state->value;
        }
    }

    public function state(): mixed
    {
        return $this->state;
    }

    public function stateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function is(string|BackedEnum $state): bool
    {
        $state = $state instanceof BackedEnum ? $state->value : $state;

        return $this->state === $state;
    }

    public function isNot(string|BackedEnum $state): bool
    {
        return ! $this->is($state);
    }

    public function canBe(string|BackedEnum $state): bool
    {
        return $this->stateMachine->canBe($this->state, $state);
    }

    public function transitionTo(string|BackedEnum $state, array $customProperties = [], ?Model $responsible = null): void
    {
        $this->stateMachine->transitionTo(
            $this->state,
            $state,
            $customProperties,
            $responsible,
        );
    }

    public function postponeTransitionTo(string|BackedEnum $state, Carbon $when, array $customProperties = [], ?Model $responsible = null): ?PendingTransition
    {
        return $this->stateMachine->postponeTransitionTo(
            $this->state,
            $state,
            $when,
            $customProperties,
            $responsible,
        );
    }

    public function was(string|BackedEnum $state): bool
    {
        return $this->stateMachine->was($state);
    }

    public function timesWas(string|BackedEnum $state): int
    {
        return $this->stateMachine->timesWas($state);
    }

    public function whenWas(string|BackedEnum $state): ?Carbon
    {
        return $this->stateMachine->whenWas($state);
    }

    public function snapshotWhen(string|BackedEnum $state): ?Transition
    {
        return $this->stateMachine->snapshotWhen($state);
    }

    public function snapshotsWhen(string|BackedEnum $state): \Illuminate\Support\Collection
    {
        return $this->stateMachine->snapshotsWhen($state);
    }

    public function history()
    {
        return $this->stateMachine->history();
    }

    public function latest(): ?Transition
    {
        return $this->snapshotWhen($this->state);
    }

    public function getCustomProperty(string $key): mixed
    {
        return $this->latest()?->getCustomProperty($key);
    }

    public function responsible(): ?Model
    {
        return $this->latest()?->responsible;
    }

    public function allCustomProperties(): array
    {
        return $this->latest()?->allCustomProperties() ?? [];
    }

    public function pendingTransitions()
    {
        return $this->stateMachine->pendingTransitions();
    }

    public function hasPendingTransitions(): bool
    {
        return $this->stateMachine->hasPendingTransitions();
    }
}
