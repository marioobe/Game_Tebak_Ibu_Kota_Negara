@extends('layouts.app')

@section('title', 'Tebak Ibu Kota Negara - Masuk')

@section('content')
<div class="max-w-md mx-auto w-full my-auto py-6">
    <!-- Header Hero -->
    <div class="text-center mb-8">
        <div class="inline-flex p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-3xl mb-3 shadow-inner">
            🏛️
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Tebak Ibu Kota Negara
        </h2>
        <p class="text-sm text-slate-400 mt-1.5">
            Kompetisi kuis cepat & seru lewat jaringan lokal (Wi-Fi/LAN)
        </p>
    </div>

    <!-- Card Masuk Permainan -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-2xl shadow-black/40 mb-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>🎮</span> Gabung Permainan
        </h3>

        <form id="joinForm" class="space-y-4">
            <div>
                <label for="roomCode" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Kode Ruang (5 Huruf)
                </label>
                <input type="text" id="roomCode" name="code" maxlength="5" placeholder="Contoh: 9X7AB" required
                    class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-center text-xl font-mono uppercase tracking-widest text-indigo-400 placeholder:text-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label for="playerName" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Nama Kamu
                </label>
                <input type="text" id="playerName" name="name" maxlength="20" placeholder="Masukkan nama panggilan..." required
                    class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder:text-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div id="errorMessage" class="hidden p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium"></div>

            <button type="submit" id="joinBtn"
                class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 active:scale-[0.99] rounded-xl font-bold text-white shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2">
                <span>Gabung ke Lobi</span>
                <span class="text-lg">➔</span>
            </button>
        </form>
    </div>

    <!-- Host Option -->
    <div class="text-center">
        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-800"></div>
            <span class="flex-shrink mx-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Penyelenggara</span>
            <div class="flex-grow border-t border-slate-800"></div>
        </div>

        <a href="{{ route('host.setup') }}"
            class="mt-3 inline-flex items-center justify-center gap-2 w-full py-3 px-4 bg-slate-900 hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-200 transition-all">
            <span>👑</span> Buat Ruang Baru (Sebagai Host)
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const joinForm = document.getElementById('joinForm');
        const codeInput = document.getElementById('roomCode');
        const nameInput = document.getElementById('playerName');
        const joinBtn = document.getElementById('joinBtn');
        const errorDiv = document.getElementById('errorMessage');

        // Check if code is in URL param (?code=...)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('code')) {
            codeInput.value = urlParams.get('code').toUpperCase();
        }

        // Restore saved player name if available
        const savedName = localStorage.getItem('player_name');
        if (savedName) {
            nameInput.value = savedName;
        }

        joinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorDiv.classList.add('hidden');
            errorDiv.textContent = '';

            const code = codeInput.value.trim().toUpperCase();
            const name = nameInput.value.trim();

            if (!code || !name) {
                errorDiv.textContent = 'Kode ruang dan nama tidak boleh kosong.';
                errorDiv.classList.remove('hidden');
                return;
            }

            joinBtn.disabled = true;
            joinBtn.innerHTML = '<span class="animate-spin text-lg">⏳</span> Memeriksa ruang...';

            // Check if player has token for this room
            const playerToken = localStorage.getItem('player_token_' + code);

            try {
                const response = await fetch(`/api/rooms/${code}/join`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: name,
                        player_token: playerToken,
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal bergabung ke ruang.');
                }

                // Save tokens
                localStorage.setItem('player_name', name);
                localStorage.setItem('player_token_' + code, data.player_token);

                // Redirect to player game view
                window.location.href = `/play/${code}`;
            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('hidden');
                joinBtn.disabled = false;
                joinBtn.innerHTML = '<span>Gabung ke Lobi</span><span class="text-lg">➔</span>';
            }
        });
    });
</script>
@endsection
