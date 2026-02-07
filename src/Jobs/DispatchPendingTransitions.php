<?php

namespace Iotron\StateMachine\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Iotron\StateMachine\Models\PendingTransition;

class DispatchPendingTransitions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        PendingTransition::with(['model'])
            ->notApplied()
            ->onScheduleOrOverdue()
            ->chunkById(100, function ($pendingTransitions) {
                $pendingTransitions->each(function (PendingTransition $pendingTransition) {
                    ExecutePendingTransition::dispatch($pendingTransition);
                });
            });
    }
}
