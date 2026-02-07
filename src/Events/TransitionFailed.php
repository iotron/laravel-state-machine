<?php

namespace Iotron\StateMachine\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class TransitionFailed
{
    use Dispatchable;

    public function __construct(
        public Model $model,
        public string $field,
        public string $from,
        public string $to,
        public \Throwable $exception,
    ) {}
}
