<?php

namespace App\Filament\Widgets\OrganizerAdmin;

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
        return auth()->check() && auth()->user()->hasRole(\App\Enums\Role::OrganizerAdmin);
    }

    protected function getStats(): array
    {
        $eventCount = Event::where('event_availability', true)->count();
        $draftStats = ProposalDraft::selectRaw('proposal_draft_status, count(*) as count')
            ->groupBy('proposal_draft_status')
            ->pluck('count', 'proposal_draft_status')
            ->toArray();
        $submissionStats = ProposalSubmission::selectRaw('proposal_submission_status, count(*) as count')
            ->groupBy('proposal_submission_status')
            ->pluck('count', 'proposal_submission_status')
            ->toArray();

        return [
            Stat::make('Event Available', $eventCount)
                ->description('Total event yang tersedia')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url(route('filament.dashboard.resources.system-admin.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Draft Menunggu Admin', $draftStats[2] ?? 0)
                ->description('Menunggu verifikasi Organizer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.organizer-admin.proposal-drafts.index', ['tableFilters[proposal_draft_status][value]' => '2']))
                ->chart([2, 1, 3, 2, 1, 3, 2]),

            Stat::make('Draft Diterima', $draftStats[3] ?? 0)
                ->description('Disetujui, menunggu Manager')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info')
                ->url(route('filament.dashboard.resources.organizer-admin.proposal-drafts.index', ['tableFilters[proposal_draft_status][value]' => '3']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Draft Diterima Admin', $draftStats[1] ?? 0)
                ->description('Sudah disetujui Admin')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.organizer-admin.proposal-drafts.index', ['tableFilters[proposal_draft_status][value]' => '1']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Draft Ditolak', $draftStats[0] ?? 0)
                ->description('Ditolak Organizer')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.dashboard.resources.organizer-admin.proposal-drafts.index', ['tableFilters[proposal_draft_status][value]' => '0']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),

            Stat::make('Submission Menunggu Manager', $submissionStats[2] ?? 0)
                ->description('Menunggu Manager approve')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.organizer-admin.relation-managers.proposal-submissions.index'))
                ->chart([1, 2, 1, 3, 2, 1, 2]),

            Stat::make('Submission Diproses', $submissionStats[3] ?? 0)
                ->description('Diproses Bendahara')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->url(route('filament.dashboard.resources.organizer-admin.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '3']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Submission Selesai', $submissionStats[1] ?? 0)
                ->description('Transfer selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.organizer-admin.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '1']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Submission Ditolak', $submissionStats[0] ?? 0)
                ->description('Ditolak Manager/Bendahara')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.dashboard.resources.organizer-admin.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '0']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),

            Stat::make('Submission Dikembalikan', $submissionStats[4] ?? 0)
                ->description('Dikembalikan ke member')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->url(route('filament.dashboard.resources.organizer-admin.relation-managers.proposal-submissions.index', ['tableFilters[proposal_submission_status][value]' => '4']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),
        ];
    }
}
