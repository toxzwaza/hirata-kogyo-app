<?php

namespace Tests\Feature;

use App\Models\Staff;
use Tests\TestCase;

/**
 * ダッシュボードの疎通テスト。
 *
 * 既存のシードデータ（staff）を用いた読み取り専用テストのため、
 * RefreshDatabase は使用しない（開発DBのデータを保持する）。
 */
class DashboardTest extends TestCase
{
    public function test_dashboard_is_displayed_with_expected_props(): void
    {
        $staff = Staff::query()->first();

        if (! $staff) {
            $this->markTestSkipped('staff シードが存在しないためスキップします。');
        }

        $response = $this->actingAs($staff)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->has('summary')
                ->has('rateNegotiation')
                ->has('staffProductivity')
                ->has('monthlyTrend')
                ->has('filters.from')
                ->has('filters.to')
        );
    }

    public function test_dashboard_accepts_period_filters(): void
    {
        $staff = Staff::query()->first();

        if (! $staff) {
            $this->markTestSkipped('staff シードが存在しないためスキップします。');
        }

        $response = $this->actingAs($staff)
            ->get('/dashboard?from=2026-05-01&to=2026-05-31');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('filters.from', '2026-05-01')
                ->where('filters.to', '2026-05-31')
        );
    }
}
