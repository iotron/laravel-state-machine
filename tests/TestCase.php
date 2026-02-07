<?php

namespace Iotron\StateMachine\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Iotron\StateMachine\StateMachineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            StateMachineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        // Run package migrations
        foreach (glob(__DIR__.'/../database/migrations/*.php') as $migration) {
            (include $migration)->up();
        }

        // Create test model table
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
}
