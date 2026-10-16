<?php

namespace App\Filament\Widgets\EagleTreasurer;

use App\Models\Event;
use App\Models\ProposalDraft;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | array | null $columns = ['default' => 1, 'sm' => 2, 'lg' => 3];

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(\App\Enums\Role::EagleTreasurer);
    }

    protected function getStats(): array
    {
        $eventCount = Event::where('event_availability', true)->count();
        // Angka widget dihitung dari draft agar konsisten dengan isi tabel tujuan.
        $needTransferCount = ProposalDraft::whereHas('ProposalSubmissions', fn ($q) => $q->where('proposal_submission_status', 3))->count();
        $draftStats = ProposalDraft::selectRaw('proposal_draft_status, count(*) as count')
            ->groupBy('proposal_draft_status')
            ->pluck('count', 'proposal_draft_status')
            ->toArray();

        return [
            Stat::make('Event Available', $eventCount)
                ->description('Total event yang tersedia')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url(route('filament.dashboard.resources.shared.events.index'))
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Proses Transfer', $needTransferCount)
                ->description('Draft dengan submission menunggu bukti transfer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.dashboard.resources.eagle-treasurer.proposal-drafts.index', ['filters[id_event][value]' => 'ALL', 'filters[transfer_status][value]' => 'need']))
                ->chart([1, 2, 1, 3, 2, 1, 2]),

            Stat::make('Selesai Transfer', $draftStats[1] ?? 0)
                ->description('Draft yang sudah selesai transfer')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.dashboard.resources.eagle-treasurer.proposal-drafts.index', ['filters[id_event][value]' => 'ALL', 'filters[proposal_draft_status][value]' => '1']))
                ->chart([1, 2, 3, 2, 1, 3, 2]),

            Stat::make('Ditolak', $draftStats[0] ?? 0)
                ->description('Draft yang ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.dashboard.resources.eagle-treasurer.proposal-drafts.index', ['filters[id_event][value]' => 'ALL', 'filters[proposal_draft_status][value]' => '0']))
                ->chart([0, 1, 0, 2, 1, 0, 1]),
        ];
    }
}
