import React, { useState, useEffect } from 'react';
import { Product } from '../types';
import { ProductCard } from './ProductCard';

interface ProductGridProps {
  onViewProduct: (product: Product) => void;
  selectedCategory: string | null;
  onSelectCategory: (slug: string | null) => void;
  wishlistIds: number[];
  onToggleWishlist: (productId: number) => void;
}

export const ProductGrid: React.FC<ProductGridProps> = ({
  onViewProduct,
  selectedCategory,
  onSelectCategory,
  wishlistIds,
  onToggleWishlist,
}) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [search, setSearch] = useState<string>('');
  const [selectedSize, setSelectedSize] = useState<string>('');
  const [sort, setSort] = useState<string>('newest');
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    setIsLoading(true);
    const params = new URLSearchParams();
    if (selectedCategory) params.append('category', selectedCategory);
    if (search.trim()) params.append('search', search.trim());
    if (selectedSize) params.append('size', selectedSize);
    if (sort) params.append('sort', sort);

    fetch(`/api/v1/products?${params.toString()}`)
      .then((res) => res.json())
      .then((data) => {
        setProducts(data.data || []);
      })
      .catch(() => {})
      .finally(() => setIsLoading(false));
  }, [selectedCategory, search, selectedSize, sort]);

  // Layout assignment helper for 4-phase asymmetric boutique cadence
  const getLayoutVariant = (
    index: number
  ): 'feature-card' | 'simple-card' | 'offset-card' | 'wide-card' => {
    const cycle = index % 4;
    switch (cycle) {
      case 0:
        return 'feature-card';
      case 1:
        return 'simple-card';
      case 2:
        return 'offset-card';
      case 3:
      default:
        return 'wide-card';
    }
  };

  return (
    <section id="collection-section" className="collection">
      {/* Section Heading & Editorial Aside */}
      <div className="section-heading">
        <div>
          <h2>
            {selectedCategory
              ? `${selectedCategory.toUpperCase()} COLLECTION`
              : 'SELECTED WORKS'}
          </h2>
        </div>

        <div className="heading-aside">
          <p>
            Curated garments and bespoke staples from verified independent ateliers.
            Each piece is cataloged with verified fiber composition and decentralized fulfillment.
          </p>
        </div>
      </div>

      {/* Editorial Filter Bar (Boutique Minimalism: No pills, no middle-dots) */}
      <div className="flex flex-wrap items-center justify-between gap-4 pb-10 mb-10 border-b border-[#E8E6E1]">
        {/* Search */}
        <div className="flex items-center gap-2">
          <span className="text-[11px] font-bold uppercase tracking-wider text-[#6B6B6B]">
            Filter /
          </span>
          <input
            type="text"
            placeholder="Search silhouettes, materials, ateliers..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="text-xs bg-transparent border-b border-[#1A1A1A] pb-1 px-1 text-[#1A1A1A] placeholder-[#6B6B6B] focus:outline-none focus:border-[#FF5A36] min-w-[240px] transition-colors"
          />
        </div>

        {/* Size & Sorting */}
        <div className="flex items-center gap-6 text-xs text-[#6B6B6B]">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold uppercase tracking-wider">Size:</span>
            <select
              value={selectedSize}
              onChange={(e) => setSelectedSize(e.target.value)}
              className="bg-transparent border-b border-[#E8E6E1] text-[#1A1A1A] text-xs py-0.5 pr-2 focus:outline-none focus:border-[#1A1A1A] cursor-pointer"
            >
              <option value="">All Sizes</option>
              <option value="S">Small (S)</option>
              <option value="M">Medium (M)</option>
              <option value="L">Large (L)</option>
              <option value="XL">Extra Large (XL)</option>
              <option value="US 9">US 9</option>
              <option value="US 10">US 10</option>
              <option value="US 11">US 11</option>
            </select>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold uppercase tracking-wider">Sort:</span>
            <select
              value={sort}
              onChange={(e) => setSort(e.target.value)}
              className="bg-transparent border-b border-[#E8E6E1] text-[#1A1A1A] text-xs py-0.5 pr-2 focus:outline-none focus:border-[#1A1A1A] cursor-pointer"
            >
              <option value="newest">Latest Release</option>
              <option value="price_asc">Price: Low to High</option>
              <option value="price_desc">Price: High to Low</option>
              <option value="name_asc">Alphabetical</option>
            </select>
          </div>

          {(search || selectedSize || selectedCategory) && (
            <button
              type="button"
              onClick={() => {
                setSearch('');
                setSelectedSize('');
                onSelectCategory(null);
              }}
              className="text-[11px] text-[#FF5A36] border-b border-[#FF5A36] hover:text-[#CC3F20] transition-colors cursor-pointer"
            >
              Clear Filter
            </button>
          )}
        </div>
      </div>

      {/* Asymmetric Product Grid (1.15fr .85fr with deliberate offsets) */}
      {isLoading ? (
        <div className="product-grid">
          <div className="feature-card">
            <div className="product-image aspect-[0.86] bg-[#f0eee9] animate-pulse" />
            <div className="product-info border-t border-[#1A1A1A] pt-4 mt-2">
              <div className="h-4 bg-[#e8e6e1] w-48 mb-1" />
              <div className="h-4 bg-[#e8e6e1] w-16" />
            </div>
          </div>
          <div className="simple-card">
            <div className="product-image aspect-[0.87] bg-[#f0eee9] animate-pulse" />
            <div className="product-info border-t border-[#1A1A1A] pt-4 mt-2">
              <div className="h-4 bg-[#e8e6e1] w-36 mb-1" />
              <div className="h-4 bg-[#e8e6e1] w-16" />
            </div>
          </div>
        </div>
      ) : products.length > 0 ? (
        <div className="product-grid">
          {products.map((product, idx) => (
            <ProductCard
              key={product.id}
              product={product}
              index={idx}
              layoutVariant={getLayoutVariant(idx)}
              onViewDetails={onViewProduct}
              isWishlisted={wishlistIds.includes(product.id)}
              onToggleWishlist={onToggleWishlist}
            />
          ))}
        </div>
      ) : (
        <div className="py-24 text-center border-t border-b border-[#E8E6E1]">
          <h3 className="text-xl font-medium text-[#1A1A1A] font-serif">
            No garments cataloged in this rack
          </h3>
          <p className="text-xs text-[#6B6B6B] mt-2 max-w-sm mx-auto">
            Try adjusting your search criteria, size filter, or selecting a different archive.
          </p>
          <button
            type="button"
            onClick={() => {
              setSearch('');
              setSelectedSize('');
              onSelectCategory(null);
            }}
            className="mt-6 text-xs font-semibold text-[#FF5A36] border-b border-[#FF5A36] pb-1 cursor-pointer"
          >
            Return to All Pieces
          </button>
        </div>
      )}

      {/* Editorial Story Section (3-Column Layout from references/style.css) */}
      <section className="story-section">
        <div className="story-copy">
          <p className="kicker">Philosophy & Provenance</p>
          <h2>Craft Over Mass Production</h2>
        </div>

        <div className="story-body">
          <p>
            Every silhouette featured on our rail originates from an independently registered atelier.
            Transactions are atomically secured and settled directly via Stripe Connect, ensuring
            ethical creator payouts with zero intermediaries.
          </p>
        </div>

        <a
          href="#collection-section"
          onClick={(e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }}
          className="circle-link"
          aria-label="Back to Top"
        >
          <div>
            Top
            <span>↑</span>
          </div>
        </a>
      </section>
    </section>
  );
};
