<?php

use Illuminate\Support\Facades\DB;
use Iotron\StateMachine\Tests\Fixtures\ValidatingModel;

/**
 * transitionTo() must not open a transaction of its own when the caller has
 * already opened one. A nested DB::transaction() would create a savepoint —
 * correct, but it makes this method a second transaction opener, and a caller
 * wrapping several transitions in one unit of work accumulates savepoints it
 * never asked for.
 */
describe('Transaction boundary', function () {

    /**
     * Records the transaction depth observed at the moment the model is saved,
     * which is inside whatever boundary transitionTo() decided to use.
     */
    $observeDepthDuringSave = function (): object {
        $observed = new stdClass;
        $observed->depth = null;

        ValidatingModel::saving(function () use ($observed) {
            $observed->depth = DB::transactionLevel();
        });

        return $observed;
    };

    it('opens its own transaction when the caller has not', function () use ($observeDepthDuringSave) {
        $observed = $observeDepthDuringSave();
        $model = ValidatingModel::create(['name' => 'Test']);

        expect(DB::transactionLevel())->toBe(0);

        $model->status()->transitionTo('active');

        expect($observed->depth)->toBe(1)
            ->and(DB::transactionLevel())->toBe(0)
            ->and($model->fresh()->status)->toBe('active');
    });

    it('reuses the caller transaction instead of nesting a savepoint', function () use ($observeDepthDuringSave) {
        $observed = $observeDepthDuringSave();
        $model = ValidatingModel::create(['name' => 'Test']);

        DB::transaction(function () use ($model) {
            $model->status()->transitionTo('active');
        });

        // 1, not 2: the outer transaction is the boundary, no savepoint added.
        expect($observed->depth)->toBe(1)
            ->and($model->fresh()->status)->toBe('active');
    });

    it('stays atomic with the caller when the outer transaction rolls back', function () {
        $model = ValidatingModel::create(['name' => 'Test']);

        try {
            DB::transaction(function () use ($model) {
                $model->status()->transitionTo('active');

                throw new RuntimeException('caller failed after the transition');
            });
        } catch (RuntimeException) {
            // Expected — the caller's unit of work failed.
        }

        expect($model->fresh()->status)->toBe('pending');
    });

    it('leaves no open transaction behind when a transition throws', function () {
        $model = ValidatingModel::create(['name' => null]);

        try {
            $model->status()->transitionTo('active');
        } catch (Throwable) {
            // Expected — validation fails for a null name.
        }

        expect(DB::transactionLevel())->toBe(0);
    });
});
