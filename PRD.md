PRD — Game Tebak Ibu Kota Negara (Offline Multiplayer)
Versi 1.0 · 26 Oktober 2026 · Status: Draf kebutuhan produk

PRD ini disusun berdasarkan seluruh keputusan yang sudah kamu pilih. Tujuannya adalah menjadi dokumen acuan untuk merancang, membangun, dan menguji game tebak ibu kota negara berbasis web yang dapat dimainkan bersama secara offline melalui jaringan lokal (LAN).
1. Masalah
Pembelajaran geografi dan pengetahuan tentang ibu kota negara dapat terasa monoton jika hanya dilakukan melalui hafalan. Dibutuhkan media permainan yang memungkinkan beberapa orang menguji pengetahuan mereka dalam suasana kompetitif, interaktif, dan menyenangkan.

Game kuis daring pada umumnya bergantung pada koneksi internet atau layanan server eksternal. Kondisi tersebut menjadi kendala ketika permainan dilakukan di kelas, ruang belajar, atau tempat berkumpul yang tidak memiliki akses internet.

Produk ini bertujuan menyediakan game kuis berbasis web yang dapat dijalankan sepenuhnya melalui jaringan lokal, dengan laptop Windows sebagai server dan perangkat pemain sebagai klien.

Masalah yang hendak diselesaikan:
- Pemain membutuhkan permainan tebak ibu kota yang mudah dipahami dan langsung dimainkan melalui browser.
- Permainan membutuhkan mekanisme kompetisi yang adil berdasarkan ketepatan dan kecepatan menjawab.
- Host membutuhkan kendali atas ruang permainan dan jalannya pertandingan.
- Skor dan peringkat harus tersinkron antarperangkat tanpa bergantung pada internet.
- Pemain yang terputus sementara perlu dapat bergabung kembali tanpa kehilangan skor sebelumnya.
2. Pengguna

Host / Penyelenggara

Laptop Windows

Membuat ruang, membagikan kode, menentukan jumlah soal, memulai dan mengakhiri pertandingan, serta mengelola peserta.




Pemain

HP atau laptop melalui browser

Bergabung menggunakan kode ruang dan nama, menjawab soal, melihat timer, memperoleh skor, dan memantau leaderboard.



Ketentuan pengguna:
- Minimal satu pemain; game dapat diuji sendiri.
- Tidak memerlukan akun, registrasi, atau kata sandi pemain.
- Pemain bergabung melalui browser dan jaringan Wi-Fi/LAN yang sama dengan host.
- Batas maksimum pemain belum ditentukan dan perlu disesuaikan dengan hasil pengujian.
3. Fitur
3.1. Ruang permainan dan lobi
- Host dapat membuat ruang permainan dan memperoleh kode ruang unik.
- Pemain memasukkan kode ruang dan nama untuk bergabung.
- Lobi menampilkan daftar pemain yang sudah bergabung.
- Host dapat mengeluarkan pemain sebelum atau selama pertandingan.
- Host dapat mengatur jumlah soal: 10, 20, atau 30.
- Host dapat memulai pertandingan ketika siap, termasuk saat hanya ada satu pemain.
- Pemain baru tidak dapat masuk ke pertandingan yang sudah berjalan, kecuali pemain lama yang memenuhi aturan bergabung kembali.
3.2. Dataset negara dan ibu kota
- Aplikasi menyediakan dataset negara dan ibu kota yang tersedia secara lokal.
- Game tidak memerlukan API eksternal atau internet untuk mengambil pertanyaan.
- Setiap pertanyaan mempunyai satu jawaban benar dan empat opsi jawaban secara keseluruhan.
- Tiga opsi pengecoh harus berbeda dari jawaban benar dan satu sama lain.
- Soal dipilih secara acak dari dataset yang memenuhi syarat.
- Dalam satu pertandingan, negara yang sama tidak boleh muncul dua kali selama jumlah soal yang diminta tidak melebihi jumlah soal unik yang tersedia.
- Data harus memiliki sumber dan proses validasi yang jelas, termasuk penanganan negara dengan status ibu kota khusus atau lebih dari satu pusat pemerintahan.
Catatan cakupan: istilah “semua negara di dunia” perlu diterjemahkan menjadi dataset yang eksplisit. Untuk versi pertama, daftar negara berdaulat dan penetapan ibu kotanya harus didefinisikan dan dibekukan sebagai bagian dari data aplikasi. Wilayah dependensi dan wilayah dengan status sengketa tidak otomatis dimasukkan.
3.3. Tampilan pertanyaan
SOAL 04 / 10
15 detik

TEBAK IBU KOTA
Apa ibu kota Jepang?
Pilih satu jawaban

A. Tokyo


B. Kyoto


C. Osaka


D. Nagoya



Ilustrasi tampilan soal, bukan sesi permainan aktif.

Ketentuan:
- Pertanyaan dan empat pilihan jawaban ditampilkan pada perangkat setiap pemain.
- Timer berdurasi 15 detik per soal.
- Timer berasal dari waktu pertandingan yang dikelola server, bukan jam perangkat pemain.
- Jawaban langsung dikunci setelah pemain memilih.
- Jawaban yang telah dikirim tidak dapat diubah.
- Pemain yang tidak menjawab sampai waktu habis memperoleh nol poin.
- Setelah menjawab, pemain menunggu hingga soal berikutnya dimulai sesuai alur pertandingan.
3.4. Sistem skor
Setiap jawaban benar mendapatkan skor maksimum 100 poin, berkurang secara linear berdasarkan waktu menjawab.

Tetapkan:
- \(T = 15\) detik sebagai batas waktu.
- \(t\) sebagai waktu yang telah terpakai saat server menerima jawaban yang valid.
Rumus skor:
\[
S =
\begin{cases}
0, & \text{jika salah atau waktu habis}\\
\left\lceil100\left(\frac{15-t}{15}\right)\right\rceil,
& \text{jika benar}
\end{cases}
\]

Skor dibatasi pada rentang 0–100 poin. Pembulatan dilakukan ke atas ke bilangan bulat terdekat.
Waktu menjawab	Skor jika benar
1 detik	94 poin
3 detik	80 poin
6 detik	60 poin
9 detik	40 poin
12 detik	20 poin
15 detik	0 poin

Catatan: contoh tersebut menggunakan waktu terpakai \(t\) dalam detik. Pada implementasi, timer perlu memakai presisi waktu yang lebih tinggi agar jawaban yang masuk tepat sebelum tenggat dinilai secara konsisten. Jawaban yang diterima setelah tenggat memperoleh nol poin.

Skor dan waktu penerimaan jawaban dihitung oleh server lokal. Klien tidak boleh mengirimkan nilai skor yang bisa menentukan hasil secara langsung.
3.5. Leaderboard
- Leaderboard diperbarui ketika jawaban pemain dinilai.
- Seluruh pemain melihat data peringkat yang konsisten.
- Leaderboard menampilkan peringkat, nama pemain, dan total skor.
- Peringkat dihitung berdasarkan total skor tertinggi.
- Jika skor seri, gunakan jumlah jawaban benar terbanyak sebagai pemecah seri; jika masih seri, gunakan total waktu menjawab paling singkat. Jika masih sama, pemain berbagi peringkat.
- Setelah pertandingan selesai, tampilkan leaderboard akhir, total skor, dan pemenang.
- Sediakan opsi kembali ke lobi.
- Skor dan leaderboard hanya berlaku selama pertandingan berlangsung dan tidak disimpan sebagai riwayat permanen setelah ruang ditutup.
3.6. Penanganan koneksi
- Jika koneksi pemain terputus, server tetap mempertahankan data skor dan status pertandingan.
- Pemain dapat bergabung kembali menggunakan kode ruang dan nama yang sama selama pertandingan masih berlangsung.
- Server memulihkan skor dan status pemain sebelumnya.
- Jawaban yang sudah terkunci tidak boleh dikirim ulang untuk mendapatkan poin tambahan.
- Jika pemain terputus saat soal berlangsung, tenggat waktu soal tetap mengikuti server; waktu tidak diulang ketika pemain kembali.
- Host dapat mengakhiri pertandingan lebih awal. Sistem kemudian menampilkan leaderboard akhir berdasarkan skor yang sudah tercatat.
3.7. Desain antarmuka
Gaya visual yang dipilih adalah modern dan kompetitif.

Elemen utama:
- Kartu pertanyaan yang mudah dibaca.
- Empat tombol pilihan jawaban berukuran nyaman untuk layar sentuh.
- Timer yang terlihat jelas.
- Indikator progres soal.
- Umpan balik visual setelah jawaban dipilih.
- Animasi ringan untuk perubahan skor dan leaderboard.
- Tampilan lobi dan hasil akhir yang mudah dipahami.
- Desain responsif untuk browser HP dan laptop.
Animasi harus ringan dan tidak menghambat respons tombol atau sinkronisasi pertandingan.
3.8. Arsitektur dan teknologi
Laptop Windows — Host

Laravel · PHP 8.5 · Server pertandingan

Wi-Fi / LAN lokal · Tanpa internet

Pemain HP

Browser responsif


Pemain laptop

Browser responsif




Teknologi yang ditetapkan:
Komponen	Ketentuan
Sistem operasi host	Windows
Lingkungan pengembangan	Laravel Herd
Backend	Laravel / PHP 8.5
Database	SQLite
Runtime JavaScript	Node.js 24
Package manager	npm
Frontend	HTML, CSS, JavaScript, dengan aset lokal
Komunikasi real-time	WebSocket atau mekanisme push lokal yang kompatibel
Domain pengembangan	Domain .test melalui Herd
Akses pemain	IP lokal laptop host beserta port server
Ketergantungan internet saat bermain	Tidak ada

Ketentuan teknis penting: penggunaan Laravel Herd dan domain .test untuk pengembangan tidak otomatis membuat aplikasi tersedia di jaringan LAN. Implementasi wajib menyediakan cara menjalankan endpoint aplikasi yang dapat diakses melalui IP lokal, menangani firewall Windows, dan memastikan koneksi real-time tidak bergantung pada layanan eksternal. Mekanisme tersebut harus diuji pada perangkat klien nyata.

SQLite menyimpan data lokal yang diperlukan aplikasi. Dataset negara dan ibu kota disediakan bersama aplikasi atau diimpor ke penyimpanan lokal sebelum permainan dimulai. Tidak diperlukan akun pemain atau penyimpanan riwayat pertandingan permanen.
4. Di luar cakupan
Fitur berikut tidak termasuk dalam versi pertama:
- Login, registrasi, dan pengelolaan akun pemain.
- Leaderboard global atau leaderboard lintas pertandingan.
- Penyimpanan permanen riwayat pertandingan.
- Server cloud atau hosting publik.
- Multiplayer melalui internet atau lintas jaringan tanpa konfigurasi tambahan.
- Integrasi API eksternal untuk mengambil pertanyaan saat bermain.
- Website admin untuk CRUD dataset negara dan ibu kota.
- Mode buzzer, mode nyawa, dan jawaban berbentuk teks.
- Sistem liga, turnamen bertingkat, hadiah, atau pencapaian.
- Chat antarpemain dan komunikasi suara.
- Fitur mengganti jawaban setelah pilihan dikunci.
- Dukungan bahasa antarmuka selain bahasa Indonesia pada versi pertama.
- Sistem analitik pengguna atau pelacakan aktivitas lintas sesi.
5. Batasan
1. Jaringan: laptop host dan perangkat pemain harus berada pada jaringan lokal yang memungkinkan komunikasi antardevice. Isolasi klien pada Wi-Fi dapat menghalangi koneksi.
2. Internet: tidak dibutuhkan ketika bermain, tetapi instalasi dependensi, pemasangan aplikasi, atau pembaruan awal mungkin membutuhkan internet.
3. Server: pertandingan bergantung pada laptop host. Jika server berhenti, pertandingan tidak dapat dilanjutkan sampai layanan dipulihkan.
4. Sinkronisasi: server menjadi sumber kebenaran untuk waktu, jawaban, skor, status ruang, dan leaderboard.
5. Kapasitas: batas maksimum pemain belum ditentukan. Target kapasitas perlu diuji sebelum rilis.
6. Dataset: cakupan negara, ejaan ibu kota, status politik, dan kasus ibu kota khusus harus ditetapkan secara eksplisit.
7. Keadilan: waktu respons jaringan dapat berbeda antarperangkat. Penilaian menggunakan waktu penerimaan jawaban di server, sehingga latensi jaringan merupakan faktor yang perlu diperhatikan.
8. Keamanan ruang: kode ruang bukan mekanisme autentikasi yang kuat. Host perlu memiliki kendali untuk mengeluarkan peserta.
9. Kompatibilitas: browser modern pada HP dan laptop menjadi target. Dukungan browser lama tidak dijamin.
10. Domain: domain .test hanya digunakan untuk pengembangan lokal; perangkat pemain menggunakan alamat IP LAN.
11. Penyimpanan: tidak ada jaminan pemulihan pertandingan setelah server ditutup atau laptop host mati. Penyimpanan sementara dan perilaku pemulihan setelah gangguan host perlu dibedakan dari pemulihan koneksi pemain.
12. Dependensi teknis: tidak boleh menggunakan XAMPP, Docker, atau MySQL. Implementasi harus sesuai dengan Windows, Laravel Herd, PHP 8.5, SQLite, Node.js 24, dan npm.
6. Kriteria permintaan
Kriteria berikut merupakan syarat penerimaan produk. Fitur dianggap selesai apabila perilakunya dapat dibuktikan melalui pengujian.
Checklist pengujian penerimaan
0/16

Checklist ini bisa digunakan saat pengujian. Centang setelah kriteria benar-benar berhasil diuji, bukan hanya setelah kode selesai dibuat.
[ ] 1. Menjalankan aplikasi offline

Aplikasi dapat dijalankan di laptop Windows menggunakan stack yang ditetapkan tanpa membutuhkan layanan internet saat pertandingan berlangsung.


[ ] 2. Akses melalui LAN

Perangkat lain pada jaringan yang sama dapat membuka aplikasi melalui IP lokal laptop host dan port yang disediakan.


[ ] 3. Membuat dan bergabung ke ruang

Host memperoleh kode ruang, dan pemain dapat bergabung dengan nama melalui browser.


[ ] 4. Validasi jumlah soal

Host dapat memilih 10, 20, atau 30 soal; pertandingan tidak dimulai jika jumlah soal unik yang tersedia tidak mencukupi.


[ ] 5. Dataset dan opsi jawaban

Setiap soal memiliki satu jawaban benar dan tiga pengecoh yang unik. Seluruh opsi ditampilkan tanpa bergantung pada internet.


[ ] 6. Timer 15 detik

Setiap soal memiliki batas 15 detik berdasarkan waktu server. Jawaban setelah tenggat ditolak atau dinilai nol sesuai aturan.


[ ] 7. Jawaban terkunci

Setiap pemain hanya dapat mengirim satu jawaban per soal. Percobaan mengubah atau mengirim ulang jawaban tidak menghasilkan poin tambahan.


[ ] 8. Skor linear

Jawaban benar memperoleh skor 0–100 sesuai rumus yang ditetapkan. Jawaban salah atau waktu habis memperoleh nol poin.


[ ] 9. Sinkronisasi pertandingan

Pertanyaan, timer, status jawaban, skor, dan leaderboard konsisten antarperangkat.


[ ] 10. Leaderboard

Peringkat dihitung dari total skor dan aturan pemecah seri yang ditentukan. Semua pemain melihat hasil yang sama.


[ ] 11. Host mengelola pertandingan

Host dapat memulai, mengakhiri pertandingan lebih awal, dan mengeluarkan pemain.


[ ] 12. Pemain bergabung kembali

Pemain yang terputus dapat bergabung kembali dengan kode ruang dan nama yang sama, lalu memperoleh kembali skor sebelumnya.


[ ] 13. Hasil akhir

Setelah semua soal selesai atau host mengakhiri pertandingan, hasil menampilkan leaderboard akhir, total skor, pemenang, dan opsi kembali ke lobi.


[ ] 14. Penyimpanan sementara

Skor dan peringkat tidak dipertahankan sebagai riwayat permanen setelah ruang pertandingan ditutup.


[ ] 15. Responsif

Tampilan dan tombol dapat digunakan pada browser HP maupun laptop tanpa elemen utama terpotong.


[ ] 16. Tidak ada ketergantungan eksternal saat bermain

Aset, dataset, dan mekanisme komunikasi yang diperlukan tersedia secara lokal; tidak ada permintaan wajib ke API internet.


 Salin checklist pengujian
Ringkasan keputusan produk
- Game kuis tebak ibu kota seluruh negara di dunia.
- Multiplayer offline melalui LAN, dengan laptop Windows sebagai host.
- Pemain menggunakan HP atau laptop melalui browser.
- Bergabung menggunakan kode ruang dan nama.
- Pilihan ganda empat opsi, satu jawaban per soal, langsung terkunci.
- Timer 15 detik dan skor linear maksimum 100 poin.
- Host mengatur 10, 20, atau 30 soal.
- Leaderboard real-time dan hasil akhir tanpa riwayat permanen.
- Pemain dapat bergabung kembali tanpa kehilangan skor selama pertandingan berlangsung.
- Stack: Laravel Herd, PHP 8.5, SQLite, Node.js 24, dan npm.