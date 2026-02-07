<?php

namespace Iotron\StateMachine\StateMachines;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Iotron\StateMachine\Events\TransitionCompleted;
use Iotron\StateMachine\Events\TransitionFailed;
use Iotron\StateMachine\Events\TransitionStarted;
use Iotron\StateMachine\Exceptions\TransitionNotAllowedException;
use Iotron\StateMachine\Models\PendingTransition;
use Iotron\StateMachine\Models\Transition;

abstract class StateMachine
{
    public string $field;

    public Model $model;

    public function __construct(string $field, Model &$model)
    {
        $this->field = $field;
        $this->model = $model;
    }

    // ──────────────────────────────────────────────
    //  Abstract contract
    // ──────────────────────────────────────────────

    abstract public function transitions(): array;

    abstract public function defaultState(): string|BackedEnum|null;

    // ──────────────────────────────────────────────
    //  Overridable defaults
    // ──────────────────────────────────────────────

    public function recordHistory(): bool
    {
        return true;
    }

    public function validatorForTransition($from, $to, $model): ?Validator
    {
        return null;
    }

    public function beforeTransitionHooks(): array
    {
        return [];
    }

    public function afterTransitionHooks(): array
    {
        return [];
    }

    // ──────────────────────────────────────────────
    //  State queries
    // ──────────────────────────────────────────────

    public function currentState()
    {
        $field = $this->field;

        return $this->normalize($this->model->$field);
    }

    public function canBe($from, $to): bool
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        $availableTransitions = $this->transitions()[$from] ?? [];

        return collect($availableTransitions)->contains($to);
    }

    // ──────────────────────────────────────────────
    //  History queries (with N+1 prevention)
    // ──────────────────────────────────────────────

    public function history()
    {
        $relationName = $this->getTransitionsRelationName();

        return $this->model->$relationName()->forField($this->field);
    }

    public function was($state): bool
    {
        $state = $this->normalize($state);

        if ($history = $this->getLoadedHistory()) {
            return $history->contains('to', $state);
        }

        return $this->history()->to($state)->exists();
    }

    public function timesWas($state): int
    {
        $state = $this->normalize($state);

        if ($history = $this->getLoadedHistory()) {
            return $history->where('to', $state)->count();
        }

        return $this->history()->to($state)->count();
    }

    public function whenWas($state): ?Carbon
    {
        $state = $this->normalize($state);

        if ($history = $this->getLoadedHistory()) {
            $record = $history->where('to', $state)->sortByDesc('id')->first();

            return $record?->created_at;
        }

        $snapshot = $this->snapshotWhen($state);

        return $snapshot?->created_at;
    }

    public function snapshotWhen($state): ?Transition
    {
        $state = $this->normalize($state);

        if ($history = $this->getLoadedHistory()) {
            return $history->where('to', $state)->sortByDesc('id')->first();
        }

        return $this->history()->to($state)->latest('id')->first();
    }

    public function snapshotsWhen($state): Collection
    {
        $state = $this->normalize($state);

        if ($history = $this->getLoadedHistory()) {
            return $history->where('to', $state)->values();
        }

        return $this->history()->to($state)->get();
    }

    // ──────────────────────────────────────────────
    //  Transition execution
    // ──────────────────────────────────────────────

    /**
     * @throws TransitionNotAllowedException
     * @throws ValidationException
     */
    public function transitionTo($from, $to, $customProperties = [], $responsible = null): void
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if ($to === $this->currentState()) {
            return;
        }

        // Validation runs OUTSIDE transaction (read-only)
        if (! $this->canBe($from, $to) && ! $this->canBe($from, '*') && ! $this->canBe('*', $to) && ! $this->canBe('*', '*')) {
            throw new TransitionNotAllowedException($from, $to, get_class($this->model));
        }

        $validator = $this->validatorForTransition($from, $to, $this->model);
        if ($validator !== null && $validator->fails()) {
            throw new ValidationException($validator);
        }

        event(new TransitionStarted($this->model, $this->field, $from, $to));

        try {
            // Before hooks run before the save
            $beforeTransitionHooks = $this->beforeTransitionHooks()[$from] ?? [];
            collect($beforeTransitionHooks)->each(fn ($callable) => $callable($from, $to, $this->model));

            DB::transaction(function () use ($from, $to, $customProperties, $responsible) {
                $field = $this->field;
                $this->model->$field = $to;

                $changedAttributes = $this->model->getChangedAttributes();

                $this->model->save();

                if ($this->recordHistory()) {
                    $responsible = $this->resolveResponsible($responsible);
                    $this->model->recordState($field, $from, $to, $customProperties, $responsible, $changedAttributes);
                }

                if (config('state-machine.cancel_pending_on_transition', true)) {
                    $this->cancelAllPendingTransitions();
                }
            });

            // After hooks run OUTSIDE transaction
            $afterTransitionHooks = $this->afterTransitionHooks()[$to] ?? [];
            collect($afterTransitionHooks)->each(fn ($callable) => $callable($from, $to, $this->model));

            event(new TransitionCompleted($this->model, $this->field, $from, $to));
        } catch (\Throwable $e) {
            event(new TransitionFailed($this->model, $this->field, $from, $to, $e));
            throw $e;
        }
    }

    /**
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo($from, $to, Carbon $when, $customProperties = [], $responsible = null): ?PendingTransition
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if ($to === $this->currentState()) {
            return null;
        }

        if (! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException($from, $to, get_class($this->model));
        }

        $responsible = $this->resolveResponsible($responsible);

        return $this->model->recordPendingTransition(
            $this->field,
            $from,
            $to,
            $when,
            $customProperties,
            $responsible
        );
    }

    // ──────────────────────────────────────────────
    //  Pending transitions
    // ──────────────────────────────────────────────

    public function pendingTransitions()
    {
        return $this->model->pendingTransitions()->forField($this->field);
    }

    public function hasPendingTransitions(): bool
    {
        return $this->pendingTransitions()->notApplied()->exists();
    }

    public function cancelAllPendingTransitions(): void
    {
        $this->pendingTransitions()->delete();
    }

    // ──────────────────────────────────────────────
    //  Protected helpers
    // ──────────────────────────────────────────────

    /**
     * Normalize BackedEnum instances to their string/int value.
     */
    protected function normalize(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * Get loaded history from eager-loaded relationship for N+1 prevention.
     * Returns null if the relationship is not loaded (caller should fall back to query).
     */
    protected function getLoadedHistory(): ?Collection
    {
        $relationName = $this->getTransitionsRelationName();

        if (! $this->model->relationLoaded($relationName)) {
            return null;
        }

        return $this->model->$relationName->where('field', $this->field);
    }

    /**
     * Safely resolve the responsible user/model.
     */
    protected function resolveResponsible(mixed $explicit = null): ?Model
    {
        if ($explicit !== null) {
            return $explicit;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        try {
            return auth()->user();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the relationship name used for transition history on the model.
     * Supports both 'transitions' and 'stateHistory' for backward compatibility.
     */
    protected function getTransitionsRelationName(): string
    {
        $tableName = config('state-machine.tables.transitions', 'state_histories');

        return $tableName === 'state_histories' ? 'stateHistory' : 'transitions';
    }
}
