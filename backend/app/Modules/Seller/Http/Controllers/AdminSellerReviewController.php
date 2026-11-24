<?php

namespace App\Modules\Seller\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\Role;
use App\Modules\Seller\Http\Resources\SellerResource;
use App\Modules\Seller\Models\Seller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSellerReviewController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $sellers = Seller::with('user', 'store', 'verifications')->latest()->paginate(20);

        return $this->successResponse(
            SellerResource::collection($sellers),
            'Seller applications retrieved',
            200,
            [
                'current_page' => $sellers->currentPage(),
                'total' => $sellers->total(),
                'per_page' => $sellers->perPage(),
            ]
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $seller = Seller::with('store', 'user')->findOrFail($id);

        DB::transaction(function () use ($seller) {
            $seller->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            if ($seller->store) {
                $seller->store->update(['status' => 'active']);
            }

            // Assign seller role to user if role exists
            $sellerRole = Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']);
            if (! $seller->user->hasRole('seller')) {
                $seller->user->roles()->syncWithoutDetaching([$sellerRole->id]);
            }
        });

        return $this->successResponse(new SellerResource($seller->fresh()), 'Seller application approved');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string']]);
        $seller = Seller::findOrFail($id);

        $seller->update([
            'status' => 'rejected',
        ]);

        return $this->successResponse(new SellerResource($seller), 'Seller application rejected');
    }
}
