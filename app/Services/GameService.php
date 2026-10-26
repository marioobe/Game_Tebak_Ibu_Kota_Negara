<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\Room;
use App\Models\RoomQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GameService
{
    /**
     * Duration in seconds for each question.
     */
    public const QUESTION_DURATION = 15.0;

    /**
     * Create a new room with randomly selected unique questions.
     *
     * @param int $questionCount 10, 20, or 30
     */
    public function createRoom(int $questionCount = 10): Room
    {
        $validCounts = [10, 20, 30];
        if (! in_array($questionCount, $validCounts, true)) {
            $questionCount = 10;
        }

        $totalCountries = Country::count();
        if ($totalCountries < $questionCount) {
            throw new \RuntimeException("Jumlah soal unik yang tersedia ($totalCountries) tidak mencukupi untuk $questionCount soal.");
        }

        // Generate a 5-letter unique room code (avoid ambiguous characters like 0, O, 1, I)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (Room::where('code', $code)->exists());

        $hostToken = Str::random(40);

        $room = Room::create([
            'code' => $code,
            'host_token' => $hostToken,
            'question_count' => $questionCount,
            'status' => 'lobby',
            'current_question_index' => 0,
            'question_started_at' => null,
            'is_question_active' => false,
        ]);

        // Select random unique countries
        $selectedCountries = Country::inRandomOrder()->limit($questionCount)->get();

        $allCapitals = Country::pluck('capital_name')->unique()->values()->all();

        $order = 1;
        foreach ($selectedCountries as $country) {
            $correctCapital = $country->capital_name;

            // Pick 3 distinct distractor capitals
            $distractorPool = array_values(array_filter($allCapitals, static fn ($cap) => $cap !== $correctCapital));
            shuffle($distractorPool);
            $distractors = array_slice($distractorPool, 0, 3);

            $options = array_merge([$correctCapital], $distractors);
            shuffle($options);

            RoomQuestion::create([
                'room_id' => $room->id,
                'country_id' => $country->id,
                'question_order' => $order++,
                'country_name' => $country->country_name,
                'correct_capital' => $correctCapital,
                'options' => $options,
            ]);
        }

        return $room;
    }

    /**
     * Join room or reconnect an existing player.
     *
     * @return array{player: Player, reconnected: bool}
     */
    public function joinRoom(string $code, string $playerName, ?string $playerToken = null): array
    {
        $playerName = trim($playerName);
        if ($playerName === '') {
            throw new \InvalidArgumentException('Nama pemain tidak boleh kosong.');
        }

        $room = Room::where('code', strtoupper($code))->first();
        if (! $room) {
            throw new \InvalidArgumentException('Kode ruang tidak ditemukan.');
        }

        if ($room->status === 'ended') {
            throw new \InvalidArgumentException('Pertandingan di ruang ini sudah berakhir.');
        }

        // Check if player with this name already exists in room
        $existingPlayer = Player::where('room_id', $room->id)
            ->where('name', $playerName)
            ->first();

        if ($room->status === 'lobby') {
            if ($existingPlayer) {
                if ($playerToken && hash_equals($existingPlayer->player_token, $playerToken)) {
                    $existingPlayer->update([
                        'is_connected' => true,
                        'is_kicked' => false,
                        'last_seen_at' => now(),
                    ]);

                    return ['player' => $existingPlayer, 'reconnected' => true];
                }

                throw new \InvalidArgumentException("Nama '$playerName' sudah digunakan di ruang ini. Silakan gunakan nama lain.");
            }

            $token = Str::random(40);
            $player = Player::create([
                'room_id' => $room->id,
                'player_token' => $token,
                'name' => $playerName,
                'score' => 0,
                'correct_answers_count' => 0,
                'total_time_taken' => 0,
                'is_connected' => true,
                'is_kicked' => false,
                'last_seen_at' => now(),
            ]);

            return ['player' => $player, 'reconnected' => false];
        }

        // When game is 'playing'
        if (! $existingPlayer) {
            throw new \InvalidArgumentException('Pertandingan sudah berjalan. Pemain baru tidak dapat bergabung.');
        }

        if ($existingPlayer->is_kicked) {
            throw new \InvalidArgumentException('Anda telah dikeluarkan dari ruang ini.');
        }

        // Reconnection successful
        $existingPlayer->update([
            'is_connected' => true,
            'last_seen_at' => now(),
        ]);

        return ['player' => $existingPlayer, 'reconnected' => true];
    }

    /**
     * Start the match.
     */
    public function startMatch(Room $room, string $hostToken): void
    {
        if (! $room->isHost($hostToken)) {
            throw new \InvalidArgumentException('Akses ditolak: Anda bukan host ruang ini.');
        }

        if ($room->status !== 'lobby') {
            throw new \InvalidArgumentException('Pertandingan sudah dimulai atau sudah selesai.');
        }

        $playerCount = $room->players()->where('is_kicked', false)->count();
        if ($playerCount < 1) {
            throw new \InvalidArgumentException('Minimal harus ada 1 pemain di lobi untuk memulai pertandingan.');
        }

        $now = microtime(true);
        $room->update([
            'status' => 'playing',
            'current_question_index' => 1,
            'question_started_at' => $now,
            'is_question_active' => true,
        ]);
    }

    /**
     * Submit an answer for the current question.
     *
     * @return array{score: int, is_correct: bool, correct_capital: string}
     */
    public function submitAnswer(Room $room, Player $player, int $questionOrder, string $selectedCapital): array
    {
        if ($room->status !== 'playing') {
            throw new \InvalidArgumentException('Pertandingan sedang tidak aktif.');
        }

        if ($player->is_kicked) {
            throw new \InvalidArgumentException('Anda telah dikeluarkan dari permainan.');
        }

        if ($room->current_question_index !== $questionOrder) {
            throw new \InvalidArgumentException('Soal ini sudah tidak aktif.');
        }

        $currentQuestion = $room->questions()
            ->where('question_order', $questionOrder)
            ->firstOrFail();

        // Check if player has already submitted an answer for this question
        $alreadyAnswered = PlayerAnswer::where('player_id', $player->id)
            ->where('room_question_id', $currentQuestion->id)
            ->exists();

        if ($alreadyAnswered) {
            throw new \InvalidArgumentException('Jawaban Anda sudah terkunci.');
        }

        // Server authoritative timing
        $now = microtime(true);
        $startedAt = (float) $room->question_started_at;
        $elapsed = max(0.0, $now - $startedAt);

        $isTimeout = $elapsed > self::QUESTION_DURATION;
        $isCorrect = (! $isTimeout) && ($selectedCapital === $currentQuestion->correct_capital);

        // PRD Formula: S = ceil(100 * (15 - t) / 15) if correct, 0 if wrong/timeout
        $score = 0;
        if ($isCorrect) {
            $t = min(self::QUESTION_DURATION, $elapsed);
            $score = (int) ceil(100.0 * ((self::QUESTION_DURATION - $t) / self::QUESTION_DURATION));
            $score = max(0, min(100, $score));
        }

        // Store the locked answer
        PlayerAnswer::create([
            'room_id' => $room->id,
            'player_id' => $player->id,
            'room_question_id' => $currentQuestion->id,
            'selected_answer' => $selectedCapital,
            'is_correct' => $isCorrect,
            'score_awarded' => $score,
            'time_taken_seconds' => round($elapsed, 4),
        ]);

        // Update player stats
        $player->score += $score;
        if ($isCorrect) {
            $player->correct_answers_count += 1;
            $player->total_time_taken += round($elapsed, 4);
        }
        $player->last_seen_at = now();
        $player->save();

        return [
            'score' => $score,
            'is_correct' => $isCorrect,
            'correct_capital' => $currentQuestion->correct_capital,
        ];
    }

    /**
     * Advance to the next question or complete the match.
     */
    public function advanceOrFinishQuestion(Room $room, ?string $hostToken = null): void
    {
        if ($hostToken !== null && ! $room->isHost($hostToken)) {
            throw new \InvalidArgumentException('Hanya host yang dapat memajukan soal secara manual.');
        }

        if ($room->status !== 'playing') {
            return;
        }

        $nextIndex = $room->current_question_index + 1;
        if ($nextIndex > $room->question_count) {
            // All questions completed
            $room->update([
                'status' => 'ended',
                'is_question_active' => false,
            ]);
        } else {
            $room->update([
                'current_question_index' => $nextIndex,
                'question_started_at' => microtime(true),
                'is_question_active' => true,
            ]);
        }
    }

    /**
     * End match early (Host control).
     */
    public function endMatchEarly(Room $room, string $hostToken): void
    {
        if (! $room->isHost($hostToken)) {
            throw new \InvalidArgumentException('Hanya host yang dapat mengakhiri pertandingan.');
        }

        $room->update([
            'status' => 'ended',
            'is_question_active' => false,
        ]);
    }

    /**
     * Reset room back to lobby for another game.
     */
    public function resetToLobby(Room $room, string $hostToken): void
    {
        if (! $room->isHost($hostToken)) {
            throw new \InvalidArgumentException('Hanya host yang dapat mereset ruang ke lobi.');
        }

        // Clean up answers and reset player scores
        PlayerAnswer::where('room_id', $room->id)->delete();
        $room->players()->update([
            'score' => 0,
            'correct_answers_count' => 0,
            'total_time_taken' => 0,
        ]);

        // Regenerate new questions
        $room->questions()->delete();
        $selectedCountries = Country::inRandomOrder()->limit($room->question_count)->get();
        $allCapitals = Country::pluck('capital_name')->unique()->values()->all();

        $order = 1;
        foreach ($selectedCountries as $country) {
            $correctCapital = $country->capital_name;
            $distractorPool = array_values(array_filter($allCapitals, static fn ($cap) => $cap !== $correctCapital));
            shuffle($distractorPool);
            $distractors = array_slice($distractorPool, 0, 3);
            $options = array_merge([$correctCapital], $distractors);
            shuffle($options);

            RoomQuestion::create([
                'room_id' => $room->id,
                'country_id' => $country->id,
                'question_order' => $order++,
                'country_name' => $country->country_name,
                'correct_capital' => $correctCapital,
                'options' => $options,
            ]);
        }

        $room->update([
            'status' => 'lobby',
            'current_question_index' => 0,
            'question_started_at' => null,
            'is_question_active' => false,
        ]);
    }

    /**
     * Kick a player from the room.
     */
    public function kickPlayer(Room $room, int $playerId, string $hostToken): void
    {
        if (! $room->isHost($hostToken)) {
            throw new \InvalidArgumentException('Hanya host yang dapat mengeluarkan pemain.');
        }

        $player = $room->players()->where('id', $playerId)->firstOrFail();
        $player->update([
            'is_kicked' => true,
            'is_connected' => false,
        ]);
    }

    /**
     * Get sorted and ranked leaderboard with tie-breaking according to PRD.
     *
     * Tie-breaker order:
     * 1. score DESC
     * 2. correct_answers_count DESC
     * 3. total_time_taken ASC
     * 4. id ASC
     *
     * Players with equal score, equal correct answers, and equal time share the same rank.
     *
     * @return Collection<int, array{rank: int, id: int, name: string, score: int, correct_answers_count: int, total_time_taken: float}>
     */
    public function getLeaderboard(Room $room): Collection
    {
        $players = $room->players()
            ->where('is_kicked', false)
            ->get()
            ->sort(function (Player $a, Player $b) {
                if ($a->score !== $b->score) {
                    return $b->score <=> $a->score;
                }
                if ($a->correct_answers_count !== $b->correct_answers_count) {
                    return $b->correct_answers_count <=> $a->correct_answers_count;
                }
                if ($a->total_time_taken != $b->total_time_taken) {
                    return $a->total_time_taken <=> $b->total_time_taken;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $ranked = collect();
        $currentRank = 1;

        for ($i = 0; $i < $players->count(); $i++) {
            $p = $players[$i];

            if ($i > 0) {
                $prev = $players[$i - 1];
                $isTie = ($p->score === $prev->score)
                    && ($p->correct_answers_count === $prev->correct_answers_count)
                    && (abs($p->total_time_taken - $prev->total_time_taken) < 0.001);

                if (! $isTie) {
                    $currentRank = $i + 1;
                }
            }

            $ranked->push([
                'rank' => $currentRank,
                'id' => $p->id,
                'name' => $p->name,
                'score' => $p->score,
                'correct_answers_count' => $p->correct_answers_count,
                'total_time_taken' => (float) $p->total_time_taken,
                'is_connected' => (bool) $p->is_connected,
            ]);
        }

        return $ranked;
    }

    /**
     * Get the full synchronized room state for client consumption.
     *
     * @return array<string, mixed>
     */
    public function getRoomState(Room $room, ?Player $player = null, bool $isHost = false): array
    {
        $now = microtime(true);
        $startedAt = (float) $room->question_started_at;
        $elapsed = $startedAt > 0 ? max(0.0, $now - $startedAt) : 0.0;
        $remaining = max(0.0, self::QUESTION_DURATION - $elapsed);

        // Auto-check if question time has expired
        $isTimeUp = ($room->status === 'playing') && ($elapsed >= self::QUESTION_DURATION);

        $currentQuestionData = null;
        if ($room->status === 'playing' && $room->current_question_index > 0) {
            $cq = $room->questions()
                ->where('question_order', $room->current_question_index)
                ->first();

            if ($cq) {
                $currentQuestionData = [
                    'order' => $cq->question_order,
                    'total' => $room->question_count,
                    'country_name' => $cq->country_name,
                    'options' => $cq->options,
                    'duration' => self::QUESTION_DURATION,
                    'time_remaining' => round($remaining, 1),
                    'is_time_up' => $isTimeUp,
                ];

                // Only show correct answer if time is up, or match ended, or for host
                if ($isTimeUp || $room->status === 'ended' || $isHost) {
                    $currentQuestionData['correct_capital'] = $cq->correct_capital;
                }
            }
        }

        // Player answer for current question
        $playerAnswerData = null;
        if ($player && $room->status === 'playing' && $currentQuestionData) {
            $cq = $room->questions()
                ->where('question_order', $room->current_question_index)
                ->first();

            if ($cq) {
                $answer = PlayerAnswer::where('player_id', $player->id)
                    ->where('room_question_id', $cq->id)
                    ->first();

                if ($answer) {
                    $playerAnswerData = [
                        'has_answered' => true,
                        'selected_answer' => $answer->selected_answer,
                        'is_correct' => $isTimeUp ? $answer->is_correct : null,
                        'score_awarded' => $isTimeUp ? $answer->score_awarded : null,
                    ];
                } else {
                    $playerAnswerData = [
                        'has_answered' => false,
                        'selected_answer' => null,
                        'is_correct' => null,
                        'score_awarded' => null,
                    ];
                }
            }
        }

        $leaderboard = $this->getLeaderboard($room);

        // Lobby players list
        $lobbyPlayers = $room->players()
            ->where('is_kicked', false)
            ->get(['id', 'name', 'is_connected', 'score'])
            ->toArray();

        return [
            'room_code' => $room->code,
            'status' => $room->status, // 'lobby', 'playing', 'ended'
            'question_count' => $room->question_count,
            'current_question_index' => $room->current_question_index,
            'question' => $currentQuestionData,
            'player_answer' => $playerAnswerData,
            'leaderboard' => $leaderboard,
            'lobby_players' => $lobbyPlayers,
            'server_time' => $now,
            'is_host' => $isHost,
            'is_kicked' => $player ? (bool) $player->is_kicked : false,
        ];
    }
}
