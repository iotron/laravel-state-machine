<?php

use Iotron\StateMachine\Exceptions\TransitionNotAllowedException;
use Iotron\StateMachine\Tests\Fixtures\TestEnum;
use Iotron\StateMachine\Tests\Fixtures\TestModel;
use Iotron\StateMachine\Tests\Fixtures\TestModelWithEnum;

describe('Basic Transitions', function () {

    it('creates a model with default state', function () {
        $model = TestModel::create();

        expect($model->status)->toBe('pending');
    });

    it('can transition to an allowed state', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');

        expect($model->fresh()->status)->toBe('active');
    });

    it('throws on disallowed transition', function () {
        $model = TestModel::create();

        expect(fn () => $model->status()->transitionTo('completed'))
            ->toThrow(TransitionNotAllowedException::class);
    });

    it('checks canBe correctly', function () {
        $model = TestModel::create();

        expect($model->status()->canBe('active'))->toBeTrue();
        expect($model->status()->canBe('completed'))->toBeFalse();
        expect($model->status()->canBe('cancelled'))->toBeTrue();
    });

    it('is() and isNot() work correctly', function () {
        $model = TestModel::create();

        expect($model->status()->is('pending'))->toBeTrue();
        expect($model->status()->isNot('active'))->toBeTrue();
    });

    it('does nothing when transitioning to current state', function () {
        $model = TestModel::create();

        // No exception, no change
        $model->status()->transitionTo('pending');

        expect($model->status)->toBe('pending');
    });

    it('can chain transitions', function () {
        $model = TestModel::create();

        $model->status()->transitionTo('active');
        $model->status()->transitionTo('completed');

        expect($model->fresh()->status)->toBe('completed');
    });
});

describe('Transitions with Enum Cast', function () {

    it('creates model with default state as enum', function () {
        $model = TestModelWithEnum::create();

        expect($model->fresh()->status)->toBeInstanceOf(TestEnum::class);
        expect($model->fresh()->status)->toBe(TestEnum::PENDING);
    });

    it('can transition using enum values', function () {
        $model = TestModelWithEnum::create();

        $model->status()->transitionTo(TestEnum::ACTIVE);

        expect($model->fresh()->status)->toBe(TestEnum::ACTIVE);
    });

    it('canBe works with enum values', function () {
        $model = TestModelWithEnum::create();

        expect($model->status()->canBe(TestEnum::ACTIVE))->toBeTrue();
        expect($model->status()->canBe(TestEnum::COMPLETED))->toBeFalse();
    });

    it('is() works with enum values', function () {
        $model = TestModelWithEnum::create();

        expect($model->status()->is(TestEnum::PENDING))->toBeTrue();
        expect($model->status()->is(TestEnum::ACTIVE))->toBeFalse();
    });
});
