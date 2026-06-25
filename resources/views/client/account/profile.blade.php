@extends('layouts.public')
@section('title', 'Mi perfil')

@section('content')
<!-- Leaflet Map Assets -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

@include('client.account.partials.header', ['title' => 'Mi perfil'])

<section class="py-10 bg-slate-50 min-h-[40vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid lg:grid-cols-3 gap-6">
        
        {{-- Formulario de Datos Personales --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 text-lg mb-4">Datos personales</h2>
            
            <form method="POST" action="{{ route('account.profile.update') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf 
                @method('PUT')
                
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nombre *</label>
                    <input name="first_name" value="{{ old('first_name', $customer->first_name) }}" required class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Apellido *</label>
                    <input name="last_name" value="{{ old('last_name', $customer->last_name) }}" required class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Fecha de nacimiento</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate', $customer->birthdate?->toDateString()) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>
                
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Licencia de Conducir</label>
                    <input name="license_number" value="{{ old('license_number', $customer->license_number) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">País</label>
                    <input name="country" id="input-country" value="{{ old('country', $customer->country) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Ciudad</label>
                    <input name="city" id="input-city" value="{{ old('city', $customer->city) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm text-slate-600">Dirección</label>
                        <button type="button" id="btn-geolocation" class="rounded-full bg-slate-800 text-white text-xs px-3 py-1.5 hover:bg-slate-900 flex items-center gap-1 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Obtener ubicación actual
                        </button>
                    </div>
                    <input name="address" id="input-address" value="{{ old('address', $customer->address) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Ubícate en el mapa (arrastra el marcador o haz clic)</label>
                    <div id="map" class="h-64 w-full rounded-xl border border-slate-200 mt-2 z-10"></div>
                </div>
                
                <div class="sm:col-span-2 mt-2">
                    <button class="rounded-full bg-primary hover:bg-primary-dark text-white font-medium px-8 py-3 transition">Guardar datos</button>
                </div>
            </form>
        </div>
        
        {{-- Documentación --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-semibold text-slate-800 text-lg mb-4">Documentos subidos</h2>
                <div class="space-y-4 mb-6">
                    @forelse ($customer->documents as $doc)
                        <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-medium text-slate-700 capitalize text-sm">
                                    {{ str_replace('_',' ', $doc->type->value) }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $doc->status->value === 'approved' ? 'bg-emerald-50 text-emerald-600' : ($doc->status->value === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}">
                                    {{ ucfirst($doc->status->value) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 truncate mb-1" title="{{ $doc->original_name }}">
                                {{ $doc->original_name }}
                            </p>
                            <p class="text-[10px] text-slate-400">
                                Subido el {{ $doc->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-slate-400 text-sm">No has subido ningún documento aún.</p>
                    @endforelse
                </div>
                
                <div class="border-t border-slate-100 pt-4">
                    <h3 class="font-semibold text-slate-700 text-sm mb-3">Subir nuevo documento</h3>
                    <form method="POST" action="{{ route('account.documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <select name="type" class="w-full rounded-xl border-slate-300 text-sm px-3 py-2.5 focus:border-primary focus:ring-primary">
                            <option value="license">Licencia de conducir</option>
                            <option value="id_front">Cédula (frente)</option>
                            <option value="id_back">Cédula (reverso)</option>
                            <option value="proof_address">Comprobante de domicilio</option>
                        </select>
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-primary file:text-white file:px-4 file:py-2">
                        <button class="w-full rounded-full bg-slate-800 hover:bg-slate-900 text-white text-sm py-2.5 transition">Subir documento</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
    let map, marker;
    const defaultLat = 18.4861;
    const defaultLon = -69.9312;

    function initMap() {
        // Inicializar Leaflet
        map = L.map('map').setView([defaultLat, defaultLon], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // Crear marcador arrastrable
        marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);

        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            reverseGeocode(position.lat, position.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // Intentar geocodificar dirección guardada para centrar el mapa
        const savedAddress = "{{ $customer->address }}";
        const savedCity = "{{ $customer->city }}";
        const savedCountry = "{{ $customer->country }}";

        if (savedAddress || savedCity) {
            const query = [savedAddress, savedCity, savedCountry].filter(Boolean).join(', ');
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(res => res.json())
                .then(results => {
                    if (results && results.length > 0) {
                        const lat = parseFloat(results[0].lat);
                        const lon = parseFloat(results[0].lon);
                        map.setView([lat, lon], 15);
                        marker.setLatLng([lat, lon]);
                    }
                })
                .catch(err => console.error(err));
        }
    }

    function reverseGeocode(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
            headers: {
                'Accept-Language': 'es'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.address) {
                const addr = data.address;
                const street = addr.road || addr.suburb || addr.neighbourhood || '';
                const house = addr.house_number || '';
                const fullAddress = (street ? (street + ' ' + house).trim() : data.display_name) || '';
                
                document.getElementById('input-address').value = fullAddress;
                document.getElementById('input-city').value = addr.city || addr.town || addr.village || addr.county || '';
                document.getElementById('input-country').value = addr.country || '';
            }
        })
        .catch(err => console.error(err));
    }

    document.getElementById('btn-geolocation').addEventListener('click', function () {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                reverseGeocode(lat, lng);
            }, function (error) {
                alert('No se pudo obtener la ubicación actual: ' + error.message);
            });
        } else {
            alert('Geolocalización no soportada en este navegador.');
        }
    });

    window.addEventListener('load', initMap);
</script>
@endsection
