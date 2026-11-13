<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-neutral-900 antialiased">
    @php
        $pengguna = auth()->user();
        $menu = [
            ['Dashboard', 'dashboard', 'dashboard'],
            ['Penjualan', 'penjualan.*', 'penjualan.index'],
            ['Stok', 'stok.*', 'stok.index'],
        ];
    @endphp

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="flex w-60 shrink-0 flex-col border-r border-neutral-200 bg-white">
            <div class="border-b border-neutral-200 px-5 py-4">
                <div class="font-semibold">Apotek Multi Cabang</div>
                {{-- Badge environment aktif (untuk demo perpindahan environment) --}}
                <span class="badge mt-2 uppercase">{{ app()->environment() }}</span>
            </div>

            <nav class="flex-1 space-y-1 p-3">
                @foreach ($menu as [$label, $pola, $rute])
                    <a href="{{ route($rute) }}"
                        class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($pola) ? 'bg-neutral-100 font-medium text-black' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-neutral-200 p-4 text-sm">
                <div class="font-medium">{{ $pengguna->nama_lengkap }}</div>
                <div class="text-neutral-500">
                    {{ $pengguna->labelRole() }} &middot; {{ $pengguna->cabang?->nama ?? 'Semua cabang' }}
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="btn-outline w-full">Keluar</button>
                </form>
            </div>
        </aside>

        {{-- Konten --}}
        <main class="min-w-0 flex-1">
            <header class="border-b border-neutral-200 px-8 py-4">
                <h1 class="text-lg font-semibold">@yield('title')</h1>
            </header>

            <div class="p-8">
                @if (session('sukses'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('sukses') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        @foreach ($errors->all() as $pesan)
                            <div>{{ $pesan }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>