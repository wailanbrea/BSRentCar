<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositTransaction;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(): View
    {
        $deposits = DepositTransaction::with('reservation')
            ->latest()
            ->paginate(20);

        return view('admin.deposits.index', compact('deposits'));
    }
}
