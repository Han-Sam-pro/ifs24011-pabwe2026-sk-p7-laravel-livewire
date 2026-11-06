<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Administrator</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Kelola Pengguna</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau seluruh pengguna dan stok barang mereka dalam mode lihat saja.</p>
        </div>
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, username, email..." class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3 text-center">Total Stok</th>
                        <th class="px-5 py-3 text-center">Menipis</th>
                        <th class="px-5 py-3 text-center">Habis</th>
                        <th class="px-5 py-3">Bergabung</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="font-mono text-[11px] text-slate-400">{{ '@'.$user->username }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $user->isAdmin() ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center font-bold text-slate-900">{{ number_format($user->stocks_count) }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="{{ $user->low_stock_count > 0 ? 'font-bold text-amber-600' : 'text-slate-400' }}">{{ number_format($user->low_stock_count) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="{{ $user->out_of_stock_count > 0 ? 'font-bold text-rose-600' : 'text-slate-400' }}">{{ number_format($user->out_of_stock_count) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <a href="{{ route('admin.users.stocks', $user) }}" class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">Lihat Stok</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-slate-500">Tidak ada pengguna yang cocok dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    </div>
</div>
