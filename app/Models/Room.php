<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'host_token',
        'question_count',
        'status',
        'current_question_index',
        'question_started_at',
        'is_question_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'current_question_index' => 'integer',
            'question_started_at' => 'float',
            'is_question_active' => 'boolean',
        ];
    }

    /**
     * Get the questions for this room.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(RoomQuestion::class)->orderBy('question_order');
    }

    /**
     * Get the players in this room.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the player answers for this room.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    /**
     * Check if a given token belongs to the host of this room.
     */
    public function isHost(?string $token): bool
    {
        return ! empty($token) && hash_equals($this->host_token, $token);
    }
}
