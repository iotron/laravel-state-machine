<?php

namespace Iotron\StateMachine\Tests\Fixtures;

use Iotron\StateMachine\StateMachines\StateMachine;

class HookStateMachine extends StateMachine
{
    public static array $hookLog = [];

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

    public function beforeTransitionHooks(): array
    {
        return [
            'pending' => [
                function ($from, $to, $model) {
                    static::$hookLog[] = "before:{$from}->{$to}";
                },
            ],
        ];
    }

    public function afterTransitionHooks(): array
    {
        return [
            'active' => [
                function ($from, $to, $model) {
                    static::$hookLog[] = "after:{$from}->{$to}";
                },
            ],
        ];
    }
}
