<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Room;
use App\Services\GameService;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CountrySeeder::class);
        $this->gameService = app(GameService::class);
    }

    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Tebak Ibu Kota Negara');
        $response->assertSee('Kode Ruang');
    }

    public function test_host_setup_page_loads_successfully(): void
    {
        $response = $this->get('/host');
        $response->assertStatus(200);
        $response->assertSee('Buat Ruang Pertandingan');
        $response->assertSee('10');
        $response->assertSee('20');
        $response->assertSee('30');
    }

    public function test_can_create_room_via_api(): void
    {
        $response = $this->postJson('/api/rooms', [
            'question_count' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'code',
            'host_token',
            'redirect_url',
        ]);

        $this->assertDatabaseHas('rooms', [
            'code' => $response->json('code'),
            'question_count' => 10,
            'status' => 'lobby',
        ]);
    }

    public function test_player_can_join_room_via_api(): void
    {
        $room = $this->gameService->createRoom(10);

        $response = $this->postJson("/api/rooms/{$room->code}/join", [
            'name' => 'Budi Santoso',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'name' => 'Budi Santoso',
            'reconnected' => false,
        ]);

        $this->assertDatabaseHas('players', [
            'room_id' => $room->id,
            'name' => 'Budi Santoso',
        ]);
    }

    public function test_host_can_start_match_and_access_state(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain 1');

        $response = $this->postJson("/api/rooms/{$room->code}/start", [], [
            'X-Host-Token' => $room->host_token,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'playing',
            'current_question_index' => 1,
        ]);

        // Check state
        $stateResponse = $this->getJson("/api/rooms/{$room->code}/state?player_token={$join['player']->player_token}");
        $stateResponse->assertStatus(200);
        $stateResponse->assertJsonStructure([
            'room_code',
            'status',
            'question' => [
                'order',
                'total',
                'country_name',
                'options',
                'time_remaining',
            ],
            'leaderboard',
        ]);
    }

    public function test_player_can_submit_answer_via_api(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain 1');
        $player = $join['player'];

        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        $q = $room->questions()->where('question_order', 1)->first();

        $response = $this->postJson("/api/rooms/{$room->code}/answer", [
            'player_token' => $player->player_token,
            'question_order' => 1,
            'selected_capital' => $q->correct_capital,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('result.is_correct', true);

        // Attempting to submit again should fail with 422
        $resubmit = $this->postJson("/api/rooms/{$room->code}/answer", [
            'player_token' => $player->player_token,
            'question_order' => 1,
            'selected_capital' => 'SomethingElse',
        ]);
        $resubmit->assertStatus(422);
    }

    public function test_host_can_kick_player(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain Nakal');
        $player = $join['player'];

        $response = $this->postJson("/api/rooms/{$room->code}/kick", [
            'player_id' => $player->id,
        ], [
            'X-Host-Token' => $room->host_token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'is_kicked' => true,
        ]);
    }

    public function test_host_can_end_match_early(): void
    {
        $room = $this->gameService->createRoom(10);
        $this->gameService->joinRoom($room->code, 'Pemain 1');
        $this->gameService->startMatch($room, $room->host_token);

        $response = $this->postJson("/api/rooms/{$room->code}/end", [], [
            'X-Host-Token' => $room->host_token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'ended',
        ]);
    }

    public function test_host_can_reset_match_to_lobby(): void
    {
        $room = $this->gameService->createRoom(10);
        $this->gameService->joinRoom($room->code, 'Pemain 1');
        $this->gameService->startMatch($room, $room->host_token);
        $this->gameService->endMatchEarly($room, $room->host_token);

        $response = $this->postJson("/api/rooms/{$room->code}/reset", [], [
            'X-Host-Token' => $room->host_token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'lobby',
            'current_question_index' => 0,
        ]);
    }
}
