<?php

namespace Iotron\StateMachine\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Iotron\StateMachine\Concerns\HasStateMachines;

class ValidatingModel extends Model
{
    use HasStateMachines;

    protected $table = 'test_models';

    protected $guarded = [];

    public $stateMachines = [
        'status' => ValidatingStateMachine::class,
    ];
}
