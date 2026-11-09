<div>
    <a href="{{ route('stocks.index') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-blue-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar stok
    </a>

    @if ($errors->has('adjust'))
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('adjust') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @if ($stock->coverImageUrl())
                    <img src="{{ $stock->coverImageUrl() }}" alt="Cover {{ $stock->name }}" class="aspect-square w-full object-cover">
                @else
                    <div class="flex aspect-square w-full flex-col items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 text-slate-300">
                        <svg class="h-16 w-16" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="mt-2 text-xs font-medium">Belum ada foto cover</p>
                    </div>
                @endif

                <div class="space-y-3 p-4">
                    <label class="block cursor-pointer rounded-lg border border-dashed border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-600 hover:border-blue-400 hover:text-blue-600">
                        <span wire:loading.remove wire:target="coverPhoto">{{ $stock->coverImageUrl() ? 'Ganti Foto Cover' : 'Unggah Foto Cover' }}</span>
                        <span wire:loading wire:target="coverPhoto">Mengunggah...</span>
                        <input type="file" wire:model="coverPhoto" accept="image/*" class="hidden">
                    </label>
                    @error('coverPhoto') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if ($stock->coverImageUrl())
                        <button wire:click="confirmCoverDeletion" class="w-full rounded-lg border border-rose-200 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus Foto Cover</button>
                    @endif
                    <p class="text-center text-[10px] text-slate-600">JPG, PNG, WEBP maks. 5 MB</p>
                </div>
            </div>
        </div>

        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $stock->status->badgeClass() }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $stock->status->dotClass() }}"></span>
                                {{ $stock->status->label() }}
                            </span>
                            @if ($stock->sku)
                                <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-600">{{ $stock->sku }}</span>
                            @endif
                        </div>
                        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $stock->name }}</h1>
                        <p class="mt-1 text-xs text-slate-600">Diperbarui {{ $stock->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="openEditModal" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                        <button wire:click="confirmDeletion" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-100">Hapus</button>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Harga</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $stock->price !== null ? $stock->formattedPrice() : '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Min. Stok</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ number_format($stock->min_stock) }} {{ $stock->unit }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Satuan</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $stock->unit }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Status</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $stock->status->label() }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center justify-between gap-4 rounded-2xl border border-blue-100 bg-blue-50/60 p-5 sm:flex-row">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Jumlah Stok</p>
                        <p class="mt-1 text-4xl font-extrabold tracking-tight text-blue-900">{{ number_format($stock->quantity) }} <span class="text-base font-semibold text-blue-500">{{ $stock->unit }}</span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="adjustQuantity(-1)" class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-200 bg-white text-lg font-bold text-blue-700 hover:bg-blue-100">−</button>
                        <button wire:click="adjustQuantity(1)" class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white hover:bg-blue-700">+</button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600">Deskripsi</h2>
                @if ($stock->description)
                    <div class="markdown-body mt-3 text-sm text-slate-700">
                        {!! \Illuminate\Support\Str::markdown($stock->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                @else
                    <p class="mt-3 text-sm italic text-slate-600">Belum ada deskripsi untuk barang ini.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Edit modal --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 py-10">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-slate-900">Edit Stok</h2>
                    <button wire:click="closeEditModal" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="saveStock" class="space-y-4 px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Barang <span class="text-rose-500">*</span></label>
                            <input wire:model="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">SKU</label>
                            <input wire:model="sku" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Jumlah <span class="text-rose-500">*</span></label>
                            <input wire:model="quantity" type="number" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Satuan <span class="text-rose-500">*</span></label>
                            <input wire:model="unit" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Harga (Rp)</label>
                            <input wire:model="price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Minimal Stok <span class="text-rose-500">*</span></label>
                            <input wire:model="minStock" type="number" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @error('minStock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Deskripsi (Markdown)</label>
                        <div x-data="{ tab: 'write' }" class="overflow-hidden rounded-lg border border-slate-300">
                            <div class="flex items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                                <button type="button" @click="tab = 'write'" :class="tab === 'write' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600'" class="rounded px-2.5 py-1 text-xs font-semibold">Tulis</button>
                                <button type="button" @click="tab = 'preview'; $nextTick(() => window.renderMarkdownPreview('detail-description', 'detail-description-preview'))" :class="tab === 'preview' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600'" class="rounded px-2.5 py-1 text-xs font-semibold">Pratinjau</button>
                            </div>
                            <textarea id="detail-description" wire:model="description" rows="6" x-show="tab === 'write'" class="w-full resize-y border-0 px-3 py-2 font-mono text-sm focus:ring-0"></textarea>
                            <div id="detail-description-preview" x-show="tab === 'preview'" x-cloak class="markdown-body min-h-32 px-3 py-2 text-sm"></div>
                        </div>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="closeEditModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete stock --}}
    @if ($confirmingDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900">Hapus "{{ $stock->name }}"?</h3>
                <p class="mt-1 text-sm text-slate-600">Stok dan foto cover akan dihapus permanen.</p>
                <div class="mt-5 flex gap-2">
                    <button wire:click="cancelDeletion" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button wire:click="deleteStock" class="flex-1 rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white hover:bg-rose-700">Hapus</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete cover --}}
    @if ($confirmingCoverDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900">Hapus foto cover?</h3>
                <p class="mt-1 text-sm text-slate-600">Foto akan dihapus dari penyimpanan.</p>
                <div class="mt-5 flex gap-2">
                    <button wire:click="cancelCoverDeletion" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button wire:click="deleteCoverPhoto" class="flex-1 rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white hover:bg-rose-700">Hapus Foto</button>
                </div>
            </div>
        </div>
    @endif
</div>
