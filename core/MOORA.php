<?php
// ============================================================
// core/MOORA.php
// ENGINE UTAMA: Implementasi Metode MOORA
// (Multi-Objective Optimization on the Basis of Ratio Analysis)
//
// Untuk Skripsi: SMA Negeri 1 Mirit — Rekomendasi Peminatan Kelas 11
//
// ARSITEKTUR DUA SKOR TERPISAH:
// Sistem ini menghitung DUA skor berbeda per siswa:
//   - Skor IPA: menggunakan kolom C1, C2, C4_arah_IPA, C5_arah_IPA
//   - Skor IPS: menggunakan kolom C1, C3, C4_arah_IPS, C5_arah_IPS
//
// ALASAN C1 (Matematika) masuk ke KEDUA skor:
//   Matematika adalah fondasi akademik yang relevan untuk kedua jurusan.
//   Ini adalah keputusan desain yang eksplisit dan disengaja.
//
// ENCODING C4 & C5 (KRITIS — JANGAN DIUBAH TANPA MEMAHAMI INI):
//   C4 dan C5 berasal dari pilihan kategorikal (IPA/IPS).
//   Karena semua kriteria bersifat BENEFIT, kita encode berbeda per arah:
//     → Untuk Skor IPA : pilih IPA = 100, pilih IPS = 0
//     → Untuk Skor IPS : pilih IPS = 100, pilih IPA = 0
//   Ini memastikan skor IPA tinggi jika siswa/ortu prefer IPA,
//   dan skor IPS tinggi jika siswa/ortu prefer IPS.
//   JANGAN menggunakan satu kolom biner tunggal karena arahnya ambigu.
// ============================================================

class MOORA {

    // --------------------------------------------------------
    // KONSTANTA BOBOT (FIXED - sesuai kesepakatan penelitian)
    // Total bobot C1+C2+C4+C5 = 0.80 (untuk skor IPA)
    // Total bobot C1+C3+C4+C5 = 0.75 (untuk skor IPS)
    // Perbedaan total bobot ini valid dalam MOORA karena berbasis rasio vektor
    // --------------------------------------------------------
    public static function getBobot(): array {
        // Nilai default — digunakan jika tabel kriteria belum ada atau ada kode yang hilang
        $default = [
            'c1' => 0.35,
            'c2' => 0.25,
            'c3' => 0.20,
            'c4' => 0.15,
            'c5' => 0.05,
        ];

        $bobot = [];
        try {
            $kriteria = dbQuery('SELECT kode, bobot FROM kriteria');
            foreach ($kriteria as $k) {
                $bobot[$k['kode']] = (float) $k['bobot'];
            }
        } catch (PDOException $e) {
            // Abaikan error jika tabel belum ada (fallback ke default)
        }

        // Merge: kode yang ada di DB menimpa default, kode yang hilang tetap pakai default
        // Ini mencegah "Undefined index" pada c1–c5 meski tabel kriteria belum lengkap
        return array_merge($default, $bobot);
    }

    // Nilai encoding untuk pilihan kategorikal (JANGAN DIUBAH)
    const ENCODE_MATCH    = 100;  // Pilihan sesuai dengan arah yang dihitung
    const ENCODE_NEUTRAL  = 50;   // Pilihan ragu-ragu (netral ke kedua arah)
    const ENCODE_MISMATCH = 0;    // Pilihan tidak sesuai dengan arah yang dihitung

    // --------------------------------------------------------
    // FUNGSI ENCODING C4/C5
    // --------------------------------------------------------

    /**
     * Encode pilihan minat untuk PERHITUNGAN SKOR IPA.
     *
     * Logika: Jika siswa/ortu memilih IPA → nilai tinggi (100) → berkontribusi besar ke skor IPA
     *         Jika siswa/ortu memilih IPS → nilai rendah (0)   → tidak berkontribusi ke skor IPA
     *
     * @param string $pilihan 'IPA' atau 'IPS' (case insensitive)
     * @return int 100 atau 0
     */
    public static function encodeUntukIPA(string $pilihan): int {
        // Semakin cocok preferensi dengan IPA, semakin tinggi kontribusinya ke skor IPA
        $p = strtoupper(trim($pilihan));
        if ($p === 'IPA') return self::ENCODE_MATCH;
        if ($p === 'RAGU') return self::ENCODE_NEUTRAL;
        return self::ENCODE_MISMATCH;
    }

    /**
     * Encode pilihan minat untuk PERHITUNGAN SKOR IPS.
     *
     * Logika: Jika siswa/ortu memilih IPS → nilai tinggi (100) → berkontribusi besar ke skor IPS
     *         Jika siswa/ortu memilih IPA → nilai rendah (0)   → tidak berkontribusi ke skor IPS
     *
     * @param string $pilihan 'IPA' atau 'IPS' (case insensitive)
     * @return int 100 atau 0
     */
    public static function encodeUntukIPS(string $pilihan): int {
        // Kebalikan dari encodeUntukIPA — jangan sampai terbalik!
        $p = strtoupper(trim($pilihan));
        if ($p === 'IPS') return self::ENCODE_MATCH;
        if ($p === 'RAGU') return self::ENCODE_NEUTRAL;
        return self::ENCODE_MISMATCH;
    }

    // --------------------------------------------------------
    // FUNGSI UTAMA: HITUNG SEMUA SISWA
    // --------------------------------------------------------

    /**
     * Menghitung skor MOORA untuk seluruh data siswa.
     *
     * Langkah-langkah (sesuai teori MOORA):
     *   1. Bangun matriks keputusan (dua kelompok: IPA dan IPS)
     *   2. Normalisasi vektor: x*ij = xij / sqrt(Σ xij²) per kolom
     *   3. Pembobotan: y*ij = wj × x*ij
     *   4. Hitung skor akhir: Yi = Σ y*ij (semua benefit, tidak ada cost)
     *   5. Tentukan rekomendasi: IPA jika skor_ipa > skor_ips
     *   6. Hitung confidence score
     *
     * @param array $dataSiswa Array asosiatif dengan kunci:
     *                         'nis', 'nama', 'c1', 'c2', 'c3', 'c4', 'c5'
     *                         c4 dan c5 berisi string 'IPA' atau 'IPS'
     * @return array Hasil perhitungan lengkap beserta detail langkah per langkah
     */
    public static function hitung(array $dataSiswa): array {
        if (empty($dataSiswa)) {
            return [];
        }

        $n = count($dataSiswa);

        // ====================================================
        // LANGKAH 1: Bangun matriks keputusan (dua kelompok)
        // ====================================================

        // Kolom untuk Skor IPA: C1 (Matematika), C2 (Nilai IPA), C4_ipa, C5_ipa
        $kolomIPA = [
            'c1'     => [],   // Nilai Matematika (masuk kedua skor)
            'c2'     => [],   // Nilai IPA
            'c4_ipa' => [],   // Minat siswa → encoded untuk arah IPA
            'c5_ipa' => [],   // Minat ortu  → encoded untuk arah IPA
        ];

        // Kolom untuk Skor IPS: C1 (Matematika), C3 (Nilai IPS), C4_ips, C5_ips
        $kolomIPS = [
            'c1'     => [],   // Nilai Matematika (masuk kedua skor — keputusan desain eksplisit)
            'c3'     => [],   // Nilai IPS
            'c4_ips' => [],   // Minat siswa → encoded untuk arah IPS
            'c5_ips' => [],   // Minat ortu  → encoded untuk arah IPS
        ];

        // Bangun kolom-kolom dari data input
        foreach ($dataSiswa as $siswa) {
            // Validasi encoding C4 dan C5 sebelum digunakan
            $c4EncodedIPA = self::encodeUntukIPA($siswa['c4']);
            $c5EncodedIPA = self::encodeUntukIPA($siswa['c5']);
            $c4EncodedIPS = self::encodeUntukIPS($siswa['c4']);
            $c5EncodedIPS = self::encodeUntukIPS($siswa['c5']);

            // Kolom IPA
            $kolomIPA['c1'][]     = (float) $siswa['c1'];
            $kolomIPA['c2'][]     = (float) $siswa['c2'];
            $kolomIPA['c4_ipa'][] = $c4EncodedIPA;
            $kolomIPA['c5_ipa'][] = $c5EncodedIPA;

            // Kolom IPS
            $kolomIPS['c1'][]     = (float) $siswa['c1'];
            $kolomIPS['c3'][]     = (float) $siswa['c3'];
            $kolomIPS['c4_ips'][] = $c4EncodedIPS;
            $kolomIPS['c5_ips'][] = $c5EncodedIPS;
        }

        // ====================================================
        // LANGKAH 2: Hitung pembagi normalisasi (penyebut)
        // Rumus: pembagi_j = sqrt(Σ xij²) untuk setiap kolom j
        // ====================================================

        $pembaginya = self::hitungPembagi($kolomIPA, $kolomIPS);

        // ====================================================
        // LANGKAH 3: Normalisasi, pembobotan, dan skor akhir
        // ====================================================

        $dbBobot = self::getBobot();

        $bobotIPA = [
            'c1'     => $dbBobot['c1'],
            'c2'     => $dbBobot['c2'],
            'c4_ipa' => $dbBobot['c4'],
            'c5_ipa' => $dbBobot['c5'],
        ];

        $bobotIPS = [
            'c1'     => $dbBobot['c1'],
            'c3'     => $dbBobot['c3'],
            'c4_ips' => $dbBobot['c4'],
            'c5_ips' => $dbBobot['c5'],
        ];

        $hasilSementara = [];

        foreach ($dataSiswa as $i => $siswa) {
            // --- Ambil nilai mentah ---
            $c4EncIPA = self::encodeUntukIPA($siswa['c4']);
            $c5EncIPA = self::encodeUntukIPA($siswa['c5']);
            $c4EncIPS = self::encodeUntukIPS($siswa['c4']);
            $c5EncIPS = self::encodeUntukIPS($siswa['c5']);

            // --- Nilai mentah per kolom ---
            $nilaiMentahIPA = [
                'c1'     => (float) $siswa['c1'],
                'c2'     => (float) $siswa['c2'],
                'c4_ipa' => (float) $c4EncIPA,
                'c5_ipa' => (float) $c5EncIPA,
            ];
            $nilaiMentahIPS = [
                'c1'     => (float) $siswa['c1'],
                'c3'     => (float) $siswa['c3'],
                'c4_ips' => (float) $c4EncIPS,
                'c5_ips' => (float) $c5EncIPS,
            ];

            // --- Normalisasi vektor: x*ij = xij / pembagi_j ---
            $normIPA = [];
            foreach ($nilaiMentahIPA as $kol => $val) {
                $pembagi = $pembaginya['ipa'][$kol];
                // Handle edge case: jika pembagi = 0 (semua nilai di kolom ini 0)
                $normIPA[$kol] = ($pembagi > 0) ? ($val / $pembagi) : 0;
            }

            $normIPS = [];
            foreach ($nilaiMentahIPS as $kol => $val) {
                $pembagi = $pembaginya['ips'][$kol];
                $normIPS[$kol] = ($pembagi > 0) ? ($val / $pembagi) : 0;
            }

            // --- Pembobotan: y*ij = wj × x*ij ---
            $bobotedIPA = [];
            foreach ($normIPA as $kol => $normVal) {
                $bobotedIPA[$kol] = $bobotIPA[$kol] * $normVal;
            }

            $bobotedIPS = [];
            foreach ($normIPS as $kol => $normVal) {
                $bobotedIPS[$kol] = $bobotIPS[$kol] * $normVal;
            }

            // --- Skor akhir: Yi = Σ y*ij (semua benefit) ---
            $skorIPA = array_sum($bobotedIPA);
            $skorIPS = array_sum($bobotedIPS);

            // --- Confidence score ---
            $maxSkor    = max($skorIPA, $skorIPS);
            $confidence = ($maxSkor > 0)
                ? (abs($skorIPA - $skorIPS) / $maxSkor * 100)
                : 0.0;

            // --- Rekomendasi akhir ---
            // PENTING: jika skor_ipa > skor_ips → IPA, sebaliknya → IPS
            // Arah perbandingan ini TIDAK BOLEH TERBALIK
            $rekomendasi = ($skorIPA > $skorIPS) ? 'IPA' : 'IPS';

            // Simpan hasil lengkap termasuk semua langkah untuk transparansi
            $hasilSementara[] = [
                // Data siswa asli
                'siswa'       => $siswa,

                // Encoding C4/C5 (untuk dokumentasi transparansi)
                'encoding' => [
                    'c4_pilihan'  => strtoupper($siswa['c4']),
                    'c5_pilihan'  => strtoupper($siswa['c5']),
                    'c4_ipa_val'  => $c4EncIPA,
                    'c5_ipa_val'  => $c5EncIPA,
                    'c4_ips_val'  => $c4EncIPS,
                    'c5_ips_val'  => $c5EncIPS,
                ],

                // Nilai mentah per kolom (setelah encoding)
                'nilai_mentah' => [
                    'ipa' => $nilaiMentahIPA,
                    'ips' => $nilaiMentahIPS,
                ],

                // Pembagi normalisasi (sama untuk semua siswa, disimpan agar bisa ditampilkan)
                'pembagi' => $pembaginya,

                // Nilai ternormalisasi
                'normalisasi' => [
                    'ipa' => $normIPA,
                    'ips' => $normIPS,
                ],

                // Bobot yang digunakan
                'bobot' => [
                    'ipa' => $bobotIPA,
                    'ips' => $bobotIPS,
                ],

                // Nilai setelah pembobotan
                'pembobotan' => [
                    'ipa' => $bobotedIPA,
                    'ips' => $bobotedIPS,
                ],

                // Skor akhir
                'skor_ipa'    => $skorIPA,
                'skor_ips'    => $skorIPS,
                'yi'          => $skorIPA + $skorIPS,
                'confidence'  => $confidence,
                'rekomendasi' => $rekomendasi,
            ];
        }

        // ====================================================
        // LANGKAH 4: Ranking
        // Ranking IPA: urutkan berdasarkan skor_ipa (tertinggi = rank 1)
        // ====================================================

        // Hitung rank IPA
        $indeksIPA = array_keys($hasilSementara);
        usort($indeksIPA, function($a, $b) use ($hasilSementara) {
            return $hasilSementara[$b]['skor_ipa'] <=> $hasilSementara[$a]['skor_ipa'];
        });
        foreach ($indeksIPA as $rank => $idx) {
            $hasilSementara[$idx]['rank_ipa'] = $rank + 1;
        }

        // Hitung rank IPS
        $indeksIPS = array_keys($hasilSementara);
        usort($indeksIPS, function($a, $b) use ($hasilSementara) {
            return $hasilSementara[$b]['skor_ips'] <=> $hasilSementara[$a]['skor_ips'];
        });
        foreach ($indeksIPS as $rank => $idx) {
            $hasilSementara[$idx]['rank_ips'] = $rank + 1;
        }

        // Urutkan hasil final berdasarkan skor_ipa untuk tampilan tabel
        usort($hasilSementara, function($a, $b) {
            return $b['skor_ipa'] <=> $a['skor_ipa'];
        });

        // Tambahkan nomor urut final
        foreach ($hasilSementara as $i => &$item) {
            $item['no_urut'] = $i + 1;
        }
        unset($item);

        return $hasilSementara;
    }

    // --------------------------------------------------------
    // HELPER PRIVATE: Hitung Pembagi Normalisasi
    // --------------------------------------------------------

    /**
     * Menghitung pembagi normalisasi untuk setiap kolom.
     * Rumus: pembagi_j = sqrt(Σ xij²)
     *
     * @param array $kolomIPA Matriks kolom untuk skor IPA
     * @param array $kolomIPS Matriks kolom untuk skor IPS
     * @return array ['ipa' => [...], 'ips' => [...]]
     */
    private static function hitungPembagi(array $kolomIPA, array $kolomIPS): array {
        $pembaginya = ['ipa' => [], 'ips' => []];

        foreach ($kolomIPA as $kol => $nilaiArr) {
            $sumKuadrat = 0;
            foreach ($nilaiArr as $v) {
                $sumKuadrat += $v * $v;
            }
            // sqrt dari jumlah kuadrat; jika 0 set ke 1 agar tidak division by zero
            $pembaginya['ipa'][$kol] = ($sumKuadrat > 0) ? sqrt($sumKuadrat) : 1.0;
        }

        foreach ($kolomIPS as $kol => $nilaiArr) {
            $sumKuadrat = 0;
            foreach ($nilaiArr as $v) {
                $sumKuadrat += $v * $v;
            }
            $pembaginya['ips'][$kol] = ($sumKuadrat > 0) ? sqrt($sumKuadrat) : 1.0;
        }

        return $pembaginya;
    }

    // --------------------------------------------------------
    // HELPER: Format angka untuk tampilan
    // --------------------------------------------------------

    /**
     * Format angka desimal dengan presisi tertentu untuk tampilan
     *
     * @param float  $angka
     * @param int    $presisi Jumlah digit desimal
     * @return string
     */
    public static function formatAngka(float $angka, int $presisi = 6): string {
        return number_format($angka, $presisi, '.', '');
    }

    /**
     * Ambil label nama kolom yang dapat dibaca manusia
     *
     * @param string $kodeKolom  e.g. 'c1', 'c4_ipa', 'c5_ips'
     * @return string Label yang mudah dibaca
     */
    public static function labelKolom(string $kodeKolom): string {
        $labels = [
            'c1'     => 'C1 - Nilai Matematika',
            'c2'     => 'C2 - Nilai IPA',
            'c3'     => 'C3 - Nilai IPS',
            'c4_ipa' => 'C4 - Minat Siswa (arah IPA)',
            'c5_ipa' => 'C5 - Minat Ortu (arah IPA)',
            'c4_ips' => 'C4 - Minat Siswa (arah IPS)',
            'c5_ips' => 'C5 - Minat Ortu (arah IPS)',
        ];
        return $labels[$kodeKolom] ?? $kodeKolom;
    }
}
