@extends('admin.layouts.app')
@section('title', 'Nuevo vehículo')

@section('content')
<div class="max-w-3xl bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
    <form method="POST" action="{{ route('admin.vehicles.store') }}">
        @csrf
        @include('admin.vehicles._form', ['submitLabel' => 'Crear vehículo'])
    </form>
</div>
@endsection
