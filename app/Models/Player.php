<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'room_id',
        'player_token',
        'name',
        'score',
        'correct_answers_count',
        'total_time_taken',
        'is_connected',
        'is_kicked',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'correct_answers_count' => 'integer',
            'total_time_taken' => 'float',
            'is_connected' => 'boolean',
            'is_kicked' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Room relationship.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Answers relationship.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }
}
