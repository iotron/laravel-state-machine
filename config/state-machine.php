<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the state machine package.
    | For backward compatibility with asantibanez/laravel-eloquent-state-machines,
    | set 'transitions' to 'state_histories'.
    |
    */

    'tables' => [
        'transitions' => 'state_histories',
        'pending_transitions' => 'pending_transitions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Record Changed Attributes
    |--------------------------------------------------------------------------
    |
    | When true, the package will capture the model's dirty attributes
    | at the time of transition and store them in the history record.
    |
    */

    'record_changed_attributes' => true,

    /*
    |--------------------------------------------------------------------------
    | Cancel Pending on Transition
    |--------------------------------------------------------------------------
    |
    | When true, any pending transitions for the same field will be
    | automatically cancelled when a direct transition occurs.
    |
    */

    'cancel_pending_on_transition' => true,

];
