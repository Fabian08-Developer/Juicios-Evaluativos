<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // Mostrar formulario de login
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // Procesar login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // ── Protección contra fuerza bruta ────────────────────────────────
        // Dos contadores: por correo+IP (ataque dirigido a una cuenta) y por IP
        // (ataque que va cambiando de correo).
        $keyCorreo = 'login:' . Str::lower($credentials['email']) . '|' . $request->ip();
        $keyIp     = 'login-ip:' . $request->ip();
        $maxCorreo = (int) config('sena.login.max_intentos_por_correo');
        $maxIp     = (int) config('sena.login.max_intentos_por_ip');
        $bloqueo   = (int) config('sena.login.bloqueo_segundos');

        foreach ([[$keyCorreo, $maxCorreo], [$keyIp, $maxIp]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $segundos = RateLimiter::availableIn($key);
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => "Demasiados intentos fallidos. Intenta de nuevo en {$segundos} segundos."]);
            }
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($keyCorreo);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($keyCorreo, $bloqueo);
        RateLimiter::hit($keyIp, $bloqueo);

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ]);
    }

    // Cerrar sesión
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
