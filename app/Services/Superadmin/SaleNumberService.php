<?php

namespace App\Services\Superadmin;

use App\Models\Venta;
use Illuminate\Support\Carbon;

class SaleNumberService
{
    public function next(): string
    {
        $prefix = 'VEN-' . Carbon::now()->format('Ymd') . '-';
        $last = Venta::query()
            ->where('nve_ven', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id_venta')
            ->value('nve_ven');

        $sequence = 1;

        if ($last && preg_match('/-(\d+)$/', (string) $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
