<?php

namespace App\Services\Superadmin;

use Illuminate\Support\Collection;

class ReservationPickupService
{
    public function __construct(private readonly PickupLogisticsService $logistics)
    {
    }

    public function calculate(Collection $inventories): array
    {
        return $this->logistics->reservationPayload($inventories);
    }
}
