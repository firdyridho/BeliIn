<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\AddressRequest;
use App\Modules\Auth\Http\Resources\AddressResource;
use App\Modules\Auth\Models\UserAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();

        return $this->successResponse(AddressResource::collection($addresses), 'Addresses retrieved successfully');
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        return DB::transaction(function () use ($validated, $user) {
            if (! empty($validated['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            } else {
                // If this is the user's first address, set as default
                if ($user->addresses()->count() === 0) {
                    $validated['is_default'] = true;
                }
            }

            $address = $user->addresses()->create($validated);

            return $this->successResponse(new AddressResource($address), 'Address created successfully', 201);
        });
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        return $this->successResponse(new AddressResource($address), 'Address details retrieved');
    }

    public function update(AddressRequest $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $validated = $request->validated();
        $user = $request->user();

        return DB::transaction(function () use ($validated, $address, $user) {
            if (! empty($validated['is_default'])) {
                $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update($validated);

            return $this->successResponse(new AddressResource($address), 'Address updated successfully');
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return $this->successResponse(null, 'Address deleted successfully');
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($user, $address) {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return $this->successResponse(new AddressResource($address), 'Default address updated successfully');
    }
}
