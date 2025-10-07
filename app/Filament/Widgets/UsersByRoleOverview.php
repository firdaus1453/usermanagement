<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersByRoleOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Super Admins', User::where('role', 'superadmin')->count())
                ->description(User::where('role', 'superadmin')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger')
                ->chart($this->getWeeklyTrend('superadmin')),

            Stat::make('Admins', User::where('role', 'admin')->count())
                ->description(User::where('role', 'admin')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->chart($this->getWeeklyTrend('admin')),

            Stat::make('Operators', User::where('role', 'operator')->count())
                ->description(User::where('role', 'operator')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('success')
                ->chart($this->getWeeklyTrend('operator')),

            Stat::make('Validators', User::where('role', 'validator')->count())
                ->description(User::where('role', 'validator')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->chart($this->getWeeklyTrend('validator')),
        ];
    }

    /**
     * Get weekly trend for a specific role (last 7 days)
     */
    protected function getWeeklyTrend(string $role): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $data[] = User::where('role', $role)
                ->where('created_at', '<=', $date->endOfDay())
                ->count();
        }
        return $data;
    }
}
