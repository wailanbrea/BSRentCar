<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::with(['reservation', 'customer'])
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }
}
