<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\Room;
use App\Services\GameService;
use App\Services\NetworkService;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrdAcceptanceCriteriaTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;
    private NetworkService $networkService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CountrySeeder::class);
        $this->gameService = app(GameService::class);
        $this->networkService = app(NetworkService::class);
    }

    /**
     * Kriteria 1: Menjalankan aplikasi offline.
     * Stack: Laravel, PHP 8.5+, SQLite, aset build lokal tanpa internet.
     */
    public function test_criterion_1_run_application_offline_stack(): void
    {
        // PHP Version
        $this->assertGreaterThanOrEqual('8.5.0', PHP_VERSION);

        // SQLite connection active
        $this->assertEquals('sqlite', config('database.default'));
        $this->assertGreaterThanOrEqual(190, Country::count());

        // Compiled Vite assets exist locally
        $this->assertFileExists(public_path('build/manifest.json'));

        // Local GeoJSON boundaries exist offline
        $this->assertFileExists(public_path('data/world-boundaries.json'));
    }

    /**
     * Kriteria 2: Akses melalui LAN.
     * NetworkService mendeteksi IPv4 lokal dan endpoint aplikasi dapat diakses.
     */
    public function test_criterion_2_lan_access_endpoints(): void
    {
        $ips = $this->networkService->getLocalIpv4Addresses();
        $this->assertNotEmpty($ips);

        foreach ($ips as $ip) {
            $this->assertMatchesRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip);
        }

        // Web endpoints are accessible
        $response = $this->get('/');
        $response->assertStatus(200);

        $hostResponse = $this->get('/host');
        $hostResponse->assertStatus(200);
    }

    /**
     * Kriteria 3: Membuat dan bergabung ke ruang.
     * Host mendapatkan kode 5 huruf unik, pemain bergabung dengan nama.
     */
    public function test_criterion_3_create_and_join_room(): void
    {
        $createRes = $this->postJson('/api/rooms', [
            'question_count' => 10,
        ]);
        $createRes->assertStatus(200);
        $code = $createRes->json('code');
        $hostToken = $createRes->json('host_token');

        $this->assertEquals(5, strlen($code));
        $this->assertNotEmpty($hostToken);

        // Player joins
        $joinRes = $this->postJson("/api/rooms/{$code}/join", [
            'name' => 'Mario',
        ]);
        $joinRes->assertStatus(200);
        $joinRes->assertJson([
            'success' => true,
            'name' => 'Mario',
            'reconnected' => false,
        ]);

        $this->assertNotEmpty($joinRes->json('player_token'));
    }

    /**
     * Kriteria 4: Validasi jumlah soal.
     * Host dapat memilih 10, 20, 30 soal; nilai invalid ditolak atau disesuaikan.
     */
    public function test_criterion_4_validate_question_count(): void
    {
        foreach ([10, 20, 30] as $count) {
            $room = $this->gameService->createRoom($count);
            $this->assertEquals($count, $room->question_count);
            $this->assertCount($count, $room->questions);
        }

        // API validation rejects invalid counts
        $badRes = $this->postJson('/api/rooms', [
            'question_count' => 15,
        ]);
        $badRes->assertStatus(422);
    }

    /**
     * Kriteria 4b: Jumlah soal melebihi dataset unik ditolak.
     * Tes dilakukan dengan menipu jumlah negara di DB agar lebih kecil dari count.
     */
    public function test_criterion_4b_exceeds_dataset_throws_exception(): void
    {
        // Hapus semua negara agar dataset kosong
        Country::truncate();
        $this->assertEquals(0, Country::count());

        // Bahkan 10 soal tidak bisa dibuat jika dataset = 0
        $this->expectException(\RuntimeException::class);
        $this->gameService->createRoom(10);
    }

    /**
     * Kriteria 5: Dataset dan opsi jawaban.
     * Setiap soal memiliki 1 jawaban benar dan 3 opsi pengecoh unik (total 4 pilihan).
     * Tidak ada negara berulang dalam satu pertandingan.
     */
    public function test_criterion_5_dataset_and_answer_options(): void
    {
        $room = $this->gameService->createRoom(20);
        $questions = $room->questions;

        $countryNames = [];
        foreach ($questions as $q) {
            // Check 4 options
            $this->assertCount(4, $q->options);
            // Check all options are distinct
            $this->assertCount(4, array_unique($q->options));
            // Check correct answer is among options
            $this->assertContains($q->correct_capital, $q->options);

            $countryNames[] = $q->country_name;
        }

        // Check no duplicate country in match
        $this->assertCount(20, array_unique($countryNames));
    }

    /**
     * Kriteria 6: Timer 15 detik berbasis server.
     * Jawaban setelah tenggat (t > 15s) dinilai 0 poin.
     */
    public function test_criterion_6_timer_fifteen_seconds_server_authoritative(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain Cepat');
        $player = $join['player'];
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        $q = $room->questions()->first();

        // Simulate 16.5 seconds elapsed (timeout)
        $room->update(['question_started_at' => microtime(true) - 16.5]);

        $result = $this->gameService->submitAnswer($room, $player, 1, $q->correct_capital);
        $this->assertFalse($result['is_correct']);
        $this->assertEquals(0, $result['score']);
    }

    /**
     * Kriteria 7: Jawaban terkunci.
     * Hanya satu jawaban yang diterima per soal per pemain, tidak dapat diubah.
     */
    public function test_criterion_7_locked_answer_single_submission(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain 1');
        $player = $join['player'];
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        $q = $room->questions()->first();

        // First answer accepted
        $res1 = $this->gameService->submitAnswer($room, $player, 1, $q->options[0]);
        $this->assertNotNull($res1);

        // Attempt second answer -> must throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->submitAnswer($room, $player, 1, $q->options[1]);
    }

    /**
     * Kriteria 8: Skor linear (0 - 100 poin).
     * S = ceil(100 * (15 - t) / 15) untuk jawaban benar; 0 untuk salah.
     */
    public function test_criterion_8_linear_scoring_formula(): void
    {
        $expectedScores = [
            1.0 => 94,
            3.0 => 80,
            6.0 => 60,
            9.0 => 40,
            12.0 => 20,
            15.0 => 0,
        ];

        foreach ($expectedScores as $secondsElapsed => $expectedScore) {
            $room = $this->gameService->createRoom(10);
            $join = $this->gameService->joinRoom($room->code, "Pemain_{$secondsElapsed}");
            $player = $join['player'];
            $this->gameService->startMatch($room, $room->host_token);

            $q = $room->questions()->first();
            $room->update(['question_started_at' => microtime(true) - $secondsElapsed]);

            $res = $this->gameService->submitAnswer($room, $player, 1, $q->correct_capital);
            $this->assertEquals($expectedScore, $res['score'], "Gagal pada t = {$secondsElapsed}s");
        }

        // Wrong answer check
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain Salah');
        $this->gameService->startMatch($room, $room->host_token);
        $resWrong = $this->gameService->submitAnswer($room, $join['player'], 1, 'KotaSalah');
        $this->assertEquals(0, $resWrong['score']);
    }

    /**
     * Kriteria 9: Sinkronisasi pertandingan.
     * State menyajikan waktu, status, soal, dan menyembunyikan kunci jawaban dari pemain aktif.
     */
    public function test_criterion_9_game_state_synchronization(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain Synced');
        $player = $join['player'];
        $this->gameService->startMatch($room, $room->host_token);

        // Player state check: correct_capital must NOT be leaked
        $playerState = $this->gameService->getRoomState($room, $player, false);
        $this->assertEquals('playing', $playerState['status']);
        $this->assertArrayNotHasKey('correct_capital', $playerState['question']);

        // Host state check: correct_capital IS available to host
        $hostState = $this->gameService->getRoomState($room, null, true);
        $this->assertArrayHasKey('correct_capital', $hostState['question']);
    }

    /**
     * Kriteria 10: Leaderboard dan pemecah seri.
     * Skor -> Jawaban Benar -> Waktu Tercepat -> Berbagi Peringkat.
     */
    public function test_criterion_10_leaderboard_and_tie_breaking(): void
    {
        $room = $this->gameService->createRoom(10);
        $p1 = $this->gameService->joinRoom($room->code, 'P1')['player'];
        $p2 = $this->gameService->joinRoom($room->code, 'P2')['player'];
        $p3 = $this->gameService->joinRoom($room->code, 'P3')['player'];

        // P1 & P2 have exact same score & correct count, but P1 is faster
        $p1->update(['score' => 100, 'correct_answers_count' => 1, 'total_time_taken' => 3.0]);
        $p2->update(['score' => 100, 'correct_answers_count' => 1, 'total_time_taken' => 5.0]);
        // P3 has same score and time as P2, so they share the tie
        $p3->update(['score' => 100, 'correct_answers_count' => 1, 'total_time_taken' => 5.0]);

        $lb = $this->gameService->getLeaderboard($room);

        $this->assertEquals('P1', $lb[0]['name']);
        $this->assertEquals(1, $lb[0]['rank']);

        $this->assertEquals('P2', $lb[1]['name']);
        $this->assertEquals(2, $lb[1]['rank']);

        // P3 shares rank 2 with P2
        $this->assertEquals('P3', $lb[2]['name']);
        $this->assertEquals(2, $lb[2]['rank']);
    }

    /**
     * Kriteria 11: Host mengelola pertandingan.
     * Host dapat mengeluarkan pemain (kick), memulai, dan mengakhiri lebih awal.
     */
    public function test_criterion_11_host_management_start_kick_end(): void
    {
        $room = $this->gameService->createRoom(10);
        $badJoin = $this->gameService->joinRoom($room->code, 'Pemain Bad');
        $badPlayer = $badJoin['player'];
        // Add a second player so match can start after bad player is kicked
        $this->gameService->joinRoom($room->code, 'Pemain Baik');

        // Kick the bad player
        $this->gameService->kickPlayer($room, $badPlayer->id, $room->host_token);
        $badPlayer->refresh();
        $this->assertTrue((bool) $badPlayer->is_kicked);

        // Start match with at least 1 active player remaining
        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();
        $this->assertEquals('playing', $room->status);

        // Kicked player cannot answer
        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->submitAnswer($room, $badPlayer, 1, 'Tokyo');
    }

    /**
     * Kriteria 12: Pemain bergabung kembali (Reconnect).
     * Pemain yang terputus dapat masuk kembali dengan kode dan nama sama, skor dipulihkan.
     */
    public function test_criterion_12_player_reconnection_preserves_score(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Siti');
        $player = $join['player'];

        $this->gameService->startMatch($room, $room->host_token);
        $room->refresh();

        // Answer and get points
        $q = $room->questions()->first();
        $this->gameService->submitAnswer($room, $player, 1, $q->correct_capital);
        $player->refresh();
        $savedScore = $player->score;
        $this->assertGreaterThan(0, $savedScore);

        // Reconnect with same name
        $reconnectRes = $this->gameService->joinRoom($room->code, 'Siti');
        $this->assertTrue($reconnectRes['reconnected']);
        $this->assertEquals($savedScore, $reconnectRes['player']->score);

        // Cannot answer again question 1
        $this->expectException(\InvalidArgumentException::class);
        $this->gameService->submitAnswer($room, $reconnectRes['player'], 1, $q->correct_capital);
    }

    /**
     * Kriteria 13: Hasil akhir & kembali ke lobi.
     * Menampilkan leaderboard akhir dan opsi kembali ke lobi.
     */
    public function test_criterion_13_final_results_and_return_to_lobby(): void
    {
        $room = $this->gameService->createRoom(10);
        $this->gameService->joinRoom($room->code, 'Pemain 1');
        $this->gameService->startMatch($room, $room->host_token);

        // End match
        $this->gameService->endMatchEarly($room, $room->host_token);
        $room->refresh();
        $this->assertEquals('ended', $room->status);

        // Reset to lobby
        $this->gameService->resetToLobby($room, $room->host_token);
        $room->refresh();
        $this->assertEquals('lobby', $room->status);
        $this->assertEquals(0, $room->current_question_index);
    }

    /**
     * Kriteria 14: Penyimpanan sementara.
     * Skor dan riwayat jawaban dihapus saat ruang ditutup/direset ke lobi.
     */
    public function test_criterion_14_temporary_storage_lifecycle(): void
    {
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, 'Pemain 1');
        $player = $join['player'];
        $this->gameService->startMatch($room, $room->host_token);

        $q = $room->questions()->first();
        $this->gameService->submitAnswer($room, $player, 1, $q->correct_capital);
        $this->assertEquals(1, PlayerAnswer::where('room_id', $room->id)->count());

        // Reset to lobby clears answers
        $this->gameService->resetToLobby($room, $room->host_token);
        $this->assertEquals(0, PlayerAnswer::where('room_id', $room->id)->count());
        $player->refresh();
        $this->assertEquals(0, $player->score);
    }

    /**
     * Kriteria 15: Tampilan responsif & elemen UI.
     * Memastikan view HTML ter-render tanpa error dan memiliki elemen peta/tombol.
     */
    public function test_criterion_15_responsive_views_and_ui_elements(): void
    {
        $room = $this->gameService->createRoom(10);

        // Host room page renders with map
        $hostView = $this->get("/host/{$room->code}");
        $hostView->assertStatus(200);
        $hostView->assertSee('hostMap');
        $hostView->assertSee($room->code);

        // Player room page renders with map
        $playerView = $this->get("/play/{$room->code}");
        $playerView->assertStatus(200);
        $playerView->assertSee('playerMap');
        $playerView->assertSee($room->code);
    }

    /**
     * Kriteria 16: Tanpa ketergantungan eksternal & penguatan keamanan.
     * 0 external CDN scripts, XSS sanitization pada nama, dan timing-safe auth host.
     */
    public function test_criterion_16_zero_external_dependencies_and_security_hardening(): void
    {
        // 1. Verify layout has no external CDN URLs
        $response = $this->get('/');
        $content = $response->getContent();
        $this->assertStringNotContainsString('https://cdn.', $content);
        $this->assertStringNotContainsString('https://unpkg.com', $content);
        $this->assertStringNotContainsString('https://cdnjs.cloudflare.com', $content);
        $this->assertStringNotContainsString('fonts.googleapis.com', $content);

        // 2. XSS sanitization on player names
        $room = $this->gameService->createRoom(10);
        $join = $this->gameService->joinRoom($room->code, '<script>alert("XSS")</script>Budi');
        $this->assertEquals('alert("XSS")Budi', $join['player']->name);
        $this->assertStringNotContainsString('<script>', $join['player']->name);

        // 3. Timing-safe host token verification
        $this->assertTrue($room->isHost($room->host_token));
        $this->assertFalse($room->isHost('wrong_fake_token'));
        $this->assertFalse($room->isHost(''));
        $this->assertFalse($room->isHost(null));
    }
}
