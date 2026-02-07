<?php

namespace Iotron\StateMachine\Exceptions;

use Exception;

class InvalidStartingStateException extends Exception
{
    public function __construct(
        public readonly string $expectedState,
        public readonly string $actualState,
    ) {
        parent::__construct("Expected: {$expectedState}. Actual: {$actualState}");
    }
}
