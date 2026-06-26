<?php
namespace Modules\Teori\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class AttemptModel extends Model
{
    protected $table         = 'ujian_attempt';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true; // pastikan tabel punya created_at & updated_at
    protected $allowedFields = [
        'id_mahasiswa','id_paket','kode','start_at','end_at',
        'remaining_seconds','violations','status',
        'created_at','updated_at', 'benar', 'salah', 'kosong','no_ujian'
    ];

    /**
     * Buat attempt baru atau lanjutkan. Sekaligus set urutan soal acak per peserta (sekali).
     */
    public function createOrResume(int $idMhs, int $paketId, string $kode, string $tanggal, string $mulai, string $selesai): array
    {
        // ✅ gunakan nama kolom yang benar
        $row = $this->where([
                    'id_mahasiswa' => $idMhs,
                    'id_paket'     => $paketId,
                    'kode'         => $kode,
                ])
                ->orderBy('id','DESC')
                ->first();

        // hitung total durasi (detik) dari jadwal mulai–selesai
        $start = Time::parse($tanggal.' '.$mulai);
        $end   = Time::parse($tanggal.' '.$selesai);
        $total = max(0, $end->getTimestamp() - $start->getTimestamp());

        if ($row) {
            // clamp remaining agar tidak melewati end time (jaga-jaga kalau server waktu maju/mundur)
            $now = Time::now()->getTimestamp();
            $rem = max(0, $end->getTimestamp() - $now);
            $row['remaining_seconds'] = min((int)$row['remaining_seconds'], $rem);
            return $row;
        }

        // generate urutan acak untuk paket (sekali per peserta)
        $soalRows = (new UjianTeoriModel())->listPublishedByPaket($paketId); // pastikan method ini ada
        $ids = array_map(static fn($r)=> (int)$r['id'], $soalRows);
        shuffle($ids);
        $orderJson = json_encode($ids);

        $insert = [
            'id_mahasiswa'      => $idMhs,
            'id_paket'          => $paketId,
            'kode'              => $kode,
            'start_at'          => $start->toDateTimeString(),
            'end_at'            => $end->toDateTimeString(),
            'remaining_seconds' => $total,
            'violations'        => 0,
            'status'            => 'ongoing',
            'order_json'        => $orderJson,
        ];

        $id = $this->insert($insert, true);
        $insert['id'] = $id;
        return $insert;
    }

    /**
     * Kurangi waktu, tambah pelanggaran bila perlu. Auto-selesai jika habis / melewati batas pelanggaran.
     */
    public function tickAndMaybeFlag(int $attemptId, int $delta, string $reason): array
    {
        $row = $this->find($attemptId);
        if (!$row) return ['status'=>'done','remaining_seconds'=>0,'violations'=>0];

        if ($row['status'] === 'done') return $row;

        // waktu
        $rem = max(0, (int)$row['remaining_seconds'] - $delta);

        // pelanggaran
        $viol = (int)$row['violations'];
        if (in_array($reason, ['blur','visibility','fullscreen-exit'], true)) {
            $viol++;
        }

        $status = $rem <= 0 ? 'done' : $row['status'];
        if ($viol >= 3) $status = 'done';

        $this->update($attemptId, [
            'remaining_seconds' => $rem,
            'violations'        => $viol,
            'status'            => $status,
        ]);

        $row['remaining_seconds'] = $rem;
        $row['violations']        = $viol;
        $row['status']            = $status;
        return $row;
    }

    public function finish(int $attemptId): void
    {
        $row = $this->find($attemptId);
        if (!$row) return;
        if ($row['status'] !== 'done') {
           $now = Time::now(config('App')->appTimezone);
        $this->update($attemptId, [
            'status'      => 'finished',
            'finished_at' => $now->toDateTimeString()
        ]);
        }
    }

     public function nilai(int $attemptId,int $benar,int $salah,int $kosong): void
    {
        $row = $this->find($attemptId);
      
        if (!$row) return;
          $this->update($attemptId, [
            'benar'      => $benar,
            'salah' => $salah,
            'kosong' => $kosong
        ]);
    }
      public function heartbeat(array $attempt, int $delta, ?string $reason=null, int $maxViol=3): array
    {
        $tz = config('App')->appTimezone;
        $delta = max(1, (int)$delta);

        // kurangi waktu
        $remain = max(0, (int)$attempt['remaining_seconds'] - $delta);

        // tambah pelanggaran HANYA untuk reason cheating
        $viol = (int)$attempt['violations'];
        if (in_array($reason, ['blur','visibility','fullscreen-exit'], true)) {
            $viol++;
        }

        // status
        $status = $remain <= 0 ? 'done' : 'ongoing';
        if ($viol >= $maxViol) $status = 'done';

        $this->update($attempt['id'], [
            'remaining_seconds' => $remain,
            'violations'        => $viol,
            'last_heartbeat'    => Time::now($tz)->toDateTimeString(),
            'status'            => $status,
            'finished_at'       => $status === 'done' ? Time::now($tz)->toDateTimeString() : null,
        ]);

        $attempt['remaining_seconds'] = $remain;
        $attempt['violations']        = $viol;
        $attempt['status']            = $status;
        return $attempt;
    }

     public function attachNoUjian(int $attemptId, int $idMhs, string $kode, ?string $noUjian = null): void
    {
        $row = $this->select('id,no_ujian')->find($attemptId);
        if (!$row) return;
        if (!empty($row['no_ujian'])) return; // sudah ada, tidak perlu apa-apa

        if (!$noUjian) {
            $ac = db_connect()->table('admin_cbt')
                  ->select('no_ujian')->where(['id_mahasiswa'=>$idMhs,'kode'=>$kode])
                  ->get()->getRowArray();
            $noUjian = $ac['no_ujian'] ?? null;
        }

        if ($noUjian) {
            $this->update($attemptId, ['no_ujian' => $noUjian]);
        }
    }

}
