<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

/**
 * Perfil del cliente autenticado. Contratos: docs/06_API_CONTRACTS.md (Customers).
 */
class CustomerProfileController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function show(Request $request): CustomerResource
    {
        $customer = $this->customers->createForUser($request->user());

        return new CustomerResource($customer->load('documents'));
    }

    public function update(UpdateProfileRequest $request): CustomerResource
    {
        $customer = $this->customers->createForUser($request->user());
        $customer = $this->customers->updateProfile($customer, $request->validated());

        return new CustomerResource($customer->load('documents'));
    }
}
