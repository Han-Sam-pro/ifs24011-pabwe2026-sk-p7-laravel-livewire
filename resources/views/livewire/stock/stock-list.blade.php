<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Inventaris</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Stok Barang</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola persediaan, pantau status, dan sesuaikan jumlah stok Anda.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="exportExcel" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Ekspor Excel
            </button>
            <button wire:click="openImportModal" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3m0 0 4 4m-4-4L8 7M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Impor
            </button>
            <button wire:click="openAiModal" class="inline-flex items-center gap-2 rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 shadow-sm transition hover:bg-violet-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"/></svg>
                Generate AI
            </button>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Tambah Stok
            </button>
        </div>
    </div>

    @if ($errors->has('adjust'))
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('adjust') }}</div>
    @endif

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Total Barang</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($counts['total']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Stok Menipis</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ number_format($counts['low']) }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Stok Habis</p>
            <p class="mt-1 text-2xl font-extrabold text-rose-700">{{ number_format($counts['out']) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau SKU..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
            <select wire:model.live="statusFilter" aria-label="Filter status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none sm:w-48">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if ($stocks->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h2 class="mt-3 text-sm font-semibold text-slate-900">Belum ada stok</h2>
                <p class="mt-1 text-sm text-slate-600">Tambahkan barang pertama Anda atau impor dari file Excel.</p>
            </div>
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
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stocks as $stock)
                            <tr wire:key="stock-{{ $stock->id }}" class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($stock->coverImageUrl())
                                            <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('stocks.show', $stock) }}" class="block truncate font-semibold text-slate-900 hover:text-blue-600">{{ $stock->name }}</a>
                                            <p class="text-xs text-slate-600">Min. {{ $stock->min_stock }} {{ $stock->unit }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $stock->sku ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="adjustQuantity({{ $stock->id }}, -1)" title="Kurangi stok" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:border-rose-400 hover:text-rose-600">−</button>
                                        <span class="min-w-10 text-center font-bold text-slate-900">{{ number_format($stock->quantity) }}</span>
                                        <button wire:click="adjustQuantity({{ $stock->id }}, 1)" title="Tambah stok" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:border-emerald-400 hover:text-emerald-600">+</button>
                                    </div>
                                    <p class="mt-1 text-center text-[11px] text-slate-600">{{ $stock->unit }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $stock->price !== null ? $stock->formattedPrice() : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $stock->status->badgeClass() }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $stock->status->dotClass() }}"></span>
                                        {{ $stock->status->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('stocks.show', $stock) }}" class="rounded-md px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">Detail</a>
                                    <button wire:click="openEditModal({{ $stock->id }})" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</button>
                                    <button wire:click="confirmDelete({{ $stock->id }})" class="rounded-md px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-3">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    {{-- Form modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 py-10">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Edit Stok' : 'Tambah Stok Baru' }}</h2>
                    <button type="button" aria-label="Tutup" wire:click="closeFormModal" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="saveStock" class="space-y-4 px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f11964">Nama Barang <span class="text-rose-500">*</span></label>
                            <input wire:model="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f11964">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f12503">SKU</label>
                            <input wire:model="sku" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f12503">
                            @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f13045">Jumlah <span class="text-rose-500">*</span></label>
                            <input wire:model="quantity" type="number" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f13045">
                            @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f13597">Satuan <span class="text-rose-500">*</span></label>
                            <input wire:model="unit" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f13597">
                            @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f14072">Harga (Rp)</label>
                            <input wire:model="price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f14072">
                            @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f14706">Minimal Stok <span class="text-rose-500">*</span></label>
                            <input wire:model="minStock" type="number" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" id="stock-list-blade-f14706">
                            <p class="mt-1 text-[11px] text-slate-600">Status "Stok Menipis" muncul saat jumlah ≤ minimal stok.</p>
                            @error('minStock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="stock-description" class="mb-1 block text-xs font-semibold text-slate-600">Deskripsi (Markdown)</label>
                        <div x-data="{ tab: 'write' }" class="overflow-hidden rounded-lg border border-slate-300">
                            <div class="flex items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                                <button type="button" @click="tab = 'write'" :class="tab === 'write' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600'" class="rounded px-2.5 py-1 text-xs font-semibold">Tulis</button>
                                <button type="button" @click="tab = 'preview'; $nextTick(() => window.renderMarkdownPreview('stock-description', 'stock-description-preview'))" :class="tab === 'preview' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600'" class="rounded px-2.5 py-1 text-xs font-semibold">Pratinjau</button>
                                <span class="ml-auto text-[10px] text-slate-600">Markdown didukung</span>
                            </div>
                            <textarea id="stock-description" wire:model="description" rows="6" x-show="tab === 'write'" placeholder="Tulis deskripsi barang... **tebal**, - daftar, # judul" class="w-full resize-y border-0 px-3 py-2 font-mono text-sm focus:ring-0"></textarea>
                            <div id="stock-description-preview" x-show="tab === 'preview'" x-cloak class="markdown-body min-h-32 px-3 py-2 text-sm"></div>
                        </div>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="closeFormModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                            {{ $editingId ? 'Simpan Perubahan' : 'Tambah Stok' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if ($confirmingDeletionId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.6 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                </div>
                <h3 class="mt-4 text-center text-lg font-bold text-slate-900">Hapus stok ini?</h3>
                <p class="mt-1 text-center text-sm text-slate-600">Data dan gambar sampul akan dihapus permanen.</p>
                <div class="mt-5 flex gap-2">
                    <button wire:click="cancelDelete" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button wire:click="deleteStock" class="flex-1 rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white hover:bg-rose-700">Hapus</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-slate-900">Impor Stok dari Excel</h2>
                    <button type="button" aria-label="Tutup" wire:click="closeImportModal" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <p class="text-sm text-slate-600">Kolom yang dibaca: <strong>Nama, SKU, Deskripsi, Qty, Satuan, Harga, Min Stok</strong>. Status dihitung otomatis dari Qty dan Min Stok.</p>
                    <input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv" class="block w-full rounded-lg border border-slate-300 p-2 text-sm">
                    @error('importFile') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if ($importErrors !== [])
                        <div class="max-h-40 overflow-y-auto rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                            <p class="mb-1 font-bold">{{ count($importErrors) }} baris bermasalah:</p>
                            <ul class="list-disc space-y-0.5 pl-4">
                                @foreach ($importErrors as $importError)
                                    <li>{{ $importError }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="closeImportModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button wire:click="importExcel" wire:loading.attr="disabled" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="importExcel">Impor Sekarang</span>
                        <span wire:loading wire:target="importExcel">Mengimpor...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- AI modal --}}
    @if ($showAiModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 py-10">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Generate Stok dengan AI</h2>
                        <p class="text-xs text-slate-600">Deskripsikan kebutuhan inventaris, AI akan mengusulkan item.</p>
                    </div>
                    <button type="button" aria-label="Tutup" wire:click="closeAiModal" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f22664">Kebutuhan stok</label>
                        <textarea wire:model="aiPrompt" rows="3" placeholder="Contoh: perlengkapan alat tulis kantor untuk 10 karyawan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" id="stock-list-blade-f22664"></textarea>
                        @error('aiPrompt') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end gap-3">
                        <div class="w-32">
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="stock-list-blade-f23270">Jumlah item</label>
                            <input wire:model="aiCount" type="number" min="1" max="10" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" id="stock-list-blade-f23270">
                        </div>
                        <button wire:click="generateWithAi" wire:loading.attr="disabled" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="generateWithAi">Generate</span>
                            <span wire:loading wire:target="generateWithAi">Memproses...</span>
                        </button>
                    </div>
                    @error('aiCount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if ($aiError !== '')
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $aiError }}</div>
                    @endif

                    @if ($aiItems !== [])
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-left font-bold text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2"></th>
                                        <th class="px-3 py-2">Nama</th>
                                        <th class="px-3 py-2">Qty</th>
                                        <th class="px-3 py-2">Satuan</th>
                                        <th class="px-3 py-2">Min</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($aiItems as $index => $item)
                                        <tr wire:key="ai-item-{{ $index }}">
                                            <td class="px-3 py-2"><input type="checkbox" wire:model="aiSelected" value="{{ $index }}" class="rounded border-slate-300 text-blue-600"></td>
                                            <td class="px-3 py-2">
                                                <p class="font-semibold text-slate-800">{{ $item['name'] }}</p>
                                                @if (! empty($item['sku']))<p class="font-mono text-[10px] text-slate-600">{{ $item['sku'] }}</p>@endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-600">{{ $item['quantity'] }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $item['unit'] }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $item['min_stock'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('aiSelected') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="closeAiModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button wire:click="saveAiGenerated" @disabled($aiItems === []) class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40">Simpan Item Terpilih</button>
                </div>
            </div>
        </div>
    @endif
</div>
