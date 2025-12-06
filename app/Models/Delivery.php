<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Events\DeliveryCompleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'run_id',
        'email',
        'name',
        'position',
        'status',
        'notified_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'notified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DeliveryStatus::Pending);
    }

    public function scopeNotified(Builder $query): Builder
    {
        return $query->where('status', DeliveryStatus::Notified);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DeliveryStatus::Completed);
    }

    // Accessors

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    // Custom Methods

    public function isPending(): bool
    {
        return $this->status === DeliveryStatus::Pending;
    }

    public function isNotified(): bool
    {
        return $this->status === DeliveryStatus::Notified;
    }

    public function isCompleted(): bool
    {
        return $this->status === DeliveryStatus::Completed;
    }

    public function markAsNotified(): void
    {
        $this->update([
            'status' => DeliveryStatus::Notified,
            'notified_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => DeliveryStatus::Completed,
            'completed_at' => now(),
        ]);

        DeliveryCompleted::dispatch($this);
    }
}
