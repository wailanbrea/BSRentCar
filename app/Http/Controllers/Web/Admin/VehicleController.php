<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::with('primaryImage')->latest()->paginate(12);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('admin.vehicles.create', $this->formData());
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('status', 'Vehículo creado correctamente.');
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.edit', array_merge($this->formData(), [
            'vehicle' => $vehicle->load('images'),
        ]));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('status', 'Cambios guardados.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehículo archivado.');
    }

    public function uploadImage(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $request->validate([
            'image' => ['required', File::image()->max(5 * 1024)],
        ]);

        $path = $request->file('image')->store("vehicles/{$vehicle->id}", 'public');
        $makePrimary = $vehicle->images()->count() === 0;

        if ($makePrimary) {
            $vehicle->images()->update(['is_primary' => false]);
        }

        $vehicle->images()->create([
            'path' => $path,
            'is_primary' => $makePrimary,
            'sort_order' => (int) $vehicle->images()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Foto subida.');
    }

    public function setPrimaryImage(Vehicle $vehicle, VehicleImage $image): RedirectResponse
    {
        abort_unless($image->vehicle_id === $vehicle->id, 404);

        $vehicle->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('status', 'Foto principal actualizada.');
    }

    public function deleteImage(Vehicle $vehicle, VehicleImage $image): RedirectResponse
    {
        abort_unless($image->vehicle_id === $vehicle->id, 404);
        $image->delete();

        return back()->with('status', 'Foto eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => VehicleCategory::cases(),
            'transmissions' => Transmission::cases(),
            'statuses' => VehicleStatus::cases(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
