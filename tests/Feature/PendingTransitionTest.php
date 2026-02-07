<?php

use Carbon\Carbon;
use Iotron\StateMachine\Tests\Fixtures\TestModel;

describe('Pending Transitions', function () {

    it('can postpone a transition', function () {
        $model = TestModel::create();

        $pending = $model->status()->postponeTransitionTo('active', Carbon::tomorrow());

        expect($pending)->not->toBeNull();
        expect($pending->from)->toBe('pending');
        expect($pending->to)->toBe('active');
        expect($model->status()->hasPendingTransitions())->toBeTrue();
    });

    it('returns null when postponing to current state', function () {
        $model = TestModel::create();

        $result = $model->status()->postponeTransitionTo('pending', Carbon::tomorrow());

        expect($result)->toBeNull();
    });

    it('cancels pending transitions when transitioning directly', function () {
        $model = TestModel::create();

        $model->status()->postponeTransitionTo('active', Carbon::tomorrow());

        expect($model->status()->hasPendingTransitions())->toBeTrue();

        $model->status()->transitionTo('active');

        expect($model->status()->hasPendingTransitions())->toBeFalse();
    });

    it('can query pending transitions', function () {
        $model = TestModel::create();

        $model->status()->postponeTransitionTo('active', Carbon::tomorrow());
        $model->status()->postponeTransitionTo('cancelled', Carbon::tomorrow()->addDay());

        expect($model->status()->pendingTransitions()->count())->toBe(2);
    });
});
