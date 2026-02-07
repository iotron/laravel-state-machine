<?php

namespace Iotron\StateMachine;

use Illuminate\Support\ServiceProvider;
use Iotron\StateMachine\Commands\MakeStateMachineCommand;

class StateMachineServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/state-machine.php' => config_path('state-machine.php'),
        ], 'state-machine-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'state-machine-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeStateMachineCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/state-machine.php',
            'state-machine'
        );
    }
}
