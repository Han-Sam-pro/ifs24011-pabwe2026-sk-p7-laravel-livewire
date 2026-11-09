<div>
    <a href="{{ route('admin.users') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-blue-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke kelola pengguna
    </a>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Stok {{ $user->name }}</h1>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">Mode Admin</span>
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $user->email }} &middot; {{ '@'.$user->username }} &middot; hanya lihat</p>
        </div>
        <button wire:click="exportExcel" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            Ekspor Excel
        </button>
    </div>

    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" aria-label="Cari" placeholder="Cari nama atau SKU..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <select wire:model.live="statusFilter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm sm:w-48">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if ($stocks->isEmpty())
            <div class="px-6 py-16 text-center text-sm text-slate-600">Pengguna ini belum memiliki stok yang cocok.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3 text-center">Jumlah</th>
                            <th class="px-4 py-3">Harga</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stocks as $stock)
                            <tr wire:key="admin-stock-{{ $stock->id }}" class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($stock->coverImageUrl())
                                            <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $stock->name }}</p>
                                            <p class="text-[11px] text-slate-600">Min. {{ $stock->min_stock }} {{ $stock->unit }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $stock->sku ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center font-bold text-slate-900">{{ number_format($stock->quantity) }} <span class="text-[11px] font-normal text-slate-600">{{ $stock->unit }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $stock->price !== null ? $stock->formattedPrice() : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $stock->status->badgeClass() }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $stock->status->dotClass() }}"></span>
                                        {{ $stock->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $stocks->links() }}</div>
        @endif
    </div>
</div>
