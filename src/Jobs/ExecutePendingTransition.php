<?php

namespace Iotron\StateMachine\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Iotron\StateMachine\Exceptions\InvalidStartingStateException;
use Iotron\StateMachine\Models\PendingTransition;

class ExecutePendingTransition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PendingTransition $pendingTransition,
    ) {}

    public function handle(): void
    {
        $field = $this->pendingTransition->field;
        $model = $this->pendingTransition->model;
        $from = $this->pendingTransition->from;
        $to = $this->pendingTransition->to;
        $customProperties = $this->pendingTransition->custom_properties ?? [];
        $responsible = $this->pendingTransition->responsible;

        if ($model->$field()->isNot($from)) {
            $this->fail(new InvalidStartingStateException(
                expectedState: $from,
                actualState: $model->$field()->state(),
            ));

            return;
        }

        $model->$field()->transitionTo($to, $customProperties, $responsible);

        $this->pendingTransition->update(['applied_at' => now()]);
    }
}
