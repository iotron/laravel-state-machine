<?php

use Illuminate\Validation\ValidationException;
use Iotron\StateMachine\Tests\Fixtures\ValidatingModel;

describe('Transition Validation', function () {

    it('blocks transition when validation fails', function () {
        $model = ValidatingModel::create(['name' => null]);

        expect(fn () => $model->status()->transitionTo('active'))
            ->toThrow(ValidationException::class);

        expect($model->fresh()->status)->toBe('pending');
    });

    it('allows transition when validation passes', function () {
        $model = ValidatingModel::create(['name' => 'Test']);

        $model->status()->transitionTo('active');

        expect($model->fresh()->status)->toBe('active');
    });

    it('skips validation for states without validator', function () {
        $model = ValidatingModel::create(['name' => null]);

        // Transitioning to 'cancelled' has no validator
        $model->status()->transitionTo('cancelled');

        expect($model->fresh()->status)->toBe('cancelled');
    });

    it('does not leave partial state on validation failure', function () {
        $model = ValidatingModel::create(['name' => null]);

        try {
            $model->status()->transitionTo('active');
        } catch (ValidationException) {
            // Expected
        }

        // Model should be unchanged
        expect($model->fresh()->status)->toBe('pending');

        // No active transition recorded
        expect($model->status()->was('active'))->toBeFalse();
    });
});
