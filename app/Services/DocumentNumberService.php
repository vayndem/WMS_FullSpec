<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public const EXTERNAL = 'EXTERNAL';
    public const FINANCIAL = 'FINANCIAL';
    public const INTERNAL = 'INTERNAL';
    public function external(string $prefix, CarbonInterface|string|null $date = null): string
    {
        $date = $this->date($date);
        $prefix = strtoupper($prefix);
        if (!preg_match('/^[A-Z]{3}$/', $prefix)) throw new InvalidArgumentException('Prefix eksternal wajib tiga huruf.');
        $sequence = $this->increment("EXT_{$prefix}", $date->format('ymd'));
        return $prefix . $date->format('ymd') . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }
    public function financial(string $marker, CarbonInterface|string|null $date = null): string
    {
        $date = $this->date($date);
        $marker = strtoupper($marker);
        if (!preg_match('/^[A-Z]{2}$/', $marker)) throw new InvalidArgumentException('Tanda finansial wajib dua huruf.');
        $sequence = $this->increment("FIN_{$marker}", $date->format('Ymd'));
        return $date->format('y-d') . "-{$marker}-" . $this->romanMonth((int)$date->format('n')) . '-' . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }
    public function internal(string $prefix, string $sub, CarbonInterface|string|null $date = null): string
    {
        $date = $this->date($date);
        $prefix = strtoupper($prefix);
        $sub = strtoupper($sub);
        if (!preg_match('/^[A-Z]{3}$/', $prefix) || !preg_match('/^[A-Z]{2,3}$/', $sub)) throw new InvalidArgumentException('Prefix internal tidak valid.');
        $sequence = $this->increment("INT_{$prefix}_{$sub}", $date->format('ym'));
        return "{$prefix}-{$sub}-" . $date->format('ym') . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }
    private function increment(string $namespace, string $period): int
    {
        return DB::transaction(function () use ($namespace, $period) {
            DB::table('document_sequences')->insertOrIgnore(['namespace' => $namespace, 'period_key' => $period, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('document_sequences')->where('namespace', $namespace)->where('period_key', $period)->lockForUpdate()->first();
            $next = (int)$row->last_number + 1;
            if ($next > 999) {
                throw new InvalidArgumentException("Nomor urut {$namespace} sudah melebihi kapasitas 999 pada periode {$period}.");
            }
            DB::table('document_sequences')->where('id', $row->id)->update(['last_number' => $next, 'updated_at' => now()]);
            return $next;
        });
    }
    private function date(CarbonInterface|string|null $date): CarbonInterface
    {
        return $date instanceof CarbonInterface ? $date : Carbon::parse($date ?: now());
    }
    private function romanMonth(int $month): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'][$month];
    }
}
