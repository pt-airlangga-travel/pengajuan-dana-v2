<?php

namespace App\Filament\Widgets\EagleTreasurer;

use App\Models\Event;
use App\Models\ProposalSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | array | null $columns = ['default' => 2, 'md' => 3, 'lg' => 6];

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(\App\Enums\Role::EagleTreasurer);
    }

    protected function getStats(): array
    {
        $eventCount = Event::where('event_availability', true)->count();
        $submissionStats = ProposalSubmission::selectRaw('proposal_submission_status, count(*) as count')
            ->groupBy('proposal_submission_status')
            ->pluck('count', 'proposal_submission_status')
            ->toArray();

        return [
            Stat::make('Event Available', $eventCount)
                ->description('Total event yang tersedia')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url(route('filament.dashboard.resources.shared.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Proses Transfer', $submissionStats[3] ?? 0)
                ->description('Menunggu bukti transfer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.eagle-treasurer.relation-managers.proposal-submissions.index'))
                ->chart([1, 2, 1, 3, 2, 1, 2]),

            Stat::make('Selesai Transfer', $submissionStats[1] ?? 0)
                ->description('Semua rekening transferred')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.eagle-treasurer.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '1']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Ditolak', $submissionStats[0] ?? 0)
                ->description('Ditolak Treasurer')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.dashboard.resources.eagle-treasurer.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '0']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),

            Stat::make('Selesai (Draft)', $submissionStats[1] ?? 0)
                ->description('Draft & Submission selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.eagle-treasurer.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '1']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),
        ];
    }
}
