<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

/**
 * Fotos de vehículos (disco público para catálogo). Ver docs/04_DATABASE_SCHEMA.md (#7).
 */
class VehicleImageController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'image' => ['required', File::image()->max(5 * 1024)],
            'is_primary' => ['nullable', 'boolean'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store("vehicles/{$vehicle->id}", 'public');

        // La primera imagen del vehículo es principal por defecto.
        $makePrimary = $request->boolean('is_primary') || $vehicle->images()->count() === 0;

        if ($makePrimary) {
            $vehicle->images()->update(['is_primary' => false]);
        }

        $image = $vehicle->images()->create([
            'path' => $path,
            'is_primary' => $makePrimary,
            'sort_order' => $vehicle->images()->max('sort_order') + 1,
            'alt' => $request->string('alt')->toString() ?: null,
        ]);

        return (new VehicleImageResource($image))->response()->setStatusCode(201);
    }

    public function setPrimary(Vehicle $vehicle, VehicleImage $image): VehicleImageResource
    {
        abort_unless($image->vehicle_id === $vehicle->id, 404);

        $vehicle->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return new VehicleImageResource($image->refresh());
    }

    public function destroy(Vehicle $vehicle, VehicleImage $image): JsonResponse
    {
        abort_unless($image->vehicle_id === $vehicle->id, 404);

        $image->delete();

        return response()->json([], 204);
    }
}
