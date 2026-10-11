@php
    $banks = $record->BankAccounts ?? collect();
    $total = $banks->count();
    $done = $banks->filter(fn ($ba) => $ba->BankTransfer)->count();
    $percent = $total > 0 ? (int) round($done / $total * 100) : 0;

    $digits = fn ($value) => preg_replace('/\D/', '', (string) $value);
    $rupiah = fn ($value) => 'Rp ' . number_format((int) $digits($value), 0, ',', '.');
    // 1234567890 -> "1234 5678 90" (hanya untuk tampilan, yang disalin tetap angka asli)
    $groupRek = fn ($value) => trim(chunk_split($digits($value), 4, ' '));
    // 1234567890 -> "•••• 7890"
    $maskRek = fn ($value) => filled($digits($value)) ? '•••• ' . substr($digits($value), -4) : '';

    $grandTotal = $banks->sum(fn ($ba) => (int) $digits($ba->bank_account_sub_total ?? 0));
@endphp

<div
    class="space-y-2"
    x-data="{
        copied: null,
        copy(text, key) {
            const done = () => {
                this.copied = key
                setTimeout(() => { if (this.copied === key) this.copied = null }, 1500)
            }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done)
            } else {
                const el = document.createElement('textarea')
                el.value = text
                document.body.appendChild(el)
                el.select()
                document.execCommand('copy')
                el.remove()
                done()
            }
        },
    }"
>
    {{-- Ringkasan --}}
    <div class="pb-4">
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total transfer</p>
                <p class="mt-0.5 text-2xl font-semibold tabular-nums tracking-tight text-gray-950 dark:text-white">
                    {{ $rupiah($grandTotal) }}
                </p>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ $done }}</span>
                dari {{ $total }} rekening sudah ditransfer
            </p>
        </div>

        <div class="mt-4 h-1 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
             role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-success-500 transition-all" style="width: {{ $percent }}%"></div>
        </div>
    </div>

    @if($banks->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-white/20">
            <p class="text-sm font-medium text-gray-950 dark:text-white">Belum ada rekening bank</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submission ini belum memiliki rekening tujuan transfer.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($banks as $ba)
                @php
                    $bt = $ba->BankTransfer;
                    $noRek = $digits($ba->bank_account_number);
                    $hasNominal = filled($ba->bank_account_sub_total);
                    $fileName = $bt ? basename($bt->transfer_attached_name) : null;
                    $rekKey = 'rek-' . $loop->index;
                    $nomKey = 'nom-' . $loop->index;
                @endphp

                <section class="flex flex-col gap-4 rounded-2xl border border-gray-200 p-5 dark:border-white/10">
                    {{-- Pemilik + status --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $ba->bank_account_owner ?? '-' }}
                            </h3>
                            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">{{ $ba->Bank?->bank_name ?? '-' }}</p>
                        </div>

                        @if($bt)
                            <x-filament::badge color="success">Sudah ditransfer</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">Belum ditransfer</x-filament::badge>
                        @endif
                    </div>

                    {{-- Nomor rekening + nominal --}}
                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-white/5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nomor rekening</p>
                            <div class="mt-1.5 flex items-center justify-between gap-3">
                                <p class="select-all font-mono text-base font-semibold text-gray-950 dark:text-white">
                                    {{ $noRek !== '' ? $groupRek($noRek) : '-' }}
                                </p>
                                @if($noRek !== '')
                                    <button type="button"
                                            x-on:click="copy(@js($noRek), '{{ $rekKey }}')"
                                            title="Salin nomor rekening"
                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-1.5 py-1 text-sm font-medium text-gray-500 transition hover:text-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-white">
                                        <span x-show="copied !== '{{ $rekKey }}'" class="inline-flex items-center gap-1.5">
                                            <x-filament::icon icon="heroicon-o-clipboard-document" class="h-4 w-4" />
                                            Salin
                                        </span>
                                        <span x-show="copied === '{{ $rekKey }}'" x-cloak class="inline-flex items-center gap-1.5 text-success-600 dark:text-success-400">
                                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                                            Tersalin
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-white/5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nominal</p>
                            <div class="mt-1.5 flex items-center justify-between gap-3">
                                <p class="text-base font-semibold tabular-nums text-gray-950 dark:text-white">
                                    {{ $hasNominal ? $rupiah($ba->bank_account_sub_total) : '-' }}
                                </p>
                                @if($hasNominal)
                                    <button type="button"
                                            x-on:click="copy(@js($digits($ba->bank_account_sub_total)), '{{ $nomKey }}')"
                                            title="Salin nominal (angka saja)"
                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-1.5 py-1 text-sm font-medium text-gray-500 transition hover:text-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-white">
                                        <span x-show="copied !== '{{ $nomKey }}'" class="inline-flex items-center gap-1.5">
                                            <x-filament::icon icon="heroicon-o-clipboard-document" class="h-4 w-4" />
                                            Salin
                                        </span>
                                        <span x-show="copied === '{{ $nomKey }}'" x-cloak class="inline-flex items-center gap-1.5 text-success-600 dark:text-success-400">
                                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                                            Tersalin
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($bt)
                        {{-- Detail transfer --}}
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 border-t border-gray-200 pt-4 dark:border-white/10">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Tanggal</dt>
                                <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                                    {{ $bt->bank_transfer_dijalankan_tanggal ? \Carbon\Carbon::parse($bt->bank_transfer_dijalankan_tanggal)->format('d M Y') : '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Metode</dt>
                                <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">{{ $bt->bank_transfer_melalui ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Rekening sumber</dt>
                                <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                                    {{ trim(($bt->BankAsal?->bank_name ?? '-') . ' ' . $maskRek($bt->BankAsal?->no_rekening)) }}
                                </dd>
                            </div>
                        </dl>

                        {{-- File bukti --}}
                        <a href="{{ route('bukti_tf.download', ['file' => $fileName]) }}"
                           target="_blank" rel="noopener"
                           title="{{ $fileName }}"
                           class="flex items-center gap-4 rounded-2xl px-5 py-4 text-base ring-1 ring-gray-950/10 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:ring-white/10 dark:hover:bg-white/5">
                            <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6 shrink-0 text-gray-400" />
                            <span class="min-w-0 flex-1 truncate text-gray-800 dark:text-gray-200">{{ $fileName }}</span>
                            @if($bt->eagleTreasurer?->name)
                                <span class="hidden shrink-0 text-sm text-gray-500 sm:inline dark:text-gray-400">oleh {{ $bt->eagleTreasurer->name }}</span>
                            @endif
                            <span class="inline-flex shrink-0 items-center gap-1 font-medium text-gray-950 dark:text-white">
                                Buka
                                <x-filament::icon icon="heroicon-m-arrow-up-right" class="h-4 w-4" />
                            </span>
                        </a>
                    @else
                        <div class="rounded-2xl border border-dashed border-gray-300 px-5 py-4 text-base text-gray-500 dark:border-white/20 dark:text-gray-400">
                            Bukti transfer belum diunggah. Gunakan tombol
                            <span class="font-semibold text-gray-950 dark:text-white">Upload Bukti</span>
                            pada baris submission.
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>