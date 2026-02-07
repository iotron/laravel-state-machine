<?php

namespace Iotron\StateMachine\Tests\Fixtures;

enum TestEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
