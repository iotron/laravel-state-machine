<?php

use Iotron\StateMachine\Tests\Fixtures\TestEnum;
use Iotron\StateMachine\Tests\Fixtures\TestModel;
use Iotron\StateMachine\Tests\Fixtures\TestModelWithEnum;

describe('History Tracking', function () {

    it('records initial state on creation', function () {
        $model = TestModel::create();

        $history = $model->stateHistory()->get();

        expect($history)->toHaveCount(1);
        expect($history->first()->from)->toBeNull();
        expect($history->first()->to)->toBe('pending');
    });

    it('records transition in history', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        $transition = $model->status()->history()
            ->where('from', 'pending')
            ->where('to', 'active')
            ->first();

        expect($transition)->not->toBeNull();
        expect($transition->from)->toBe('pending');
        expect($transition->to)->toBe('active');
    });

    it('was() returns true for previous states', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        expect($model->status()->was('pending'))->toBeTrue();
        expect($model->status()->was('completed'))->toBeFalse();
    });

    it('timesWas() counts correctly', function () {
        $model = TestModel::create();

        expect($model->status()->timesWas('pending'))->toBe(1); // initial state

        $model->status()->transitionTo('active');

        expect($model->status()->timesWas('pending'))->toBe(1);
        expect($model->status()->timesWas('active'))->toBe(1);
    });

    it('whenWas() returns carbon timestamp', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        $when = $model->status()->whenWas('active');

        expect($when)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('snapshotWhen() returns transition record', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        $snapshot = $model->status()->snapshotWhen('active');

        expect($snapshot)->not->toBeNull();
        expect($snapshot->to)->toBe('active');
    });

    it('snapshotsWhen() returns collection', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        $snapshots = $model->status()->snapshotsWhen('active');

        expect($snapshots)->toHaveCount(1);
    });

    it('latest() returns the most recent snapshot for current state', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        $latest = $model->status()->latest();

        expect($latest)->not->toBeNull();
        expect($latest->to)->toBe('active');
    });

    it('stores custom properties', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active', ['reason' => 'approved']);

        $snapshot = $model->status()->snapshotWhen('active');

        expect($snapshot->getCustomProperty('reason'))->toBe('approved');
        expect($snapshot->allCustomProperties())->toBe(['reason' => 'approved']);
    });

    it('records changed attributes', function () {
        $model = TestModel::create(['name' => 'old']);

        $model->name = 'new';
        $model->status()->transitionTo('active');

        $snapshot = $model->status()->snapshotWhen('active');

        expect($snapshot->changedAttributesNames())->toContain('name');
        expect($snapshot->changedAttributeOldValue('name'))->toBe('old');
        expect($snapshot->changedAttributeNewValue('name'))->toBe('new');
    });

    it('tracks was() with enum values', function () {
        $model = TestModelWithEnum::create();

        $model->status()->transitionTo(TestEnum::ACTIVE);

        expect($model->status()->was(TestEnum::PENDING))->toBeTrue();
        expect($model->status()->was(TestEnum::COMPLETED))->toBeFalse();
    });
});
