<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = [
            Stat::make('Total Users', User::where('role', 'user')->count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Open Tenders', Tender::where('status', 'open')->where('deadline', '>=', now())->count())
                ->description('Available tenders')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];

        if (auth()->user()?->isAdmin()) {
            $stats[] = Stat::make('Active Subscriptions', Subscription::where('status', 'active')->where('end_date', '>=', now())->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success');
            $stats[] = Stat::make('Total Revenue', 'TZS ' . number_format(Payment::where('status', 'completed')->sum('amount')))
                ->description('Completed payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning');
        } else {
            $stats[] = Stat::make('Total Applications', TenderApplication::count())
                ->description('All applications')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success');
        }

        return $stats;
    }
}
