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
        'address',
        'phone',
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

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DeliveryStatus::Completed);
    }

    public function scopeStillToDo(Builder $query): Builder
    {
        return $query->where('status', '!=', DeliveryStatus::Completed);
    }

    // Accessors

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    public function getPhoneLinkAttribute(): string
    {
        return 'tel:'.str_replace(' ', '', $this->phone);
    }

    // Custom Methods

    public function isCompleted(): bool
    {
        return $this->status === DeliveryStatus::Completed;
    }

    public function canBeCompleted(): bool
    {
        return $this->run->isStarted() && ! $this->isCompleted();
    }

    public function isNextStop(): bool
    {
        return $this->stopsAhead() === 0;
    }

    public function stopsAhead(): int
    {
        return $this->run->deliveries()
            ->stillToDo()
            ->where('position', '<', $this->position)
            ->count();
    }

    /**
     * The customer's place on the list as an ordinal, e.g. "2nd", "11th", "21st".
     */
    public function placeOnList(): string
    {
        $suffix = match (true) {
            in_array($this->position % 100, [11, 12, 13]) => 'th',
            $this->position % 10 === 1 => 'st',
            $this->position % 10 === 2 => 'nd',
            $this->position % 10 === 3 => 'rd',
            default => 'th',
        };

        return $this->position.$suffix;
    }

    public function markAsNotified(): void
    {
        $this->update(['status' => DeliveryStatus::Notified]);
    }

    public function recordToldTheyAreNext(): void
    {
        $this->update(['notified_at' => now()]);
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
