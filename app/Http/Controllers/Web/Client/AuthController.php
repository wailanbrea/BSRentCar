<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Autenticación web del cliente (sesión, guard web). Distinta del panel admin.
 */
class AuthController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function showLogin(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('account.dashboard') : view('client.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciales inválidas.']);
        }

        $request->session()->regenerate();

        // Admin/staff van al panel; clientes a su cuenta.
        if (Auth::user()->hasAnyRole(['admin', 'staff'])) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function showRegister(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('account.dashboard') : view('client.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole('customer');
        $this->customers->createForUser($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')
            ->with('status', '¡Bienvenido! Completa tu perfil y sube tu licencia para poder reservar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
