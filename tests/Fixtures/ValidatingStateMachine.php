<?php

namespace Iotron\StateMachine\Tests\Fixtures;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Iotron\StateMachine\StateMachines\StateMachine;

class ValidatingStateMachine extends StateMachine
{
    public function transitions(): array
    {
        return [
            'pending' => ['active', 'cancelled'],
            'active' => ['completed', 'cancelled'],
        ];
    }

    public function defaultState(): ?string
    {
        return 'pending';
    }

    public function validatorForTransition($from, $to, $model): ?Validator
    {
        if ($to === 'active') {
            $validator = ValidatorFacade::make([], []);

            if (! $model->name) {
                $validator->after(fn ($v) => $v->errors()->add('name', 'Name is required to activate.'));
            }

            return $validator;
        }

        return null;
    }
}
