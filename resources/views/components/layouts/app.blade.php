<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Inventaris Stok' }}</title>
    @vite('resources/css/app.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .markdown-body { line-height: 1.7; }
        .markdown-body h1, .markdown-body h2, .markdown-body h3 { font-weight: 700; color: #0f172a; margin: 1em 0 .5em; }
        .markdown-body h1 { font-size: 1.5rem; }
        .markdown-body h2 { font-size: 1.25rem; }
        .markdown-body h3 { font-size: 1.1rem; }
        .markdown-body p { margin: .6em 0; }
        .markdown-body ul { list-style: disc; padding-left: 1.4em; margin: .6em 0; }
        .markdown-body ol { list-style: decimal; padding-left: 1.4em; margin: .6em 0; }
        .markdown-body code { background: #f1f5f9; padding: .1em .35em; border-radius: .25rem; font-size: .875em; }
        .markdown-body pre { background: #0f172a; color: #e2e8f0; padding: 1em; border-radius: .5rem; overflow-x: auto; }
        .markdown-body blockquote { border-left: 4px solid #cbd5e1; padding-left: 1em; color: #475569; }
        .markdown-body a { color: #2563eb; text-decoration: underline; }
        .markdown-body table { width: 100%; border-collapse: collapse; }
        .markdown-body th, .markdown-body td { border: 1px solid #e2e8f0; padding: .4em .6em; }
    </style>
    @livewireStyles
</head>
<body class="h-full text-slate-800 antialiased">
    @auth
        <div class="min-h-screen lg:flex">
            <aside class="hidden w-72 shrink-0 flex-col bg-slate-900 text-slate-300 lg:flex">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.users') : route('stocks.index') }}" class="flex items-center gap-3 px-6 py-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-900/40">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold tracking-wide text-white">INVENTARIS</span>
                        <span class="block text-[11px] uppercase tracking-widest text-slate-500">Stok Barang</span>
                    </span>
                </a>

                <nav class="mt-2 flex-1 space-y-1 px-3">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.users') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-1.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                            Kelola Pengguna
                        </a>
                    @endif
                    <a href="{{ route('stocks.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('stocks.*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Stok Barang
                    </a>
                    <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('profile') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 5a7 7 0 0 1 14 0"/></svg>
                        Profil Saya
                    </a>
                </nav>

                <div class="m-3 rounded-xl bg-slate-800/70 p-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="Foto profil" class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ '@'.auth()->user()->username }}</p>
                        </div>
                    </div>
                    <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ auth()->user()->isAdmin() ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300' }}">{{ auth()->user()->role }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-slate-700 py-1.5 text-xs font-semibold text-slate-300 transition hover:border-rose-500 hover:text-rose-400">Keluar</button>
                    </form>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-3 lg:hidden">
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.users') : route('stocks.index') }}" class="flex items-center gap-2 font-bold text-slate-900">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white">I</span>
                        Inventaris
                    </a>
                    <div class="flex items-center gap-3 text-xs">
                        <a href="{{ route('profile') }}" class="font-medium text-slate-600">Profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="font-medium text-rose-600">Keluar</button>
                        </form>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
                    @endif

                    {{ $slot }}
                </main>

                <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400">
                    Inventaris Stok &middot; PABWE 2026 P7 &middot; Laravel {{ app()->version() }} &amp; Livewire
                </footer>
            </div>
        </div>
    @else
        <div class="flex min-h-screen flex-col justify-between">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('login') }}" class="flex items-center gap-2.5 font-bold text-blue-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </span>
                        <span class="text-slate-900">Inventaris <span class="text-blue-600">Stok</span></span>
                    </a>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">Masuk</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">Daftar</a>
                    </div>
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center p-4">
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-400">
                Inventaris Stok &middot; PABWE 2026 P7
            </footer>
        </div>
    @endauth

    @livewireScripts
    <script>
        window.renderMarkdownPreview = function (sourceId, targetId) {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (!source || !target) return;
            const text = source.value || '';
            if (window.marked && window.DOMPurify) {
                target.innerHTML = window.DOMPurify.sanitize(window.marked.parse(text));
            } else {
                target.textContent = text;
            }
        };
    </script>
</body>
</html>
