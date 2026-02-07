<?php

namespace Iotron\StateMachine\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $field
 * @property string|null $from
 * @property string|null $to
 * @property Carbon $transition_at
 * @property Carbon|null $applied_at
 * @property array $custom_properties
 * @property int $model_id
 * @property string $model_type
 * @property Model $model
 * @property int|null $responsible_id
 * @property string|null $responsible_type
 * @property Model|null $responsible
 */
class PendingTransition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
            'transition_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('state-machine.tables.pending_transitions', 'pending_transitions');
    }

    // ──────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    // ──────────────────────────────────────────────
    //  Query scopes
    // ──────────────────────────────────────────────

    public function scopeNotApplied($query)
    {
        $query->whereNull('applied_at');
    }

    public function scopeOnScheduleOrOverdue($query)
    {
        $query->where('transition_at', '<=', now());
    }

    public function scopeForField($query, string $field)
    {
        $query->where('field', $field);
    }
}
