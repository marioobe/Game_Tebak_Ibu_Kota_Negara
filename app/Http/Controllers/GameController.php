<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Room;
use App\Services\GameService;
use App\Services\NetworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GameController extends Controller
{
    public function __construct(
        public GameService $gameService,
        public NetworkService $networkService
    ) {
    }

    /**
     * Home / Landing page.
     */
    public function index(): View
    {
        return view('game.index');
    }

    /**
     * Host room setup page.
     */
    public function hostSetup(): View
    {
        $ips = $this->networkService->getLocalIpv4Addresses();

        return view('game.host_setup', [
            'localIps' => $ips,
        ]);
    }

    /**
     * Create room API.
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_count' => 'required|integer|in:10,20,30',
        ]);

        $room = $this->gameService->createRoom((int) $validated['question_count']);

        return response()->json([
            'success' => true,
            'code' => $room->code,
            'host_token' => $room->host_token,
            'redirect_url' => route('host.room', ['code' => $room->code]),
        ]);
    }

    /**
     * Host screen view.
     */
    public function hostRoom(string $code): View
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $localIps = $this->networkService->getLocalIpv4Addresses();

        return view('game.host', [
            'room' => $room,
            'localIps' => $localIps,
        ]);
    }

    /**
     * Player screen view.
     */
    public function playerRoom(string $code): View
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();

        return view('game.play', [
            'room' => $room,
        ]);
    }

    /**
     * Player join API.
     */
    public function joinRoom(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:25',
            'player_token' => 'nullable|string',
        ]);

        try {
            $result = $this->gameService->joinRoom(
                $code,
                $validated['name'],
                $validated['player_token'] ?? null
            );

            return response()->json([
                'success' => true,
                'player_id' => $result['player']->id,
                'player_token' => $result['player']->player_token,
                'name' => $result['player']->name,
                'reconnected' => $result['reconnected'],
                'score' => $result['player']->score,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start match API.
     */
    public function startMatch(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $hostToken = (string) $request->header('X-Host-Token', $request->input('host_token'));

        try {
            $this->gameService->startMatch($room, $hostToken);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Submit answer API.
     */
    public function submitAnswer(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();

        $validated = $request->validate([
            'player_token' => 'required|string',
            'question_order' => 'required|integer',
            'selected_capital' => 'required|string',
        ]);

        $player = Player::where('room_id', $room->id)
            ->where('player_token', $validated['player_token'])
            ->first();

        if (! $player) {
            return response()->json(['success' => false, 'message' => 'Pemain tidak valid.'], 403);
        }

        try {
            $result = $this->gameService->submitAnswer(
                $room,
                $player,
                (int) $validated['question_order'],
                $validated['selected_capital']
            );

            return response()->json([
                'success' => true,
                'result' => $result,
                'current_score' => $player->score,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Advance to next question or end game API.
     */
    public function nextQuestion(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $hostToken = (string) $request->header('X-Host-Token', $request->input('host_token'));

        try {
            $this->gameService->advanceOrFinishQuestion($room, $hostToken);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Kick player API.
     */
    public function kickPlayer(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $hostToken = (string) $request->header('X-Host-Token', $request->input('host_token'));
        $playerId = (int) $request->input('player_id');

        try {
            $this->gameService->kickPlayer($room, $playerId, $hostToken);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * End match early API.
     */
    public function endMatch(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $hostToken = (string) $request->header('X-Host-Token', $request->input('host_token'));

        try {
            $this->gameService->endMatchEarly($room, $hostToken);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reset room to lobby API.
     */
    public function resetRoom(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $hostToken = (string) $request->header('X-Host-Token', $request->input('host_token'));

        try {
            $this->gameService->resetToLobby($room, $hostToken);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get room state (REST Polling fallback / Instant state check).
     */
    public function getRoomState(Request $request, string $code): JsonResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();

        $playerToken = $request->query('player_token');
        $player = null;
        if ($playerToken) {
            $player = Player::where('room_id', $room->id)
                ->where('player_token', $playerToken)
                ->first();

            if ($player) {
                $player->update(['last_seen_at' => now(), 'is_connected' => true]);
            }
        }

        $hostToken = $request->header('X-Host-Token', $request->query('host_token'));
        $isHost = $room->isHost($hostToken);

        $state = $this->gameService->getRoomState($room, $player, $isHost);

        return response()->json($state);
    }

    /**
     * Server-Sent Events (SSE) stream for real-time LAN communication.
     */
    public function streamEvents(Request $request, string $code): StreamedResponse
    {
        $room = Room::where('code', strtoupper($code))->firstOrFail();
        $playerToken = $request->query('player_token');
        $hostToken = $request->query('host_token');
        $isHost = $room->isHost($hostToken);

        $response = new StreamedResponse(function () use ($room, $playerToken, $isHost) {
            // Keep connection open for push events
            $seconds = 0;
            while ($seconds < 25) { // stream in chunks of 25s, browser EventSource reconnects automatically
                if (connection_aborted()) {
                    break;
                }

                $room->refresh();
                $player = null;
                if ($playerToken) {
                    $player = Player::where('room_id', $room->id)
                        ->where('player_token', $playerToken)
                        ->first();
                }

                $state = $this->gameService->getRoomState($room, $player, $isHost);

                echo 'data: ' . json_encode($state) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                usleep(800000); // 800ms intervals for smooth countdown & instant answer feedback
                $seconds++;
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
