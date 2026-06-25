<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $deals = Vehicle::rentable()
            ->with('primaryImage')
            ->latest()
            ->limit(8)
            ->get();

        return view('client.home', compact('deals'));
    }
}
