<?php

namespace App\Modules\Seller\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seller\Http\Requests\StoreUpdateRequest;
use App\Modules\Seller\Http\Resources\StoreResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerStoreController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;

        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Store not found', 404, null, 'STORE_NOT_FOUND');
        }

        return $this->successResponse(new StoreResource($seller->store), 'Store details retrieved');
    }

    public function update(StoreUpdateRequest $request): JsonResponse
    {
        $seller = $request->user()->seller;

        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Store not found', 404, null, 'STORE_NOT_FOUND');
        }

        $store = $seller->store;
        $store->update($request->validated());

        return $this->successResponse(new StoreResource($store), 'Store updated successfully');
    }
}
