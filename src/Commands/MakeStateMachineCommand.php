<?php

namespace Iotron\StateMachine\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeStateMachineCommand extends GeneratorCommand
{
    protected $name = 'make:state-machine';

    protected $description = 'Create a new state machine class';

    protected $type = 'StateMachine';

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/state-machine.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\StateMachines';
    }
}
