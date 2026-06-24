<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Lógica de negocio de clientes (perfil y documentos).
 * Ver docs/05_MODULES.md (Customers).
 */
class CustomerService
{
    /**
     * Disco privado para documentos. Ver docs/11_SECURITY.md (§3).
     */
    private const DISK = 'local';

    public function createForUser(User $user): Customer
    {
        return $user->customer()->firstOrCreate([], [
            'verification_status' => VerificationStatus::Unverified->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        $customer->fill($data)->save();

        return $customer->refresh();
    }

    public function storeDocument(Customer $customer, string $type, UploadedFile $file): CustomerDocument
    {
        $path = $file->store("documents/{$customer->id}", self::DISK);

        return $customer->documents()->create([
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => DocumentStatus::Pending->value,
        ]);
    }
}
