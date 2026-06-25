<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::with('user')
            ->withCount('reservations')
            ->latest()
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['user', 'documents', 'reservations.vehicle']);

        return view('admin.customers.show', compact('customer'));
    }
}
