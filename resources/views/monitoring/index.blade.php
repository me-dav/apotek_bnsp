@extends('layouts.app')

@section('title', 'Monitoring Resource')

@section('content')
    <p class="mb-4 text-sm text-neutral-500">
        Diperbarui otomatis setiap 5 detik. Garis merah putus-putus adalah batas atas sebelum risiko gagal.
    </p>

    <div id="monitoring" data-url="{{ route('monitoring.data') }}" class="grid gap-4 md:grid-cols-2"></div>
@endsection