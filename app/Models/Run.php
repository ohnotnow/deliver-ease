<?php

namespace App\Models;

use App\Enums\RunStatus;
use App\Events\RunStarted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Run extends Model
{
    /** @use HasFactory<\Database\Factories\RunFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'created_by_user_id',
        'name',
        'pin',
        'status',
        'started_at',
        'completed_at',
    ];

    // Lifecycle

    protected static function booted(): void
    {
        static::creating(function (Run $run) {
            $run->uuid = $run->uuid ?? (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class)->orderBy('position')->chaperone();
    }

    // Custom Methods

    public function getDriverUrl(): string
    {
        return route('driver.access', ['uuid' => $this->uuid]);
    }

    public static function generatePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function isPending(): bool
    {
        return $this->status === RunStatus::Pending;
    }

    public function isStarted(): bool
    {
        return $this->status === RunStatus::InProgress;
    }

    public function isCompleted(): bool
    {
        return $this->status === RunStatus::Completed;
    }

    public function start(): void
    {
        $this->update([
            'status' => RunStatus::InProgress,
            'started_at' => now(),
        ]);

        RunStarted::dispatch($this);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => RunStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function nextStop(): ?Delivery
    {
        if (! $this->isStarted()) {
            return null;
        }

        return $this->deliveries()->stillToDo()->first();
    }

    public function hasNoStopsLeft(): bool
    {
        return $this->deliveries()->stillToDo()->doesntExist();
    }

    /**
     * The stops not yet delivered, in order. Each one's key is how many undelivered stops are ahead of it.
     *
     * @return Collection<int, Delivery>
     */
    public function stopsStillToDo(): Collection
    {
        return $this->deliveries()->stillToDo()->get();
    }

    public function completedDeliveriesCount(): int
    {
        return $this->deliveries()->completed()->count();
    }
}
