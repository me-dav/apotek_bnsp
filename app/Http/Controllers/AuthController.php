<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            Log::warning('Login gagal', ['username' => $data['username'], 'ip' => $request->ip()]);

            return back()
                ->withErrors(['username' => 'Username atau kata sandi salah.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        Log::info('Login berhasil', ['username' => $data['username'], 'ip' => $request->ip()]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}