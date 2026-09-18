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
        [$namespace, $period, $date, $prefix] = $this->kunciEksternal($prefix, $date);

        return $this->formatEksternal($prefix, $date, $this->increment($namespace, $period));
    }

    public function financial(string $marker, CarbonInterface|string|null $date = null): string
    {
        [$namespace, $period, $date, $marker] = $this->kunciFinansial($marker, $date);

        return $this->formatFinansial($marker, $date, $this->increment($namespace, $period));
    }

    public function internal(string $prefix, string $sub, CarbonInterface|string|null $date = null): string
    {
        [$namespace, $period, $date, $prefix, $sub] = $this->kunciInternal($prefix, $sub, $date);

        return $this->formatInternal($prefix, $sub, $date, $this->increment($namespace, $period));
    }

    public function preview(string $jenis, string $prefix, ?string $sub = null, CarbonInterface|string|null $date = null): string
    {
        if ($jenis === self::EXTERNAL) {
            [$namespace, $period, $date, $prefix] = $this->kunciEksternal($prefix, $date);

            return $this->formatEksternal($prefix, $date, $this->peek($namespace, $period));
        }

        if ($jenis === self::FINANCIAL) {
            [$namespace, $period, $date, $prefix] = $this->kunciFinansial($prefix, $date);

            return $this->formatFinansial($prefix, $date, $this->peek($namespace, $period));
        }

        if ($jenis === self::INTERNAL) {
            [$namespace, $period, $date, $prefix, $sub] = $this->kunciInternal($prefix, (string) $sub, $date);

            return $this->formatInternal($prefix, $sub, $date, $this->peek($namespace, $period));
        }

        throw new InvalidArgumentException("Jenis nomor dokumen {$jenis} tidak dikenal.");
    }

    private function kunciEksternal(string $prefix, CarbonInterface|string|null $date): array
    {
        $date = $this->date($date);
        $prefix = strtoupper($prefix);

        if (!preg_match('/^[A-Z]{3}$/', $prefix)) {
            throw new InvalidArgumentException('Prefix eksternal wajib tiga huruf.');
        }

        return ["EXT_{$prefix}", $date->format('ymd'), $date, $prefix];
    }

    private function kunciFinansial(string $marker, CarbonInterface|string|null $date): array
    {
        $date = $this->date($date);
        $marker = strtoupper($marker);

        if (!preg_match('/^[A-Z]{2}$/', $marker)) {
            throw new InvalidArgumentException('Tanda finansial wajib dua huruf.');
        }

        return ["FIN_{$marker}", $date->format('Ymd'), $date, $marker];
    }

    private function kunciInternal(string $prefix, string $sub, CarbonInterface|string|null $date): array
    {
        $date = $this->date($date);
        $prefix = strtoupper($prefix);
        $sub = strtoupper($sub);

        if (!preg_match('/^[A-Z]{3}$/', $prefix) || !preg_match('/^[A-Z]{2,3}$/', $sub)) {
            throw new InvalidArgumentException('Prefix internal tidak valid.');
        }

        return ["INT_{$prefix}_{$sub}", $date->format('ym'), $date, $prefix, $sub];
    }

    private function formatEksternal(string $prefix, CarbonInterface $date, int $sequence): string
    {
        return $prefix . $date->format('ymd') . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function formatFinansial(string $marker, CarbonInterface $date, int $sequence): string
    {
        return $date->format('y-d') . "-{$marker}-" . $this->romanMonth((int) $date->format('n'))
            . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function formatInternal(string $prefix, string $sub, CarbonInterface $date, int $sequence): string
    {
        return "{$prefix}-{$sub}-" . $date->format('ym') . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function peek(string $namespace, string $period): int
    {
        $last = (int) DB::table('document_sequences')
            ->where('namespace', $namespace)
            ->where('period_key', $period)
            ->value('last_number');

        return min($last + 1, 999);
    }

    private function increment(string $namespace, string $period): int
    {
        return DB::transaction(function () use ($namespace, $period) {
            DB::table('document_sequences')->insertOrIgnore([
                'namespace' => $namespace,
                'period_key' => $period,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('document_sequences')
                ->where('namespace', $namespace)
                ->where('period_key', $period)
                ->lockForUpdate()
                ->first();

            $next = (int) $row->last_number + 1;

            if ($next > 999) {
                throw new InvalidArgumentException("Nomor urut {$namespace} sudah melebihi kapasitas 999 pada periode {$period}.");
            }

            DB::table('document_sequences')->where('id', $row->id)->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

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
