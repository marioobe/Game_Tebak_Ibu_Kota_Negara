@extends('layouts.app')

@section('title', 'Host Panel - Ruang ' . $room->code)

@section('content')
<div class="max-w-4xl mx-auto w-full py-4 space-y-6">
    <!-- Top Bar: Room Code & Status -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center gap-3">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Kode Ruang:</span>
            <span id="roomCodeDisplay" class="font-mono text-3xl font-black tracking-widest text-indigo-400 bg-slate-950 px-4 py-1.5 rounded-xl border border-indigo-500/30 shadow-inner">
                {{ $room->code }}
            </span>
            <button type="button" id="copyLinkBtn" class="text-xs px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                📋 Salin Link
            </button>
        </div>

        <div class="flex items-center gap-2">
            <span id="statusBadge" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                LOBI
            </span>
            <span class="text-xs text-slate-400 font-medium">
                {{ $room->question_count }} Soal
            </span>
        </div>
    </div>

    <!-- 1. LOBBY VIEW -->
    <div id="lobbySection" class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Left: Join Info & QR Code -->
        <div class="md:col-span-5 bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col items-center text-center justify-center space-y-4 shadow-xl">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300">
                Pindai untuk Bergabung
            </h3>
            
            <div class="p-3 bg-white rounded-2xl shadow-lg shadow-black/50">
                <canvas id="qrCanvas" class="w-48 h-48 block"></canvas>
            </div>

            <div class="text-xs text-slate-400 space-y-1">
                <p>Atau buka browser di HP & ketik:</p>
                <p id="directUrlDisplay" class="font-mono text-sm font-bold text-indigo-300 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800 break-all select-all">
                    http://{{ $localIps[0] ?? 'localhost' }}:8000
                </p>
            </div>
        </div>

        <!-- Right: Players List & Start Match -->
        <div class="md:col-span-7 bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-xl space-y-6">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <span>👥</span> Pemain di Lobi
                    </h3>
                    <span id="playerCountBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-slate-300">
                        0 Pemain
                    </span>
                </div>

                <div id="playerList" class="mt-4 space-y-2 max-h-64 overflow-y-auto pr-1">
                    <p class="text-xs text-slate-500 italic py-4 text-center">Menunggu pemain bergabung...</p>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 space-y-2">
                <div id="hostAlert" class="hidden p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs"></div>
                <button type="button" id="startMatchBtn" disabled
                    class="w-full py-4 px-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                    <span>Mulai Pertandingan Sekarang</span>
                    <span>🚀</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. PLAYING VIEW -->
    <div id="playingSection" class="hidden space-y-6">
        <!-- Question Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
            <!-- Progress & Timer Header -->
            <div class="flex items-center justify-between">
                <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1.5 rounded-xl">
                    <span id="questionOrderBadge">SOAL 01 / 10</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Waktu:</span>
                    <span id="timerSeconds" class="font-mono text-2xl font-black text-amber-400">15.0</span>
                    <span class="text-xs text-slate-400">detik</span>
                </div>
            </div>

            <!-- Timer Progress Bar -->
            <div class="w-full h-3 bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                <div id="timerBar" class="h-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500 transition-all duration-200" style="width: 100%;"></div>
            </div>

            <!-- Question Prompt & Map -->
            <div class="py-2 text-center space-y-2">
                <p class="text-xs uppercase tracking-wider font-semibold text-slate-400">Apa ibu kota dari negara:</p>
                <h2 id="countryNameDisplay" class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    Jepang
                </h2>

                <!-- Leaflet Country Boundary Map Container -->
                <div class="w-full h-64 sm:h-72 rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 relative shadow-inner my-3">
                    <div id="hostMap" class="w-full h-full"></div>
                    <div class="absolute bottom-2 left-2 z-[400] px-2.5 py-1 rounded-lg bg-slate-950/80 backdrop-blur border border-slate-800 text-[10px] text-slate-400">
                        🗺️ Batas Administrasi Negara (Offline Vector)
                    </div>
                </div>
            </div>

            <!-- Options Grid (Host sees correct answer highlighted) -->
            <div id="hostOptionsGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Dynamically populated options -->
            </div>

            <!-- Answer Status Info -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-800 text-xs text-slate-400">
                <div id="answersCountStatus">
                    Menunggu pemain memilih jawaban...
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="nextQuestionBtn"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                        <span>Soal Berikutnya</span>
                        <span>➔</span>
                    </button>
                    <button type="button" id="endEarlyBtn"
                        class="px-3 py-2 bg-rose-950/60 hover:bg-rose-900/80 border border-rose-800/60 text-rose-300 font-semibold rounded-xl transition-all">
                        Akhiri Pertandingan
                    </button>
                </div>
            </div>
        </div>

        <!-- Live Leaderboard Sidebar/Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                <span>🏆</span> Papan Peringkat Sementara
            </h3>
            <div id="liveLeaderboardTable" class="space-y-2">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- 3. ENDED VIEW -->
    <div id="endedSection" class="hidden space-y-6">
        <div class="text-center py-6">
            <div class="inline-flex p-4 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-4xl mb-3 shadow-inner">
                🎉
            </div>
            <h2 class="text-3xl font-black text-white">Pertandingan Selesai!</h2>
            <p class="text-sm text-slate-400 mt-1">Berikut adalah hasil akhir dan perolehan skor para juara</p>
        </div>

        <!-- Podium & Final Standings -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div id="finalLeaderboardList" class="space-y-3">
                <!-- Dynamically populated -->
            </div>

            <div class="pt-6 border-t border-slate-800 flex justify-center">
                <button type="button" id="resetToLobbyBtn"
                    class="py-3.5 px-6 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <span>Mulai Game Baru (Kembali ke Lobi)</span>
                    <span>🔄</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roomCode = '{{ $room->code }}';
        let hostToken = localStorage.getItem('host_token_' + roomCode) || '{{ $room->host_token }}';
        localStorage.setItem('host_token_' + roomCode, hostToken);

        const lobbySection = document.getElementById('lobbySection');
        const playingSection = document.getElementById('playingSection');
        const endedSection = document.getElementById('endedSection');
        const statusBadge = document.getElementById('statusBadge');
        const playerList = document.getElementById('playerList');
        const playerCountBadge = document.getElementById('playerCountBadge');
        const startMatchBtn = document.getElementById('startMatchBtn');
        const nextQuestionBtn = document.getElementById('nextQuestionBtn');
        const endEarlyBtn = document.getElementById('endEarlyBtn');
        const resetToLobbyBtn = document.getElementById('resetToLobbyBtn');
        const hostAlert = document.getElementById('hostAlert');

        const questionOrderBadge = document.getElementById('questionOrderBadge');
        const countryNameDisplay = document.getElementById('countryNameDisplay');
        const hostOptionsGrid = document.getElementById('hostOptionsGrid');
        const timerSeconds = document.getElementById('timerSeconds');
        const timerBar = document.getElementById('timerBar');
        const answersCountStatus = document.getElementById('answersCountStatus');
        const liveLeaderboardTable = document.getElementById('liveLeaderboardTable');
        const finalLeaderboardList = document.getElementById('finalLeaderboardList');

        // Setup Join Link and QR Code
        const primaryIp = '{{ $localIps[0] ?? "localhost" }}';
        const port = window.location.port ? ':' + window.location.port : '';
        const joinUrl = `${window.location.protocol}//${primaryIp}${port}/?code=${roomCode}`;
        
        document.getElementById('directUrlDisplay').textContent = `${window.location.protocol}//${primaryIp}${port}`;

        if (window.QRCode) {
            window.QRCode.toCanvas(document.getElementById('qrCanvas'), joinUrl, {
                width: 192,
                margin: 2,
                color: { dark: '#020617', light: '#ffffff' }
            });
        }

        // Copy link handler
        document.getElementById('copyLinkBtn').addEventListener('click', () => {
            navigator.clipboard.writeText(joinUrl).then(() => {
                const btn = document.getElementById('copyLinkBtn');
                btn.textContent = '✅ Tersalin!';
                setTimeout(() => btn.textContent = '📋 Salin Link', 2000);
            });
        });

        let currentState = null;
        let pollTimer = null;
        let hostGameMap = null;
        let currentCountryName = null;
        let currentRevealed = false;

        function updateUI(state) {
            currentState = state;

            // Status badge
            statusBadge.textContent = state.status.toUpperCase();

            if (state.status === 'lobby') {
                lobbySection.classList.remove('hidden');
                playingSection.classList.add('hidden');
                endedSection.classList.add('hidden');

                const players = state.lobby_players || [];
                playerCountBadge.textContent = `${players.length} Pemain`;

                if (players.length === 0) {
                    playerList.innerHTML = '<p class="text-xs text-slate-500 italic py-4 text-center">Menunggu pemain bergabung...</p>';
                    startMatchBtn.disabled = true;
                } else {
                    startMatchBtn.disabled = false;
                    playerList.innerHTML = players.map(p => `
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full ${p.is_connected ? 'bg-emerald-400' : 'bg-slate-500'}"></span>
                                <span class="text-sm font-semibold text-slate-200">${escapeHtml(p.name)}</span>
                            </div>
                            <button type="button" onclick="kickPlayer(${p.id})" class="text-xs px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition-colors">
                                Keluarkan
                            </button>
                        </div>
                    `).join('');
                }
            } else if (state.status === 'playing') {
                lobbySection.classList.add('hidden');
                playingSection.classList.remove('hidden');
                endedSection.classList.add('hidden');

                // Initialize map if not yet created
                if (!hostGameMap && window.createCountryMap) {
                    setTimeout(() => {
                        hostGameMap = window.createCountryMap('hostMap');
                        if (hostGameMap && state.question) {
                            hostGameMap.focusCountry(state.question.country_name, state.question.is_time_up, state.question.correct_capital);
                        }
                    }, 50);
                }

                if (state.question) {
                    const q = state.question;
                    questionOrderBadge.textContent = `SOAL ${String(q.order).padStart(2, '0')} / ${String(q.total).padStart(2, '0')}`;
                    countryNameDisplay.textContent = q.country_name;

                    // Update Leaflet Map boundary & capital marker
                    if (hostGameMap && (currentCountryName !== q.country_name || currentRevealed !== q.is_time_up)) {
                        currentCountryName = q.country_name;
                        currentRevealed = q.is_time_up;
                        hostGameMap.focusCountry(q.country_name, q.is_time_up, q.correct_capital);
                    }

                    // Timer
                    const rem = Math.max(0, q.time_remaining);
                    timerSeconds.textContent = rem.toFixed(1);
                    const pct = Math.max(0, Math.min(100, (rem / q.duration) * 100));
                    timerBar.style.width = pct + '%';

                    if (rem <= 3) {
                        timerSeconds.className = 'font-mono text-2xl font-black text-rose-400 animate-pulse';
                    } else {
                        timerSeconds.className = 'font-mono text-2xl font-black text-amber-400';
                    }

                    // Render options with correct answer indicated for host
                    hostOptionsGrid.innerHTML = q.options.map((opt, idx) => {
                        const letter = String.fromCharCode(65 + idx);
                        const isCorrect = q.correct_capital && opt === q.correct_capital;
                        return `
                            <div class="p-3.5 rounded-xl border flex items-center justify-between text-sm font-semibold transition-all ${
                                isCorrect 
                                    ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300' 
                                    : 'bg-slate-950 border-slate-800 text-slate-300'
                            }">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-300">${letter}</span>
                                    <span>${escapeHtml(opt)}</span>
                                </div>
                                ${isCorrect ? '<span class="text-xs bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded-md font-bold">Kunci Jawaban</span>' : ''}
                            </div>
                        `;
                    }).join('');

                    if (q.is_time_up) {
                        answersCountStatus.innerHTML = '<span class="text-amber-400 font-bold">⏰ Waktu Habis! Tekan Soal Berikutnya.</span>';
                    } else {
                        answersCountStatus.innerHTML = '<span class="text-slate-400">Pemain sedang menjawab...</span>';
                    }
                }

                // Render live leaderboard
                renderLeaderboardTable(liveLeaderboardTable, state.leaderboard);
            } else if (state.status === 'ended') {
                lobbySection.classList.add('hidden');
                playingSection.classList.add('hidden');
                endedSection.classList.remove('hidden');

                renderFinalLeaderboard(finalLeaderboardList, state.leaderboard);
            }
        }

        function renderLeaderboardTable(container, leaderboard) {
            if (!leaderboard || leaderboard.length === 0) {
                container.innerHTML = '<p class="text-xs text-slate-500 text-center py-2">Belum ada pemain.</p>';
                return;
            }

            container.innerHTML = leaderboard.map(p => {
                let medal = '';
                if (p.rank === 1) medal = '🥇';
                else if (p.rank === 2) medal = '🥈';
                else if (p.rank === 3) medal = '🥉';

                return `
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-sm w-6 text-center ${p.rank <= 3 ? 'text-amber-400' : 'text-slate-500'}">
                                ${medal || '#' + p.rank}
                            </span>
                            <span class="font-semibold text-slate-200 text-sm">${escapeHtml(p.name)}</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-mono">
                            <span class="text-slate-400">${p.correct_answers_count} benar</span>
                            <span class="font-bold text-amber-400 text-sm">${p.score} pts</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderFinalLeaderboard(container, leaderboard) {
            if (!leaderboard || leaderboard.length === 0) {
                container.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">Tidak ada skor tercatat.</p>';
                return;
            }

            container.innerHTML = leaderboard.map(p => {
                let badgeClass = 'bg-slate-950 border-slate-800';
                let icon = `#${p.rank}`;
                if (p.rank === 1) {
                    badgeClass = 'bg-amber-500/10 border-amber-500/40 shadow-lg shadow-amber-500/10';
                    icon = '🏆 Juara 1';
                } else if (p.rank === 2) {
                    badgeClass = 'bg-slate-400/10 border-slate-400/30';
                    icon = '🥈 Juara 2';
                } else if (p.rank === 3) {
                    badgeClass = 'bg-amber-700/10 border-amber-700/30';
                    icon = '🥉 Juara 3';
                }

                return `
                    <div class="p-4 rounded-xl border flex items-center justify-between ${badgeClass}">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-xs uppercase px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-amber-300">${icon}</span>
                            <span class="font-bold text-base text-white">${escapeHtml(p.name)}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-xl font-black text-amber-400">${p.score}</span>
                            <span class="text-xs text-slate-400 block">${p.correct_answers_count} Jawaban Benar (${p.total_time_taken.toFixed(1)}s)</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function fetchState() {
            try {
                const res = await fetch(`/api/rooms/${roomCode}/state?host_token=${encodeURIComponent(hostToken)}`);
                if (res.ok) {
                    const data = await res.json();
                    updateUI(data);
                }
            } catch (err) {
                console.error('Fetch state error', err);
            }
        }

        // Action Handlers
        startMatchBtn.addEventListener('click', async () => {
            startMatchBtn.disabled = true;
            try {
                const res = await fetch(`/api/rooms/${roomCode}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Host-Token': hostToken,
                    }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal memulai pertandingan.');
                fetchState();
            } catch (err) {
                hostAlert.textContent = err.message;
                hostAlert.classList.remove('hidden');
                startMatchBtn.disabled = false;
            }
        });

        nextQuestionBtn.addEventListener('click', async () => {
            try {
                const res = await fetch(`/api/rooms/${roomCode}/next`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Host-Token': hostToken,
                    }
                });
                fetchState();
            } catch (err) {
                console.error(err);
            }
        });

        endEarlyBtn.addEventListener('click', async () => {
            if (!confirm('Yakin ingin mengakhiri pertandingan lebih awal?')) return;
            try {
                await fetch(`/api/rooms/${roomCode}/end`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Host-Token': hostToken,
                    }
                });
                fetchState();
            } catch (err) {
                console.error(err);
            }
        });

        resetToLobbyBtn.addEventListener('click', async () => {
            try {
                await fetch(`/api/rooms/${roomCode}/reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Host-Token': hostToken,
                    }
                });
                fetchState();
            } catch (err) {
                console.error(err);
            }
        });

        window.kickPlayer = async function(playerId) {
            if (!confirm('Keluarkan pemain ini?')) return;
            try {
                await fetch(`/api/rooms/${roomCode}/kick`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Host-Token': hostToken,
                    },
                    body: JSON.stringify({ player_id: playerId })
                });
                fetchState();
            } catch (err) {
                console.error(err);
            }
        };

        // Start high-speed real-time polling (500ms)
        fetchState();
        pollTimer = setInterval(fetchState, 500);
    });
</script>
@endsection
