<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleInspectionResource;
use App\Http\Resources\InspectionPhotoResource;
use App\Models\Reservation;
use App\Models\VehicleInspection;
use App\Services\InspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInspectionController extends Controller
{
    public function __construct(protected readonly InspectionService $inspectionService)
    {
    }

    /**
     * Registra una nueva inspección para una reservación.
     */
    public function store(Request $request, Reservation $reservation): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:initial,final',
            'fuel_level' => 'required|string|max:50',
            'mileage' => 'required|integer|min:0',
            'damages' => 'nullable|array',
            'notes' => 'nullable|string',
            'accepted_by_customer' => 'boolean',
            'signature' => 'nullable|string', // base64 string
            'photos' => 'nullable|array',
            'photos.*.file' => 'required_with:photos|image|max:5120',
            'photos.*.position' => 'required_with:photos|string|in:front,back,left,right,interior,damage,other',
            'photos.*.note' => 'nullable|string|max:255',
        ]);

        try {
            $inspection = $this->inspectionService->createInspection($reservation, $request->all(), $request->user());
            return (new VehicleInspectionResource($inspection->load('photos')))
                ->response()
                ->setStatusCode(201);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
    }

    /**
     * Sube una foto de evidencia para una inspección existente.
     */
    public function uploadPhoto(Request $request, VehicleInspection $inspection): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120',
            'position' => 'required|string|in:front,back,left,right,interior,damage,other',
            'note' => 'nullable|string|max:255',
        ]);

        $photo = $this->inspectionService->uploadPhoto(
            $inspection,
            $request->file('file'),
            $request->input('position'),
            $request->input('note')
        );

        return (new InspectionPhotoResource($photo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Obtiene los detalles de una inspección con sus fotos.
     */
    public function show(VehicleInspection $inspection): VehicleInspectionResource
    {
        return new VehicleInspectionResource($inspection->load('photos'));
    }
}
