<?php
use CodeIgniter\I18n\Time;
if (!function_exists('tgl_id')) {
    /**
     * Format: "Senin, 01 - Januari - 2025"
     * @param string|null $date  (Y-m-d / strtotime-parseable)
     * @param bool        $withDay
     * @param string      $sep
     */
    function tgl_id(?string $date, bool $withDay = true, string $sep = ' - '): string
    {
        if (!$date) return '';
        $ts = strtotime(str_replace('/', '-', $date));
        if ($ts === false) return (string)$date;

        $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        $d = date('j', $ts);
        $m = (int) date('n', $ts);
        $y = date('Y', $ts);
        $h = $hari[(int) date('w', $ts)];

        $core = sprintf('%02d%s%s%s%s', $d, $sep, $bulan[$m], $sep, $y);
        return $withDay ? "$h, $core" : $core;
    }
}
