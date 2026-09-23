<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of active products with optional category, search, and sort filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $categorySlug = $request->query('category');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $sort = $request->query('sort', 'newest');
        $perPage = (int) $request->query('per_page', 12);
        $page = (int) $request->query('page', 1);

        $size = $request->query('size');
        $color = $request->query('color');
        $sellerId = $request->query('seller_id');

        $query = Product::query()
            ->active()
            ->with(['category', 'seller', 'variants', 'galleryImages', 'reviews.user']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        } elseif ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        if ($size) {
            $query->whereHas('variants', function ($q) use ($size): void {
                $q->where('size', $size);
            });
        }

        if ($color) {
            $query->whereHas('variants', function ($q) use ($color): void {
                $q->where('color', $color);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->latest('id'),
        };

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified active product.
     */
    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404, 'Product not found.');

        $product->load(['category', 'seller', 'variants', 'galleryImages', 'reviews.user']);

        return new ProductResource($product);
    }

    /**
     * Provide autocomplete suggestions for search query across products, categories, and sellers.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'products' => [],
                'categories' => [],
                'sellers' => [],
            ]);
        }

        $products = Product::active()
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'slug', 'price', 'images']);

        $categories = Category::where('name', 'like', "%{$query}%")
            ->take(4)
            ->get(['id', 'name', 'slug']);

        $sellers = SellerProfile::where('store_name', 'like', "%{$query}%")
            ->where('verification_status', 'approved')
            ->take(3)
            ->get(['id', 'store_name', 'slug']);

        return response()->json([
            'query' => $query,
            'products' => $products,
            'categories' => $categories,
            'sellers' => $sellers,
        ]);
    }
}
