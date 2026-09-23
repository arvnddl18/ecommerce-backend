import React, { useState, useEffect, useMemo } from 'react';
import { Product } from '../types';
import { ProductCard } from './ProductCard';

interface ProductGridProps {
  onViewProduct: (product: Product) => void;
  selectedCategory: string | null;
  onSelectCategory: (slug: string | null) => void;
  wishlistIds: number[];
  onToggleWishlist: (productId: number) => void;
  currentSort: string;
}

export const ProductGrid: React.FC<ProductGridProps> = ({
  onViewProduct,
  selectedCategory,
  onSelectCategory,
  wishlistIds,
  onToggleWishlist,
  currentSort,
}) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [search, setSearch] = useState<string>('');
  const [activeFilter, setActiveFilter] = useState<'all' | 'in_stock' | 'under_150'>('all');
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    setIsLoading(true);
    const params = new URLSearchParams();
    if (selectedCategory) params.append('category', selectedCategory);
    if (search.trim()) params.append('search', search.trim());
    if (currentSort) params.append('sort', currentSort);

    fetch(`/api/v1/products?${params.toString()}`)
      .then((res) => res.json())
      .then((data) => {
        setProducts(data.data || []);
      })
      .catch(() => {})
      .finally(() => setIsLoading(false));
  }, [selectedCategory, search, currentSort]);

  // Client-side quick filters (All, In stock, Under $150) matching references/index.html
  const filteredProducts = useMemo(() => {
    return products.filter((product) => {
      if (activeFilter === 'in_stock' && !product.in_stock) return false;
      if (activeFilter === 'under_150' && product.price > 15000) return false;
      return true;
    });
  }, [products, activeFilter]);

  const categoryDisplayName = selectedCategory
    ? selectedCategory.replace('-', ' ')
    : 'new arrivals';

  return (
    <>
      <section className="catalog" id="products">
        {/* Top catalog toolbar with items count & filter links */}
        <div className="catalog-top">
          <p>
            <strong>{filteredProducts.length} items</strong> <span>·</span> Showing {categoryDisplayName}
          </p>

          <div className="flex items-center gap-6">
            {/* Quick Filter Links matching references/index.html */}
            <div className="filter-links">
              <button
                type="button"
                onClick={() => setActiveFilter('all')}
                className={activeFilter === 'all' ? 'filter-active' : ''}
              >
                All
              </button>
              <button
                type="button"
                onClick={() => setActiveFilter('in_stock')}
                className={activeFilter === 'in_stock' ? 'filter-active' : ''}
              >
                In stock
              </button>
              <button
                type="button"
                onClick={() => setActiveFilter('under_150')}
                className={activeFilter === 'under_150' ? 'filter-active' : ''}
              >
                Under $150
              </button>
            </div>
          </div>
        </div>

        {/* 4-Column Product Grid */}
        {isLoading ? (
          <div className="product-grid">
            {[...Array(8)].map((_, i) => (
              <div key={i} className="product-card">
                <div className="product-image bg-[#f0eee9] animate-pulse" />
                <div className="product-info">
                  <div className="space-y-1">
                    <div className="h-4 bg-[#e8e6e1] w-28" />
                    <div className="h-3 bg-[#e8e6e1] w-16" />
                  </div>
                  <div className="h-4 bg-[#e8e6e1] w-12" />
                </div>
              </div>
            ))}
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="product-grid">
            {filteredProducts.map((product, idx) => (
              <ProductCard
                key={product.id}
                product={product}
                index={idx}
                onViewDetails={onViewProduct}
                isWishlisted={wishlistIds.includes(product.id)}
                onToggleWishlist={onToggleWishlist}
              />
            ))}
          </div>
        ) : (
          <div className="py-20 text-center border-t border-b border-[#E8E6E1]">
            <h3 className="text-xl font-medium text-[#1A1A1A] font-serif">
              No pieces match your selection
            </h3>
            <p className="text-xs text-[#6B6B6B] mt-2 max-w-sm mx-auto">
              Try switching your filter or selecting a different apparel category.
            </p>
            <button
              type="button"
              onClick={() => {
                setActiveFilter('all');
                onSelectCategory(null);
                setSearch('');
              }}
              className="mt-5 text-xs font-semibold text-[#FF5A36] border-b border-[#FF5A36] pb-0.5 cursor-pointer"
            >
              Reset to all pieces
            </button>
          </div>
        )}
      </section>

      {/* Story Section matching references/index.html */}
      <section className="story-section" id="story">
        <div>
          <p className="eyebrow">Why FOLD</p>
          <h2>Less, but better chosen.</h2>
        </div>
        <p>
          Thoughtful fabrics, honest construction, and silhouettes that get better with time.
        </p>
        <a
          className="circle-link"
          href="#products"
          onClick={(e) => {
            e.preventDefault();
            const el = document.getElementById('products');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
          }}
          aria-label="Read our edit"
        >
          Read our edit ↗
        </a>
      </section>
    </>
  );
};
