@extends('layouts.app')

@section('title', 'Tebak Ibu Kota - Ruang ' . $room->code)

@section('content')
<div class="max-w-lg mx-auto w-full py-2 space-y-4">
    <!-- Player Top Bar (Name, Room, Score) -->
    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-sm font-bold text-indigo-300">
                👤
            </div>
            <div>
                <p id="playerNameLabel" class="text-sm font-bold text-white truncate max-w-[120px] sm:max-w-[180px]">Pemain</p>
                <p class="text-[10px] text-slate-400 font-mono tracking-wider">Ruang: <span class="font-bold text-indigo-400">{{ $room->code }}</span></p>
            </div>
        </div>

        <div class="text-right">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Skor Kamu</span>
            <span id="playerScoreDisplay" class="font-mono text-xl font-black text-amber-400">0</span>
            <span class="text-[10px] text-slate-400">pts</span>
        </div>
    </div>

    <!-- KICKED ALERT -->
    <div id="kickedAlert" class="hidden p-6 rounded-2xl bg-rose-950/80 border border-rose-800 text-center space-y-3">
        <span class="text-4xl block">🚫</span>
        <h3 class="text-lg font-bold text-rose-300">Dikeluarkan dari Permainan</h3>
        <p class="text-xs text-rose-400">Host telah mengeluarkan Anda dari ruang ini.</p>
        <a href="{{ route('home') }}" class="inline-block px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white rounded-xl">
            Kembali ke Beranda
        </a>
    </div>

    <!-- 1. LOBBY STATE (Waiting for host) -->
    <div id="playerLobbySection" class="p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center space-y-6 shadow-2xl">
        <div class="inline-flex p-4 rounded-3xl bg-indigo-500/10 border border-indigo-500/20 text-4xl shadow-inner animate-bounce">
            ⏳
        </div>
        <div>
            <h2 class="text-xl font-black text-white">Menunggu Host Memulai...</h2>
            <p class="text-xs text-slate-400 mt-1">Bersiaplah! Pertandingan akan segera dimulai.</p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 space-y-2">
            <span class="text-xs font-semibold text-slate-400 block">Pemain yang Sudah Masuk:</span>
            <div id="lobbyPlayersContainer" class="flex flex-wrap justify-center gap-2 pt-1">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- 2. PLAYING STATE (Question Card & Options) -->
    <div id="playerPlayingSection" class="hidden space-y-4">
        <!-- Question Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-2xl space-y-5">
            <!-- Header: Question number & Timer -->
            <div class="flex items-center justify-between">
                <span id="playerQuestionOrder" class="text-xs font-bold uppercase tracking-wider text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-xl">
                    SOAL 01 / 10
                </span>

                <div class="flex items-center gap-1.5">
                    <span id="playerTimer" class="font-mono text-2xl font-black text-amber-400">15.0</span>
                    <span class="text-xs text-slate-400">s</span>
                </div>
            </div>

            <!-- Timer Bar -->
            <div class="w-full h-2.5 bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                <div id="playerTimerBar" class="h-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500 transition-all duration-200" style="width: 100%;"></div>
            </div>

            <!-- Country Question -->
            <div class="text-center py-2">
                <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider mb-1">Apa ibu kota dari negara:</p>
                <h2 id="playerCountryName" class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Jepang
                </h2>
            </div>

            <!-- 4 Multiple Choice Options (Touch Friendly) -->
            <div id="playerOptionsGrid" class="grid grid-cols-1 gap-2.5">
                <!-- Dynamically populated options -->
            </div>

            <!-- Answer Feedback Banner -->
            <div id="answerFeedbackBanner" class="hidden p-3.5 rounded-xl text-center text-xs font-bold transition-all">
                <!-- Filled dynamically -->
            </div>
        </div>

        <!-- Mini Live Leaderboard -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between mb-3 text-xs font-bold text-slate-400 uppercase tracking-wider">
                <span>Peringkat Sementara</span>
                <span id="myRankBadge" class="text-indigo-400">Peringkat: -</span>
            </div>
            <div id="playerMiniLeaderboard" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- 3. ENDED STATE (Final Results) -->
    <div id="playerEndedSection" class="hidden p-6 rounded-2xl bg-slate-900 border border-slate-800 text-center space-y-6 shadow-2xl">
        <div class="inline-flex p-4 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-4xl shadow-inner">
            🏆
        </div>
        <div>
            <h2 class="text-2xl font-black text-white">Pertandingan Selesai!</h2>
            <p id="playerFinalRankSubtitle" class="text-sm font-semibold text-amber-300 mt-1">Kamu meraih Juara 1!</p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
            <span class="text-xs text-slate-400">Total Skor Akhir</span>
            <div id="playerFinalScoreDisplay" class="font-mono text-4xl font-black text-amber-400">0</div>
        </div>

        <div class="text-left space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Papan Peringkat Akhir</h4>
            <div id="playerFinalLeaderboardList" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <!-- Dynamically populated -->
            </div>
        </div>

        <div class="pt-2">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition-colors">
                ← Kembali ke Menu Utama
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roomCode = '{{ $room->code }}';
        let playerToken = localStorage.getItem('player_token_' + roomCode);
        let playerName = localStorage.getItem('player_name') || 'Pemain';

        // Check if player has token or needs to register/reconnect
        if (!playerToken) {
            window.location.href = `/?code=${roomCode}`;
            return;
        }

        const playerNameLabel = document.getElementById('playerNameLabel');
        playerNameLabel.textContent = playerName;

        const playerScoreDisplay = document.getElementById('playerScoreDisplay');
        const kickedAlert = document.getElementById('kickedAlert');
        const playerLobbySection = document.getElementById('playerLobbySection');
        const playerPlayingSection = document.getElementById('playerPlayingSection');
        const playerEndedSection = document.getElementById('playerEndedSection');
        const lobbyPlayersContainer = document.getElementById('lobbyPlayersContainer');

        const playerQuestionOrder = document.getElementById('playerQuestionOrder');
        const playerTimer = document.getElementById('playerTimer');
        const playerTimerBar = document.getElementById('playerTimerBar');
        const playerCountryName = document.getElementById('playerCountryName');
        const playerOptionsGrid = document.getElementById('playerOptionsGrid');
        const answerFeedbackBanner = document.getElementById('answerFeedbackBanner');
        const playerMiniLeaderboard = document.getElementById('playerMiniLeaderboard');
        const myRankBadge = document.getElementById('myRankBadge');

        const playerFinalRankSubtitle = document.getElementById('playerFinalRankSubtitle');
        const playerFinalScoreDisplay = document.getElementById('playerFinalScoreDisplay');
        const playerFinalLeaderboardList = document.getElementById('playerFinalLeaderboardList');

        let currentQuestionOrder = null;
        let selectedOption = null;
        let hasAnsweredCurrentQuestion = false;

        function updateUI(state) {
            if (state.is_kicked) {
                kickedAlert.classList.remove('hidden');
                playerLobbySection.classList.add('hidden');
                playerPlayingSection.classList.add('hidden');
                playerEndedSection.classList.add('hidden');
                return;
            }

            // Find my own player data from leaderboard or answer
            const myData = (state.leaderboard || []).find(p => p.name === playerName);
            if (myData) {
                playerScoreDisplay.textContent = myData.score;
                myRankBadge.textContent = `Peringkat: #${myData.rank}`;
            }

            if (state.status === 'lobby') {
                playerLobbySection.classList.remove('hidden');
                playerPlayingSection.classList.add('hidden');
                playerEndedSection.classList.add('hidden');

                const players = state.lobby_players || [];
                lobbyPlayersContainer.innerHTML = players.map(p => `
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${
                        p.name === playerName 
                            ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40' 
                            : 'bg-slate-900 text-slate-300 border border-slate-800'
                    }">
                        <span class="w-1.5 h-1.5 rounded-full ${p.is_connected ? 'bg-emerald-400' : 'bg-slate-500'}"></span>
                        ${escapeHtml(p.name)} ${p.name === playerName ? '(Kamu)' : ''}
                    </span>
                `).join('');
            } else if (state.status === 'playing') {
                playerLobbySection.classList.add('hidden');
                playerPlayingSection.classList.remove('hidden');
                playerEndedSection.classList.add('hidden');

                if (state.question) {
                    const q = state.question;
                    playerQuestionOrder.textContent = `SOAL ${String(q.order).padStart(2, '0')} / ${String(q.total).padStart(2, '0')}`;
                    playerCountryName.textContent = q.country_name;

                    // Timer
                    const rem = Math.max(0, q.time_remaining);
                    playerTimer.textContent = rem.toFixed(1);
                    const pct = Math.max(0, Math.min(100, (rem / q.duration) * 100));
                    playerTimerBar.style.width = pct + '%';

                    if (rem <= 3) {
                        playerTimer.className = 'font-mono text-2xl font-black text-rose-400 animate-pulse';
                    } else {
                        playerTimer.className = 'font-mono text-2xl font-black text-amber-400';
                    }

                    // Reset question state on order change
                    if (currentQuestionOrder !== q.order) {
                        currentQuestionOrder = q.order;
                        selectedOption = null;
                        hasAnsweredCurrentQuestion = false;
                        answerFeedbackBanner.classList.add('hidden');
                    }

                    // Check server player answer
                    if (state.player_answer && state.player_answer.has_answered) {
                        hasAnsweredCurrentQuestion = true;
                        selectedOption = state.player_answer.selected_answer;
                    }

                    // Render 4 options
                    renderOptions(q, state.player_answer);
                }

                renderMiniLeaderboard(state.leaderboard || []);
            } else if (state.status === 'ended') {
                playerLobbySection.classList.add('hidden');
                playerPlayingSection.classList.add('hidden');
                playerEndedSection.classList.remove('hidden');

                if (myData) {
                    playerFinalScoreDisplay.textContent = myData.score;
                    if (myData.rank === 1) {
                        playerFinalRankSubtitle.textContent = '🎉 SELAMAT! Kamu Menjadi Juara 1! 🏆';
                    } else {
                        playerFinalRankSubtitle.textContent = `Kamu berhasil meraih Peringkat #${myData.rank}!`;
                    }
                }

                renderFinalList(state.leaderboard || []);
            }
        }

        function renderOptions(question, playerAnswer) {
            const isTimeUp = question.is_time_up;
            const correctCapital = question.correct_capital; // only available when time is up or match ended

            playerOptionsGrid.innerHTML = question.options.map((opt, idx) => {
                const letter = String.fromCharCode(65 + idx);
                const isSelected = selectedOption === opt;
                let btnClass = 'bg-slate-950 border-slate-800 text-slate-200 hover:border-indigo-500/50 hover:bg-slate-900';
                let icon = '';

                if (isTimeUp && correctCapital) {
                    if (opt === correctCapital) {
                        btnClass = 'bg-emerald-600/20 border-emerald-500 text-emerald-300 font-bold';
                        icon = '✅';
                    } else if (isSelected) {
                        btnClass = 'bg-rose-600/20 border-rose-500 text-rose-300 font-bold';
                        icon = '❌';
                    }
                } else if (isSelected) {
                    btnClass = 'bg-indigo-600/30 border-indigo-500 text-indigo-300 font-bold ring-2 ring-indigo-500/40';
                    icon = '🔒';
                }

                const disabledAttr = (hasAnsweredCurrentQuestion || isTimeUp) ? 'disabled' : '';

                return `
                    <button type="button" ${disabledAttr} onclick="selectAnswer('${escapeHtml(opt)}')"
                        class="w-full p-4 rounded-xl border text-left flex items-center justify-between text-sm sm:text-base font-semibold transition-all active:scale-[0.99] disabled:cursor-not-allowed ${btnClass}">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-xs font-bold text-slate-300">${letter}</span>
                            <span>${escapeHtml(opt)}</span>
                        </div>
                        <span>${icon}</span>
                    </button>
                `;
            }).join('');

            // Feedback banner update
            if (isTimeUp) {
                if (playerAnswer && playerAnswer.has_answered) {
                    if (playerAnswer.is_correct) {
                        answerFeedbackBanner.className = 'p-3.5 rounded-xl text-center text-xs font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 block';
                        answerFeedbackBanner.innerHTML = `🎉 BENAR! +${playerAnswer.score_awarded} Poin diperoleh!`;
                    } else {
                        answerFeedbackBanner.className = 'p-3.5 rounded-xl text-center text-xs font-bold bg-rose-500/10 border border-rose-500/30 text-rose-300 block';
                        answerFeedbackBanner.innerHTML = `❌ KURANG TEPAT! Jawaban benar: <span class="font-black">${escapeHtml(correctCapital)}</span> (0 Poin)`;
                    }
                } else {
                    answerFeedbackBanner.className = 'p-3.5 rounded-xl text-center text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-300 block';
                    answerFeedbackBanner.innerHTML = `⏰ WAKTU HABIS! Jawaban benar: <span class="font-black">${escapeHtml(correctCapital)}</span> (0 Poin)`;
                }
            } else if (hasAnsweredCurrentQuestion) {
                answerFeedbackBanner.className = 'p-3.5 rounded-xl text-center text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 block';
                answerFeedbackBanner.textContent = '🔒 Jawaban terkunci! Menunggu waktu soal berakhir...';
            }
        }

        function renderMiniLeaderboard(leaderboard) {
            playerMiniLeaderboard.innerHTML = leaderboard.map(p => `
                <div class="flex items-center justify-between p-2 rounded-lg ${p.name === playerName ? 'bg-indigo-950/60 border border-indigo-500/30' : 'bg-slate-950/40'} text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-slate-500 w-4 text-center">#${p.rank}</span>
                        <span class="font-semibold ${p.name === playerName ? 'text-indigo-300 font-bold' : 'text-slate-300'}">${escapeHtml(p.name)}</span>
                    </div>
                    <span class="font-mono font-bold text-amber-400">${p.score} pts</span>
                </div>
            `).join('');
        }

        function renderFinalList(leaderboard) {
            playerFinalLeaderboardList.innerHTML = leaderboard.map(p => `
                <div class="p-3 rounded-xl border flex items-center justify-between text-xs ${
                    p.name === playerName ? 'bg-indigo-950/60 border-indigo-500/50' : 'bg-slate-950 border-slate-800'
                }">
                    <div class="flex items-center gap-2.5">
                        <span class="font-bold text-amber-400 w-5">#${p.rank}</span>
                        <span class="font-bold text-white">${escapeHtml(p.name)} ${p.name === playerName ? '(Kamu)' : ''}</span>
                    </div>
                    <div class="font-mono font-bold text-amber-400 text-sm">
                        ${p.score} pts
                    </div>
                </div>
            `).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.selectAnswer = async function(capital) {
            if (hasAnsweredCurrentQuestion || !currentQuestionOrder) return;

            selectedOption = capital;
            hasAnsweredCurrentQuestion = true;

            // Immediately disable buttons and lock UI locally
            const buttons = playerOptionsGrid.querySelectorAll('button');
            buttons.forEach(b => b.disabled = true);
            answerFeedbackBanner.className = 'p-3.5 rounded-xl text-center text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 block';
            answerFeedbackBanner.textContent = '🔒 Jawaban terkunci! Mengirim ke server...';

            try {
                const response = await fetch(`/api/rooms/${roomCode}/answer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        player_token: playerToken,
                        question_order: currentQuestionOrder,
                        selected_capital: capital,
                    })
                });

                const data = await response.json();
                if (data.success) {
                    playerScoreDisplay.textContent = data.current_score;
                    answerFeedbackBanner.textContent = '🔒 Jawaban terkunci! Menunggu waktu soal berakhir...';
                }
            } catch (err) {
                console.error('Submit answer error', err);
            }
        };

        // Real-Time SSE + Polling fallback
        let eventSource = null;
        function connectEvents() {
            if (eventSource) eventSource.close();
            eventSource = new EventSource(`/api/rooms/${roomCode}/events?player_token=${encodeURIComponent(playerToken)}`);
            eventSource.onmessage = (e) => {
                try {
                    const data = JSON.parse(e.data);
                    updateUI(data);
                } catch (err) {
                    console.error('SSE JSON parse error', err);
                }
            };
            eventSource.onerror = () => {
                fetchState();
            };
        }

        async function fetchState() {
            try {
                const res = await fetch(`/api/rooms/${roomCode}/state?player_token=${encodeURIComponent(playerToken)}`);
                if (res.ok) {
                    const data = await res.json();
                    updateUI(data);
                }
            } catch (err) {
                console.error('Fetch state error', err);
            }
        }

        fetchState();
        connectEvents();
        setInterval(fetchState, 1500); // 1.5s background polling fallback
    });
</script>
@endsection
