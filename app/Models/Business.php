<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Relationships

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }
}
