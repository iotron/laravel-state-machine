<?php

use Illuminate\Support\Facades\DB;
use Iotron\StateMachine\Tests\Fixtures\TestEnum;
use Iotron\StateMachine\Tests\Fixtures\TestModel;
use Iotron\StateMachine\Tests\Fixtures\TestModelWithEnum;

describe('N+1 Prevention', function () {

    it('uses eager-loaded history for was() without extra queries', function () {
        $model = TestModel::create();
        $model->status()->transitionTo('active');

        // Reload with eager-loaded stateHistory
        $model = TestModel::with('stateHistory')->find($model->id);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $wasPending = $model->status()->was('pending');
        $wasCompleted = $model->status()->was('completed');
        $timesPending = $model->status()->timesWas('pending');

        expect($wasPending)->toBeTrue();
        expect($wasCompleted)->toBeFalse();
        expect($timesPending)->toBe(1);
        expect($queryCount)->toBe(0);
    });

    it('uses eager-loaded history for snapshotWhen() without extra queries', function () {
        $model = TestModel::create();
        $model->status()->transitionTo('active');

        $model = TestModel::with('stateHistory')->find($model->id);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $snapshot = $model->status()->snapshotWhen('active');

        expect($snapshot)->not->toBeNull();
        expect($snapshot->to)->toBe('active');
        expect($queryCount)->toBe(0);
    });

    it('falls back to DB query when history is not eager-loaded', function () {
        $model = TestModel::create();
        $model->status()->transitionTo('active');

        // Reload WITHOUT eager loading
        $model = TestModel::find($model->id);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $wasPending = $model->status()->was('pending');

        expect($wasPending)->toBeTrue();
        expect($queryCount)->toBeGreaterThan(0);
    });

    it('prevents N+1 when loading a collection', function () {
        // Create 5 models with transitions
        $models = collect();
        for ($i = 0; $i < 5; $i++) {
            $m = TestModel::create();
            $m->status()->transitionTo('active');
            $models->push($m);
        }

        // Load all with eager-loaded stateHistory
        $loaded = TestModel::with('stateHistory')
            ->whereIn('id', $models->pluck('id'))
            ->get();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        foreach ($loaded as $m) {
            $m->status()->was('pending');
            $m->status()->was('active');
            $m->status()->timesWas('pending');
            $m->status()->snapshotWhen('active');
        }

        // 5 models x 4 calls = 20 calls, 0 extra queries
        expect($queryCount)->toBe(0);
    });

    it('works with enum models and eager loading', function () {
        $model = TestModelWithEnum::create();
        $model->status()->transitionTo(TestEnum::ACTIVE);

        $model = TestModelWithEnum::with('stateHistory')->find($model->id);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        expect($model->status()->was(TestEnum::PENDING))->toBeTrue();
        expect($model->status()->was(TestEnum::COMPLETED))->toBeFalse();
        expect($queryCount)->toBe(0);
    });
});
