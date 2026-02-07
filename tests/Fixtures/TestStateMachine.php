<?php

namespace Iotron\StateMachine\Tests\Fixtures;

use Iotron\StateMachine\StateMachines\StateMachine;

class TestStateMachine extends StateMachine
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
        return TestEnum::PENDING->value;
    }
}
