<?php

namespace Iotron\StateMachine\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $field
 * @property string|null $from
 * @property string|null $to
 * @property array $custom_properties
 * @property array $changed_attributes
 * @property int|null $responsible_id
 * @property string|null $responsible_type
 * @property Model|null $responsible
 * @property Carbon $created_at
 */
class Transition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
            'changed_attributes' => 'array',
        ];
    }

    public function getTable(): string
    {
        return config('state-machine.tables.transitions', 'state_histories');
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
    //  Custom property helpers
    // ──────────────────────────────────────────────

    public function getCustomProperty(string $key): mixed
    {
        return data_get($this->custom_properties, $key);
    }

    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    // ──────────────────────────────────────────────
    //  Changed attribute helpers
    // ──────────────────────────────────────────────

    public function changedAttributesNames(): array
    {
        return collect($this->changed_attributes ?? [])->keys()->toArray();
    }

    public function changedAttributeOldValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "{$attribute}.old");
    }

    public function changedAttributeNewValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "{$attribute}.new");
    }

    // ──────────────────────────────────────────────
    //  Query scopes
    // ──────────────────────────────────────────────

    public function scopeForField($query, string $field)
    {
        $query->where('field', $field);
    }

    public function scopeFrom($query, string|array $from)
    {
        if (is_array($from)) {
            $query->whereIn('from', $from);
        } else {
            $query->where('from', $from);
        }
    }

    public function scopeTransitionedFrom($query, string|array $from)
    {
        $query->from($from);
    }

    public function scopeTo($query, string|array $to)
    {
        if (is_array($to)) {
            $query->whereIn('to', $to);
        } else {
            $query->where('to', $to);
        }
    }

    public function scopeTransitionedTo($query, string|array $to)
    {
        $query->to($to);
    }

    public function scopeWithTransition($query, string $from, string $to)
    {
        $query->from($from)->to($to);
    }

    public function scopeWithCustomProperty($query, string $key, $operator, $value = null)
    {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    public function scopeWithResponsible($query, $responsible)
    {
        if ($responsible instanceof Model) {
            return $query
                ->where('responsible_id', $responsible->getKey())
                ->where('responsible_type', get_class($responsible));
        }

        return $query->where('responsible_id', $responsible);
    }
}
