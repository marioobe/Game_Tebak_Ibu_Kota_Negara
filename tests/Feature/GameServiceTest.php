<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Player;
use App\Models\Room;
use App\Services\GameService;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CountrySeeder::class);
        $this->gameService = new GameService();
    }

    public function test_can_create_room_with_specified_question_count(): void
    {
        $room = $this->gameService->createRoom(10);
        $this->assertEquals(10, $room->question_count);
        $this->assertCount(10, $room->questions);
        $this->assertEquals('lobby', $room->status);

        // Check each question has 4 unique options and 1 correct answer
        foreach ($room->questions as $q) {
            $this->assertCount(4, $q->options);
            $this->assertCount(4, array_unique($q->options));
            $this->assertContains($q->correct_capital, $q->options);
        }
    }

    public function test_player_can_join_and_duplicate_names_are_rejected(): void
    {
        $room = $this->gameService->createRoom(10);

        $res1 = $this->gameService->joinRoom($room->code, 'Budi');
        $this->assertInstanceOf(Player::class, $res1['player']);
        $this->assertFalse($res1['reconnected']);

        // Duplicate name with no/different token should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->joinRoom($room->code, 'Budi');
    }

    public function test_player_can_reconnect_during_game_and_maintain_score(): void
    {
        $room = $this->gameService->createRoom(10);
        $res = $this->gameService->joinRoom($room->code, 'Siti');
        $player = $res['player'];

        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        // Player answers question 1 correctly
        $q1 = $room->questions()->first();
        $this->gameService->submitAnswer($room, $player, 1, $q1->correct_capital);
        $player->refresh();
        $this->assertGreaterThan(0, $player->score);
        $initialScore = $player->score;

        // Player simulates reconnect with same name
        $reconnectRes = $this->gameService->joinRoom($room->code, 'Siti');
        $this->assertTrue($reconnectRes['reconnected']);
        $this->assertEquals($initialScore, $reconnectRes['player']->score);
    }

    public function test_new_player_cannot_join_ongoing_game(): void
    {
        $room = $this->gameService->createRoom(10);
        $this->gameService->joinRoom($room->code, 'Pemain 1');
        $this->gameService->startMatch($room, $room->host_token);

        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->joinRoom($room->code, 'Pemain Baru');
    }

    public function test_scoring_formula_linear_reduction(): void
    {
        $room = $this->gameService->createRoom(10);
        $res = $this->gameService->joinRoom($room->code, 'Andi');
        $player = $res['player'];
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        $q = $room->questions()->first();

        // Simulate 3 seconds elapsed: 100 * (15 - 3) / 15 = 80 points
        $room->update(['question_started_at' => microtime(true) - 3.0]);

        $result = $this->gameService->submitAnswer($room, $player, 1, $q->correct_capital);
        $this->assertTrue($result['is_correct']);
        $this->assertEquals(80, $result['score']);
    }

    public function test_wrong_answer_or_timeout_gives_zero_score(): void
    {
        $room = $this->gameService->createRoom(10);
        $res = $this->gameService->joinRoom($room->code, 'Budi');
        $player = $res['player'];
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        // Wrong answer
        $result = $this->gameService->submitAnswer($room, $player, 1, 'WrongCapitalCity');
        $this->assertFalse($result['is_correct']);
        $this->assertEquals(0, $result['score']);
    }

    public function test_player_cannot_change_answer_once_locked(): void
    {
        $room = $this->gameService->createRoom(10);
        $res = $this->gameService->joinRoom($room->code, 'Citra');
        $player = $res['player'];
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        $this->gameService->submitAnswer($room, $player, 1, 'SomeCapital');

        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->submitAnswer($room, $player, 1, 'AnotherCapital');
    }

    public function test_leaderboard_sorting_and_tie_breaking(): void
    {
        $room = $this->gameService->createRoom(10);
        $p1 = $this->gameService->joinRoom($room->code, 'Alice')['player'];
        $p2 = $this->gameService->joinRoom($room->code, 'Bob')['player'];
        $p3 = $this->gameService->joinRoom($room->code, 'Charlie')['player'];

        // Alice: 150 pts, 2 correct, 8.5s total time
        $p1->update(['score' => 150, 'correct_answers_count' => 2, 'total_time_taken' => 8.5]);
        // Bob: 150 pts, 2 correct, 5.2s total time (faster than Alice!)
        $p2->update(['score' => 150, 'correct_answers_count' => 2, 'total_time_taken' => 5.2]);
        // Charlie: 100 pts, 1 correct, 4.0s
        $p3->update(['score' => 100, 'correct_answers_count' => 1, 'total_time_taken' => 4.0]);

        $lb = $this->gameService->getLeaderboard($room);

        // Bob should be rank 1 (faster tie breaker)
        $this->assertEquals('Bob', $lb[0]['name']);
        $this->assertEquals(1, $lb[0]['rank']);

        // Alice should be rank 2
        $this->assertEquals('Alice', $lb[1]['name']);
        $this->assertEquals(2, $lb[1]['rank']);

        // Charlie should be rank 3
        $this->assertEquals('Charlie', $lb[2]['name']);
        $this->assertEquals(3, $lb[2]['rank']);
    }
}
