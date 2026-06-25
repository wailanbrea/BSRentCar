<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · RentCar Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans bg-navy">
<div class="min-h-full flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="inline-block w-4 h-4 rotate-45 bg-primary rounded-sm"></span>
            <span class="font-semibold text-white text-2xl">RentCar</span>
        </div>
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-xl font-semibold text-slate-800 mb-1">Panel administrativo</h1>
            <p class="text-sm text-slate-500 mb-6">Inicia sesión para continuar.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary focus:ring-primary">
                    Recordarme
                </label>
                <button type="submit"
                        class="w-full rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-2.5 transition">
                    Entrar
                </button>
            </form>
        </div>
        <p class="text-center text-slate-400 text-xs mt-6">RentCar E-Commerce · Panel administrativo</p>
    </div>
</div>
</body>
</html>
