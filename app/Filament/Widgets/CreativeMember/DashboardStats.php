<?php

namespace App\Filament\Widgets\CreativeMember;

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
        return auth()->check() && auth()->user()->hasRole(\App\Enums\Role::CreativeMember);
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
                ->url(route('filament.dashboard.resources.creative-member.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Diterima', $draftStats[1] ?? 0)
                ->description('Draft yang sudah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.creative-member.proposal-drafts.index', ['tableFilters[id_event][value]' => '']))
                ->chart([1, 2, 3, 2, 3, 4, 3]),

            Stat::make('Menunggu Admin', $draftStats[2] ?? 0)
                ->description('Menunggu verifikasi Organizer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.creative-member.proposal-drafts.index', ['tableFilters[id_event][value]' => '', 'tableFilters[proposal_draft_status][value]' => '2']))
                ->chart([2, 1, 3, 2, 1, 3, 2]),

            Stat::make('Menunggu Manager', $draftStats[3] ?? 0)
                ->description('Disetujui Admin, menunggu Manager')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->url(route('filament.dashboard.resources.creative-member.proposal-drafts.index', ['tableFilters[id_event][value]' => '', 'tableFilters[proposal_draft_status][value]' => '3']))
                ->chart([1, 3, 2, 4, 3, 2, 4]),

            Stat::make('Ditolak', $draftStats[0] ?? 0)
                ->description('Draft yang ditolak Admin/Manager')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.dashboard.resources.creative-member.proposal-drafts.index', ['tableFilters[id_event][value]' => '', 'tableFilters[proposal_draft_status][value]' => '0']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),

            Stat::make('Submission Menunggu', $submissionStats[2] ?? 0)
                ->description('Submission menunggu Manager approve')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.creative-member.relation-managers.proposal-submissions.index', ['record' => '']))
                ->chart([1, 2, 1, 3, 2, 1, 2]),
        ];
    }
}
