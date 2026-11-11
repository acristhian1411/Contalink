<?php

namespace Tests\Unit;

use App\Http\Controllers\Web\DashboardController;
use Carbon\Carbon;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_period_bounds_include_only_dates_up_to_today(): void
    {
        $today = Carbon::parse('2026-11-10 12:30:00');

        [$from, $to] = DashboardController::periodBounds('today', $today);
        $this->assertSame(['2026-11-10', '2026-11-10'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = DashboardController::periodBounds('week', $today);
        $this->assertSame(['2026-11-09', '2026-11-10'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = DashboardController::periodBounds('month', $today);
        $this->assertSame(['2026-11-01', '2026-11-10'], [$from->toDateString(), $to->toDateString()]);
    }
}
