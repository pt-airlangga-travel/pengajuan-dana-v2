<?php

namespace App\Filament\Widgets\SystemAdmin;

use App\Models\User;
use App\Models\Event;
use App\Models\ProposalDraft;
use App\Models\ProposalSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | array | null $columns = ['default' => 2, 'md' => 3, 'lg' => 6];

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(\App\Enums\Role::SystemAdmin);
    }

    protected function getStats(): array
    {
        $userCount = User::count();
        $eventCount = Event::count();
        $draftCount = ProposalDraft::count();
        $submissionCount = ProposalSubmission::count();

        $draftStats = ProposalDraft::selectRaw('proposal_draft_status, count(*) as count')
            ->groupBy('proposal_draft_status')
            ->pluck('count', 'proposal_draft_status')
            ->toArray();
        $submissionStats = ProposalSubmission::selectRaw('proposal_submission_status, count(*) as count')
            ->groupBy('proposal_submission_status')
            ->pluck('count', 'proposal_submission_status')
            ->toArray();

        return [
            Stat::make('Total Users', $userCount)
                ->description('Semua user terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(route('filament.dashboard.resources.system-admin.users.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Events', $eventCount)
                ->description('Semua event')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Draft', $draftCount)
                ->description('Semua proposal draft')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Submission', $submissionCount)
                ->description('Semua proposal submission')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Draft Menunggu', $draftStats[2] ?? 0)
                ->description('Status Menunggu')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Draft Selesai', $draftStats[1] ?? 0)
                ->description('Status Selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Submission Selesai', $submissionStats[1] ?? 0)
                ->description('Transfer selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Submission Diproses', $submissionStats[3] ?? 0)
                ->description('Sedang diproses Treasurer')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([1, 2, 3, 2, 1, 3, 2]),
        ];
    }
}
