<?php

namespace App\Modules\Seller\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\Role;
use App\Modules\Seller\Http\Requests\SellerApplicationRequest;
use App\Modules\Seller\Http\Resources\SellerResource;
use App\Modules\Seller\Http\Resources\StoreResource;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\SellerVerification;
use App\Modules\Seller\Models\Store;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerApplicationController extends Controller
{
    use ApiResponse;

    public function apply(SellerApplicationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->seller()->exists()) {
            return $this->errorResponse('User already has a seller account or pending application', 409, null, 'SELLER_ALREADY_EXISTS');
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($user, $validated) {
            $seller = Seller::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'verification_level' => 'basic',
            ]);

            $store = Store::create([
                'seller_id' => $seller->id,
                'name' => $validated['store_name'],
                'slug' => Str::slug($validated['store_name']),
                'description' => $validated['store_description'] ?? null,
                'status' => 'pending',
            ]);

            if (! empty($validated['document_type'])) {
                SellerVerification::create([
                    'seller_id' => $seller->id,
                    'document_type' => $validated['document_type'],
                    'document_number' => $validated['document_number'] ?? null,
                    'document_path' => $validated['document_path'] ?? null,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            return $this->successResponse([
                'seller' => new SellerResource($seller),
                'store' => new StoreResource($store),
            ], 'Seller application submitted successfully', 201);
        });
    }

    public function status(Request $request): JsonResponse
    {
        $seller = $request->user()->seller()->with('store', 'verifications')->first();

        if (! $seller) {
            return $this->errorResponse('No seller application found', 404, null, 'SELLER_NOT_FOUND');
        }

        return $this->successResponse(new SellerResource($seller), 'Seller application status retrieved');
    }
}
