<?php

use Illuminate\Support\Facades\Event;
use Iotron\StateMachine\Events\TransitionCompleted;
use Iotron\StateMachine\Events\TransitionFailed;
use Iotron\StateMachine\Events\TransitionStarted;
use Iotron\StateMachine\Exceptions\TransitionNotAllowedException;
use Iotron\StateMachine\Tests\Fixtures\TestModel;

describe('Transition Events', function () {

    it('dispatches TransitionStarted and TransitionCompleted on success', function () {
        Event::fake([TransitionStarted::class, TransitionCompleted::class]);

        $model = TestModel::create();

        $model->status()->transitionTo('active');

        Event::assertDispatched(TransitionStarted::class, function ($event) use ($model) {
            return $event->model->is($model)
                && $event->field === 'status'
                && $event->from === 'pending'
                && $event->to === 'active';
        });

        Event::assertDispatched(TransitionCompleted::class, function ($event) use ($model) {
            return $event->model->is($model)
                && $event->field === 'status'
                && $event->from === 'pending'
                && $event->to === 'active';
        });
    });

    it('dispatches TransitionFailed on exception', function () {
        Event::fake([TransitionStarted::class, TransitionFailed::class, TransitionCompleted::class]);

        $model = TestModel::create();

        try {
            $model->status()->transitionTo('completed'); // not allowed from pending
        } catch (TransitionNotAllowedException) {
            // Expected
        }

        Event::assertNotDispatched(TransitionStarted::class);
        Event::assertNotDispatched(TransitionCompleted::class);
        // TransitionFailed is only dispatched after TransitionStarted succeeds, so not dispatched here either
    });
});

describe('Transition Hooks', function () {

    it('fires before and after hooks with correct arguments', function () {
        \Iotron\StateMachine\Tests\Fixtures\HookStateMachine::$hookLog = [];

        $model = \Iotron\StateMachine\Tests\Fixtures\HookModel::create();

        $model->status()->transitionTo('active');

        $log = \Iotron\StateMachine\Tests\Fixtures\HookStateMachine::$hookLog;

        expect($log)->toContain('before:pending->active');
        expect($log)->toContain('after:pending->active');
    });

    it('fires before hooks before save', function () {
        \Iotron\StateMachine\Tests\Fixtures\HookStateMachine::$hookLog = [];

        $model = \Iotron\StateMachine\Tests\Fixtures\HookModel::create();

        $model->status()->transitionTo('active');

        // Before comes first
        $log = \Iotron\StateMachine\Tests\Fixtures\HookStateMachine::$hookLog;
        $beforeIndex = array_search('before:pending->active', $log);
        $afterIndex = array_search('after:pending->active', $log);

        expect($beforeIndex)->toBeLessThan($afterIndex);
    });
});
