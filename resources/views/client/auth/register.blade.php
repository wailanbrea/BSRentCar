@extends('layouts.public')
@section('title', 'Crear cuenta')

@section('content')
<section class="min-h-screen bg-navy flex items-center justify-center px-4 py-24">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-xl font-semibold text-slate-800 mb-1">Crear cuenta</h1>
        <p class="text-sm text-slate-500 mb-6">Regístrate para rentar tu próximo vehículo.</p>

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Correo</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" name="password" required
                       class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
            </div>
            <button class="w-full rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-2.5 transition">Crear cuenta</button>
        </form>
        <p class="text-sm text-slate-500 text-center mt-6">¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-primary hover:underline">Iniciar sesión</a>
        </p>
    </div>
</section>
@endsection
