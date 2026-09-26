<?php

namespace Tests\Feature;

use App\Services\Superadmin\PickupLogisticsService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessTimezoneTest extends TestCase
{
    public function test_pickup_uses_the_business_day_across_the_utc_date_boundary(): void
    {
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $previous = date_default_timezone_get();
        try {
            date_default_timezone_set('America/La_Paz');
            Carbon::setTestNow(Carbon::parse('2026-09-11T02:00:00Z'));
            $logistics = app(PickupLogisticsService::class)->calculate(collect([(object) ['ubi_inv' => 'DEPOSITO']]));
            $this->assertSame('2026-09-13 00:00:00', $logistics['available_at']->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-13 23:59:59', $logistics['limit_at']->format('Y-m-d H:i:s'));
            $this->assertSame('-04:00', $logistics['available_at']->format('P'));
            $this->assertSame('2026-09-13T04:00:00.000000Z', $logistics['available_at']->toJSON());
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($previous);
        }
    }
}
