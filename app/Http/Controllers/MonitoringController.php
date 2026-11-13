<?php

namespace App\Http\Controllers;

use App\Services\ResourceMonitor;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    public function index()
    {
        $this->pastikanAdmin();

        return view('monitoring.index');
    }

    public function data(ResourceMonitor $monitor)
    {
        $this->pastikanAdmin();

        return response()->json($monitor->ambil());
    }

    private function pastikanAdmin(): void
    {
        abort_unless(Auth::user()->isAdminPusat(), 403, 'Hanya Admin Pusat yang boleh membuka monitoring.');
    }
}