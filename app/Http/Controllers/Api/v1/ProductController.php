<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Product;
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
}
