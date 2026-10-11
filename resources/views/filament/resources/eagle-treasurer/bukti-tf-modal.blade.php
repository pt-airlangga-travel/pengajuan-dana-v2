@php
    $items = ($record->BankAccounts ?? collect())->filter(fn ($ba) => $ba->BankTransfer && filled($ba->BankTransfer->transfer_attached_name));
@endphp

<div class="space-y-4">
    @if($items->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-white/20">
            <p class="text-sm font-medium text-gray-950 dark:text-white">Belum ada bukti transfer</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submission ini belum memiliki bukti transfer yang diunggah.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($items as $ba)
                @php
                    $bt = $ba->BankTransfer;
                    $fileName = basename($bt->transfer_attached_name);
                    $url = route('bukti_tf.download', ['file' => $fileName]);
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
                @endphp
                <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 p-5 dark:border-white/10">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $ba->Bank?->bank_name ?? '-' }}</p>
                            <p class="mt-0.5 truncate font-mono text-xs text-gray-500 dark:text-gray-400">{{ $ba->bank_account_number ?? '-' }} · {{ $ba->bank_account_owner ?? '-' }}</p>
                        </div>
                        <x-filament::badge color="success">Sudah TF</x-filament::badge>
                    </div>

                    @if($isImage)
                        <a href="{{ $url }}" target="_blank" rel="noopener" title="Buka gambar penuh">
                            <img src="{{ $url }}" alt="Bukti transfer {{ $fileName }}" loading="lazy"
                                 class="max-h-56 w-full rounded-xl object-cover ring-1 ring-gray-950/10 transition hover:opacity-90 dark:ring-white/10" />
                        </a>
                    @endif

                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 shrink-0 text-gray-400" />
                        <span class="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-300" title="{{ $fileName }}">{{ $fileName }}</span>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-sm dark:border-white/10">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Metode</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">{{ $bt->bank_transfer_melalui ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Tanggal</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">
                                {{ $bt->bank_transfer_dijalankan_tanggal ? \Carbon\Carbon::parse($bt->bank_transfer_dijalankan_tanggal)->format('d M Y') : '-' }}
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Sumber · Oleh</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">
                                {{ $bt->BankAsal?->bank_name ?? '-' }}{{ $bt->BankAsal?->no_rekening ? ' · ' . $bt->BankAsal->no_rekening : '' }}
                                @if($bt->eagleTreasurer?->name)
                                    <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $bt->eagleTreasurer->name }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <a href="{{ $url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-200">
                        Lihat / Unduh Bukti
                        <x-filament::icon icon="heroicon-m-arrow-up-right" class="h-4 w-4" />
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
