<?php

namespace App\Services\Superadmin;

use App\Models\Reserva;
use Illuminate\Support\Carbon;

class ReservationNumberService
{
    public function next(): string
    {
        $prefix = 'RES-' . Carbon::now()->format('Ymd') . '-';
        $last = Reserva::query()
            ->where('nre_res', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id_reserva')
            ->value('nre_res');

        $sequence = 1;

        if ($last && preg_match('/-(\d+)$/', (string) $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
