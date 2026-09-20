<?php

namespace App\Filament\Resources\CreativeMember\Pages;

use App\Filament\Resources\CreativeMember\ProposalDraftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProposalDraft extends CreateRecord
{
    protected static string $resource = ProposalDraftResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->url(function () {
                $event = request()->query('event') ?? session('selected_event');
                return blank($event)
                    ? ProposalDraftResource::getUrl('index')
                    : ProposalDraftResource::getUrl('index', ['tableFilters' => ['id_event' => ['value' => $event]]]);
            });
    }

    protected function getRedirectUrl(): string
    {
        $event = $this->record->id_event ?? request()->query('event') ?? session('selected_event');
        return blank($event)
            ? ProposalDraftResource::getUrl('index')
            : ProposalDraftResource::getUrl('index', ['tableFilters' => ['id_event' => ['value' => $event]]]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate proposal_draft_defined_id = id_event + index (5 = 3 seq + 2 rev), sama v2 store()
        $eventId = $data['id_event'] ?? request()->query('event') ?? session('selected_event');
        if (blank($eventId)) {
            \Filament\Notifications\Notification::make()->title('Event wajib dipilih')->danger()->send();
            throw new \Exception('Event wajib dipilih');
        }
        $data['id_event'] = $eventId;
        $latestIndex = \App\Models\ProposalDraft::select('proposal_draft_index')
            ->whereRaw('SUBSTRING(proposal_draft_defined_id, 1, 12) = ?', [$eventId])
            ->orderBy('proposal_draft_index', 'desc')
            ->first();
        if (is_null($latestIndex)) {
            $latestIndex = \App\Services\ProposalHelper::defined_id(1, 3) . \App\Services\ProposalHelper::defined_id(0, 2); // 00100
        } else {
            $latestIndex = \App\Services\ProposalHelper::defined_id(intval(substr($latestIndex->proposal_draft_index, 0, 3)) + 1, 3) . \App\Services\ProposalHelper::defined_id(0, 2);
        }
        $definedId = $eventId . $latestIndex;
        $data['proposal_draft_defined_id'] = $definedId;
        $data['proposal_draft_index'] = $latestIndex;
        $data['creative_member'] = \Illuminate\Support\Facades\Auth::id();
        $data['proposal_draft_note_admin'] = 'not set';
        $data['proposal_draft_note_member'] = $data['proposal_draft_note_member'] ?? 'not set';
        $data['proposal_draft_status'] = 2;
        // FileUpload disk public simpan 'proposal/xxx.jpeg', rename ke {definedId}.ext
        $uploaded = $data['file_attached_name'] ?? null;
        if (is_array($uploaded)) $uploaded = reset($uploaded);
        if (filled($uploaded)) {
            $ext = pathinfo($uploaded, PATHINFO_EXTENSION) ?: pathinfo(basename($uploaded), PATHINFO_EXTENSION) ?: 'pdf';
            $newFile = $definedId . '.' . $ext;
            $oldPath = ltrim($uploaded, '/'); // misal proposal/xxx.jpeg
            if (! str_starts_with($oldPath, 'proposal/')) $oldPath = 'proposal/' . basename($uploaded);
            $newPath = 'proposal/' . $newFile;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->move($oldPath, $newPath);
                $data['file_attached_name'] = $newFile;
            } else {
                // fallback: file mungkin sudah berupa nama saja
                $data['file_attached_name'] = basename($uploaded);
            }
        } else {
            $data['file_attached_name'] = $definedId . '.pdf';
        }
        // vendors + jenis_pengajuan tidak disimpan langsung (handle afterCreate)
        unset($data['vendors'], $data['jenis_pengajuan'], $data['pengaju_name'], $data['pengaju_email'], $data['pengaju_division']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // Simpan vendors dari form state (Livewire form data)
        $vendors = $this->form->getState()['vendors'] ?? [];
        $jenis = $this->form->getState()['jenis_pengajuan'] ?? 'fee';
        if ($jenis === 'non-fee' && count($vendors) > 1) {
            $vendors = array_slice($vendors, 0, 1);
        }
        foreach ($vendors as $v) {
            $rawSubtotal = isset($v['vendor_sub_total']) ? preg_replace('/\D/', '', (string) $v['vendor_sub_total']) : 'not set';
            if ($rawSubtotal === '' || $rawSubtotal === null) $rawSubtotal = 'not set';
            \App\Models\Vendor::create([
                'id_proposal_draft' => $this->record->proposal_draft_defined_id,
                'vendor_name' => $v['vendor_name'] ?? '-',
                'vendor_contact' => $v['vendor_contact'] ?? 'not set',
                'vendor_email' => $v['vendor_email'] ?? 'not set',
                'vendor_sub_total' => $rawSubtotal,
            ]);
        }
    }
}

