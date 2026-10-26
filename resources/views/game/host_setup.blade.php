@extends('layouts.app')

@section('title', 'Tebak Ibu Kota - Buat Ruang Pertandingan')

@section('content')
<div class="max-w-md mx-auto w-full my-auto py-6">
    <div class="text-center mb-8">
        <div class="inline-flex p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-3xl mb-3 shadow-inner">
            👑
        </div>
        <h2 class="text-2xl font-extrabold text-white tracking-tight">
            Buat Ruang Pertandingan
        </h2>
        <p class="text-sm text-slate-400 mt-1">
            Atur parameter permainan sebelum membagikan kode ke pemain
        </p>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-2xl shadow-black/40 space-y-6">
        <!-- Pilihan Jumlah Soal (PRD: 10, 20, 30) -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                Pilih Jumlah Soal
            </label>
            <div class="grid grid-cols-3 gap-3" id="countSelector">
                <button type="button" data-count="10"
                    class="count-btn py-3 px-2 rounded-xl border border-indigo-500 bg-indigo-600/20 text-white font-bold text-center transition-all shadow-md shadow-indigo-600/20">
                    <span class="block text-xl">10</span>
                    <span class="text-[10px] text-indigo-300 uppercase tracking-wider">Cepat (±3m)</span>
                </button>
                <button type="button" data-count="20"
                    class="count-btn py-3 px-2 rounded-xl border border-slate-800 bg-slate-950 text-slate-300 font-bold text-center hover:border-slate-700 transition-all">
                    <span class="block text-xl">20</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Standar (±6m)</span>
                </button>
                <button type="button" data-count="30"
                    class="count-btn py-3 px-2 rounded-xl border border-slate-800 bg-slate-950 text-slate-300 font-bold text-center hover:border-slate-700 transition-all">
                    <span class="block text-xl">30</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Maraton (±10m)</span>
                </button>
            </div>
        </div>

        <!-- Info Jaringan Lokal (LAN) -->
        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Jaringan Host (Wi-Fi/LAN)
                </span>
                <span class="text-[10px] text-slate-500 font-mono">Port :8000</span>
            </div>
            
            <p class="text-xs text-slate-400">
                Alamat IP laptop Anda untuk diakses pemain di HP:
            </p>
            <div class="space-y-1 font-mono text-xs text-indigo-300">
                @foreach($localIps as $ip)
                    <div class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-between">
                        <span>http://{{ $ip }}:8000</span>
                        <span class="text-[10px] text-slate-500">Wi-Fi/LAN</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div id="setupError" class="hidden p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium"></div>

        <button type="button" id="createRoomBtn"
            class="w-full py-3.5 px-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 active:scale-[0.99] rounded-xl font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-2">
            <span>Buka Ruang & Dapatkan Kode</span>
            <span class="text-lg">✨</span>
        </button>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('home') }}" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">
            ← Kembali ke Menu Utama
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let selectedCount = 10;
        const countButtons = document.querySelectorAll('.count-btn');
        const createBtn = document.getElementById('createRoomBtn');
        const errorDiv = document.getElementById('setupError');

        countButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                countButtons.forEach(b => {
                    b.className = 'count-btn py-3 px-2 rounded-xl border border-slate-800 bg-slate-950 text-slate-300 font-bold text-center hover:border-slate-700 transition-all';
                    b.querySelector('span:last-child').className = 'text-[10px] text-slate-500 uppercase tracking-wider';
                });
                btn.className = 'count-btn py-3 px-2 rounded-xl border border-indigo-500 bg-indigo-600/20 text-white font-bold text-center transition-all shadow-md shadow-indigo-600/20';
                btn.querySelector('span:last-child').className = 'text-[10px] text-indigo-300 uppercase tracking-wider';
                selectedCount = parseInt(btn.dataset.count);
            });
        });

        createBtn.addEventListener('click', async () => {
            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="animate-spin text-lg">⏳</span> Membuat ruang...';
            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('/api/rooms', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ question_count: selectedCount })
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal membuat ruang permainan.');
                }

                // Save host token in localStorage
                localStorage.setItem('host_token_' + data.code, data.host_token);

                // Redirect to host view
                window.location.href = data.redirect_url;
            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('hidden');
                createBtn.disabled = false;
                createBtn.innerHTML = '<span>Buka Ruang & Dapatkan Kode</span><span class="text-lg">✨</span>';
            }
        });
    });
</script>
@endsection
