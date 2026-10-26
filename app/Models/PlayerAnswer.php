<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAnswer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'room_id',
        'player_id',
        'room_question_id',
        'selected_answer',
        'is_correct',
        'score_awarded',
        'time_taken_seconds',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'score_awarded' => 'integer',
            'time_taken_seconds' => 'float',
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
     * Player relationship.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Room Question relationship.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(RoomQuestion::class, 'room_question_id');
    }
}
