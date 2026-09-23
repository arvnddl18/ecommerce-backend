import React, { useState } from 'react';
import { Product } from '../types';
import { useCart } from '../context/CartContext';
import { motion } from 'framer-motion';
import { pullToInspectVariants, quickAddFeedbackVariants } from '../lib/motion';

interface ProductCardProps {
  product: Product;
  index: number;
  layoutVariant?: 'feature-card' | 'simple-card' | 'offset-card' | 'wide-card';
  onViewDetails: (product: Product) => void;
  isWishlisted?: boolean;
  onToggleWishlist?: (productId: number) => void;
}

export const ProductCard: React.FC<ProductCardProps> = ({
  product,
  index,
  layoutVariant = 'feature-card',
  onViewDetails,
  isWishlisted = false,
  onToggleWishlist,
}) => {
  const { addToCart } = useCart();
  const [isAdding, setIsAdding] = useState(false);
  const [justAdded, setJustAdded] = useState(false);

  const handleQuickAdd = async (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!product.in_stock || isAdding) return;

    try {
      setIsAdding(true);
      await addToCart(product.id, 1);
      setJustAdded(true);
      setTimeout(() => setJustAdded(false), 2000);
    } catch {
      // Handled silently or toast in cart
    } finally {
      setIsAdding(false);
    }
  };

  const handleWishlistToggle = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onToggleWishlist) {
      onToggleWishlist(product.id);
    }
  };

  const imageUrl = product.images?.[0] || 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=1000&q=85';
  const formattedIndex = index + 1 < 10 ? `N° 0${index + 1}` : `N° ${index + 1}`;

  // Badge determination: scarcity badge vs new
  const isLimited = product.stock > 0 && product.stock <= 5;
  const isSoldOut = !product.in_stock;

  return (
    <article
      onClick={() => onViewDetails(product)}
      className={`product-card ${layoutVariant} group focus-visible:outline-none`}
      tabIndex={0}
      role="button"
      aria-label={`View ${product.name}`}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onViewDetails(product);
        }
      }}
    >
      {/* Product Image Frame */}
      <div className="product-image overflow-hidden relative">
        <motion.img
          src={imageUrl}
          alt={product.name}
          loading="lazy"
          variants={pullToInspectVariants}
          initial="rest"
          whileHover="inspect"
          className="w-full h-full object-cover object-center select-none"
        />

        {/* Scarcity / Attention Badges (Highlight Yellow or Coral) */}
        {isSoldOut ? (
          <span className="badge limited">Sold Out</span>
        ) : isLimited ? (
          <span className="badge limited">Only {product.stock} Left</span>
        ) : index === 0 || index === 2 ? (
          <span className="badge">Curated</span>
        ) : null}

        {/* Index Marker (Physical Catalog Spec) */}
        <span className="image-index" aria-hidden="true">
          {formattedIndex}
        </span>

        {/* Wishlist Subtle Trigger */}
        {onToggleWishlist && (
          <button
            type="button"
            onClick={handleWishlistToggle}
            className={`absolute top-3 right-3 z-10 w-8 h-8 rounded-full flex items-center justify-center transition-all bg-white/90 hover:bg-white text-[#1A1A1A] border border-[#E8E6E1] cursor-pointer ${
              isWishlisted ? '!bg-[#FF5A36] !text-white !border-[#FF5A36]' : 'opacity-0 group-hover:opacity-100'
            }`}
            title={isWishlisted ? 'Remove from Wishlist' : 'Save to Wishlist'}
            aria-label={isWishlisted ? 'Remove from Wishlist' : 'Save to Wishlist'}
          >
            <span className="text-xs font-bold leading-none">
              {isWishlisted ? '★' : '☆'}
            </span>
          </button>
        )}
      </div>

      {/* Product Info Bar */}
      <div className="product-info">
        <div className="pr-4">
          <h3>{product.name}</h3>
          <p>
            {product.seller?.store_name || product.category?.name || 'Maison Collective'}
          </p>
        </div>

        <div className="text-right flex flex-col items-end justify-between">
          <strong>{product.formatted_price}</strong>

          {/* Quick Add To Bag Action */}
          <motion.button
            type="button"
            variants={quickAddFeedbackVariants}
            initial="initial"
            whileTap="tap"
            animate={justAdded ? 'success' : 'initial'}
            onClick={handleQuickAdd}
            disabled={isSoldOut || isAdding}
            className={`mt-2 text-[11px] font-semibold tracking-wider uppercase border-b pb-0.5 transition-colors cursor-pointer ${
              justAdded
                ? 'text-[#2E7D5B] border-[#2E7D5B]'
                : isSoldOut
                ? 'text-[#A0A0A0] border-transparent cursor-not-allowed'
                : 'text-[#1A1A1A] border-[#1A1A1A] hover:text-[#FF5A36] hover:border-[#FF5A36]'
            }`}
          >
            {justAdded ? 'Added' : isAdding ? '...' : isSoldOut ? 'Archive' : 'Add to Bag'}
          </motion.button>
        </div>
      </div>
    </article>
  );
};
