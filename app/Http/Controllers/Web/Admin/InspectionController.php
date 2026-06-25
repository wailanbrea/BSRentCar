<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleInspection;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(): View
    {
        $inspections = VehicleInspection::with(['reservation', 'vehicle'])
            ->withCount('photos')
            ->latest()
            ->paginate(20);

        return view('admin.inspections.index', compact('inspections'));
    }
}
