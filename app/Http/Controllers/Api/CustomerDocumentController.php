<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreDocumentRequest;
use App\Http\Resources\CustomerDocumentResource;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Documentos del cliente (storage privado). Contratos: docs/06_API_CONTRACTS.md.
 */
class CustomerDocumentController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $customer = $this->customers->createForUser($request->user());

        return CustomerDocumentResource::collection(
            $customer->documents()->latest()->get()
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $customer = $this->customers->createForUser($request->user());

        $document = $this->customers->storeDocument(
            $customer,
            $request->string('type'),
            $request->file('file'),
        );

        return (new CustomerDocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }
}
