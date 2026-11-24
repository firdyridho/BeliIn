<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Http\Resources\ProductDetailResource;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Seller\Http\Resources\StoreResource;
use App\Modules\Seller\Models\Store;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = Category::with('children')->whereNull('parent_id')->where('status', 'active')->orderBy('sort_order')->get();

        return $this->successResponse(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }

    public function categoryBySlug(string $slug): JsonResponse
    {
        $category = Category::with('children')->where('slug', $slug)->firstOrFail();

        return $this->successResponse(new CategoryResource($category), 'Category details retrieved');
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::with(['store', 'category', 'variants', 'images'])
            ->where('status', 'published');

        if ($request->filled('q') || $request->filled('search')) {
            $searchTerm = $request->input('q') ?: $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category'));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('min_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price_amount', '>=', $request->input('min_price'));
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price_amount', '<=', $request->input('max_price'));
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 50);
        $products = $query->latest()->paginate($perPage);

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully',
            200,
            [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
            ]
        );
    }

    public function productBySlug(string $slug): JsonResponse
    {
        $product = Product::with(['store', 'category', 'variants.inventory', 'images'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return $this->successResponse(new ProductDetailResource($product), 'Product details retrieved');
    }

    public function storeBySlug(string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->where('status', 'active')->firstOrFail();

        return $this->successResponse(new StoreResource($store), 'Store details retrieved');
    }

    public function storeProducts(Request $request, string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $products = Product::with(['category', 'variants', 'images'])
            ->where('store_id', $store->id)
            ->where('status', 'published')
            ->latest()
            ->paginate(20);

        return $this->successResponse(
            ProductResource::collection($products),
            'Store products retrieved',
            200,
            [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ]
        );
    }
}
