<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-neutral-50 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg border border-neutral-200 bg-white p-8">
            <h1 class="text-xl font-semibold">Apotek Multi Cabang</h1>
            <p class="mt-1 text-sm text-neutral-500">Masuk untuk melanjutkan</p>

            <form method="POST" action="{{ route('login.proses') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" class="input" required autofocus>
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label" for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" class="input" required>
                </div>
                <button class="btn w-full">Masuk</button>
            </form>

            @if (app()->environment('local'))
                <p class="mt-6 border-t border-neutral-100 pt-4 text-xs text-neutral-500">
                    Akun demo (kata sandi: <b>password</b>): admin, apoteker1, kasir1, kasir2
                </p>
            @endif
        </div>
    </div>
</body>

</html>