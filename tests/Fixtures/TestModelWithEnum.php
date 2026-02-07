<?php

namespace Iotron\StateMachine\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Iotron\StateMachine\Concerns\HasStateMachines;

class TestModelWithEnum extends Model
{
    use HasStateMachines;

    protected $table = 'test_models';

    protected $guarded = [];

    public $stateMachines = [
        'status' => TestStateMachine::class,
    ];

    protected function casts(): array
    {
        return [
            'status' => TestEnum::class,
        ];
    }
}
