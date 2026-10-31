<?php
// ============================================================
// core/Logger.php
// Modul Histori Perhitungan
// Menyimpan snapshot setiap sesi perhitungan ke database
// sehingga bisa dibandingkan antar versi (sebelum-sesudah perubahan)
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Logger {

    /**
     * Simpan sesi perhitungan baru ke database.
     *
     * @param string $namaSesi   Nama sesi (e.g. "Perhitungan Batch 1")
     * @param string $deskripsi  Deskripsi opsional
     * @return int ID sesi yang baru dibuat
     */
    public static function buatSesi(string $namaSesi = 'Perhitungan Otomatis', string $deskripsi = ''): int {
        $bobot = MOORA::getBobot();
        $id = dbExecute(
            'INSERT INTO sesi_perhitungan (nama_sesi, deskripsi, bobot_c1, bobot_c2, bobot_c3, bobot_c4, bobot_c5)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $namaSesi,
                $deskripsi,
                $bobot['c1'] ?? 0.35,
                $bobot['c2'] ?? 0.25,
                $bobot['c3'] ?? 0.20,
                $bobot['c4'] ?? 0.15,
                $bobot['c5'] ?? 0.05,
            ]
        );
        return (int) $id;
    }

    /**
     * Simpan hasil perhitungan per siswa ke database.
     * Dipanggil setelah MOORA::hitung() selesai.
     *
     * @param int   $sesiId  ID sesi dari buatSesi()
     * @param array $hasil   Array hasil dari MOORA::hitung()
     * @return void
     */
    public static function simpanHasil(int $sesiId, array $hasil): void {
        $db  = getDB();
        $jumlah = 0;

        foreach ($hasil as $item) {
            $siswaId = self::getSiswaId($item['siswa']['nis']);
            if (!$siswaId) continue;

            // Encode detail lengkap sebagai JSON untuk keperluan step-by-step view
            $detailJson = json_encode([
                'encoding'    => $item['encoding'],
                'nilai_mentah'=> $item['nilai_mentah'],
                'pembagi'     => $item['pembagi'],
                'normalisasi' => $item['normalisasi'],
                'bobot'       => $item['bobot'],
                'pembobotan'  => $item['pembobotan'],
            ], JSON_PRETTY_PRINT);

            dbExecute(
                'INSERT INTO hasil_perhitungan
                    (sesi_id, siswa_id, skor_ipa, skor_ips, yi, confidence, rekomendasi, rank_ipa, rank_ips, detail_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $sesiId,
                    $siswaId,
                    $item['skor_ipa'],
                    $item['skor_ips'],
                    $item['yi'],
                    $item['confidence'],
                    $item['rekomendasi'],
                    $item['rank_ipa'] ?? null,
                    $item['rank_ips'] ?? null,
                    $detailJson,
                ]
            );
            $jumlah++;
        }

        // Update jumlah siswa di sesi
        dbExecute(
            'UPDATE sesi_perhitungan SET jumlah_siswa = ? WHERE id = ?',
            [$jumlah, $sesiId]
        );
    }

    /**
     * Ambil daftar semua sesi perhitungan (untuk halaman histori)
     *
     * @return array
     */
    public static function getDaftarSesi(): array {
        return dbQuery(
            'SELECT s.*,
                    COUNT(h.id) as jumlah_hasil,
                    SUM(CASE WHEN h.rekomendasi = "IPA" THEN 1 ELSE 0 END) as jumlah_ipa,
                    SUM(CASE WHEN h.rekomendasi = "IPS" THEN 1 ELSE 0 END) as jumlah_ips
             FROM sesi_perhitungan s
             LEFT JOIN hasil_perhitungan h ON h.sesi_id = s.id
             GROUP BY s.id
             ORDER BY s.created_at DESC'
        );
    }

    /**
     * Ambil detail satu sesi beserta semua hasil perhitungannya
     *
     * @param int $sesiId
     * @return array|null
     */
    public static function getDetailSesi(int $sesiId): ?array {
        $sesi = dbQueryOne(
            'SELECT * FROM sesi_perhitungan WHERE id = ?',
            [$sesiId]
        );
        if (!$sesi) return null;

        $hasilList = dbQuery(
            'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
             FROM hasil_perhitungan h
             JOIN siswa s ON s.id = h.siswa_id
             WHERE h.sesi_id = ?
             ORDER BY h.rank_ipa ASC',
            [$sesiId]
        );

        return [
            'sesi'  => $sesi,
            'hasil' => $hasilList,
        ];
    }

    /**
     * Hapus sesi perhitungan (cascade delete hasil juga)
     *
     * @param int $sesiId
     * @return void
     */
    public static function hapusSesi(int $sesiId): void {
        dbExecute('DELETE FROM sesi_perhitungan WHERE id = ?', [$sesiId]);
    }

    /**
     * Helper: cari siswa ID berdasarkan NIS
     *
     * @param string $nis
     * @return int|null
     */
    private static function getSiswaId(string $nis): ?int {
        $row = dbQueryOne('SELECT id FROM siswa WHERE nis = ?', [$nis]);
        return $row ? (int) $row['id'] : null;
    }
}
