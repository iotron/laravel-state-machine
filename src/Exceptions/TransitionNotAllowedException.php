<?php

namespace Iotron\StateMachine\Exceptions;

use Exception;

class TransitionNotAllowedException extends Exception
{
    public function __construct(
        protected string $from,
        protected string $to,
        protected string $modelClass,
    ) {
        parent::__construct(
            "Transition from '{$from}' to '{$to}' is not allowed for model '{$modelClass}'",
            422
        );
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getModel(): string
    {
        return $this->modelClass;
    }
}
