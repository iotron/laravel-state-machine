<?php

use Iotron\StateMachine\Tests\Fixtures\TestEnum;
use Iotron\StateMachine\Tests\Fixtures\TestModelWithEnum;

describe('BackedEnum Support', function () {

    it('creates with default enum state', function () {
        $model = TestModelWithEnum::create();

        expect($model->fresh()->status)->toBe(TestEnum::PENDING);
    });

    it('transitions using enum values', function () {
        $model = TestModelWithEnum::create();

        $model->status()->transitionTo(TestEnum::ACTIVE);
        expect($model->fresh()->status)->toBe(TestEnum::ACTIVE);

        $model->status()->transitionTo(TestEnum::COMPLETED);
        expect($model->fresh()->status)->toBe(TestEnum::COMPLETED);
    });

    it('canBe works with enums', function () {
        $model = TestModelWithEnum::create();

        expect($model->status()->canBe(TestEnum::ACTIVE))->toBeTrue();
        expect($model->status()->canBe(TestEnum::COMPLETED))->toBeFalse();
    });

    it('is() and isNot() work with enums', function () {
        $model = TestModelWithEnum::create();

        expect($model->status()->is(TestEnum::PENDING))->toBeTrue();
        expect($model->status()->is(TestEnum::ACTIVE))->toBeFalse();
        expect($model->status()->isNot(TestEnum::ACTIVE))->toBeTrue();
    });

    it('history queries work with enums', function () {
        $model = TestModelWithEnum::create();

        $model->status()->transitionTo(TestEnum::ACTIVE);

        expect($model->status()->was(TestEnum::PENDING))->toBeTrue();
        expect($model->status()->was(TestEnum::COMPLETED))->toBeFalse();
        expect($model->status()->timesWas(TestEnum::PENDING))->toBe(1);
        expect($model->status()->whenWas(TestEnum::ACTIVE))->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('snapshotWhen works with enums', function () {
        $model = TestModelWithEnum::create();

        $model->status()->transitionTo(TestEnum::ACTIVE);

        $snapshot = $model->status()->snapshotWhen(TestEnum::ACTIVE);
        expect($snapshot)->not->toBeNull();
        expect($snapshot->to)->toBe('active');

        $snapshots = $model->status()->snapshotsWhen(TestEnum::ACTIVE);
        expect($snapshots)->toHaveCount(1);
    });

    it('postponeTransitionTo works with enums', function () {
        $model = TestModelWithEnum::create();

        $pending = $model->status()->postponeTransitionTo(TestEnum::ACTIVE, \Carbon\Carbon::tomorrow());

        expect($pending)->not->toBeNull();
        expect($pending->to)->toBe('active');
    });
});
