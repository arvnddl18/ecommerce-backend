import React, { useState, useEffect } from 'react';
import { Product, ProductVariant } from '../types';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { ProductImageGallery } from './ProductImageGallery';
import { VariantSelector } from './VariantSelector';
import { motion, AnimatePresence } from 'framer-motion';
import { modalEntranceVariants } from '../lib/motion';

interface ProductDetailModalProps {
  product: Product | null;
  onClose: () => void;
  isWishlisted?: boolean;
  onToggleWishlist?: (productId: number) => void;
}

export const ProductDetailModal: React.FC<ProductDetailModalProps> = ({
  product,
  onClose,
  isWishlisted = false,
  onToggleWishlist,
}) => {
  const { addToCart, setIsCartOpen } = useCart();
  const { token } = useAuth();
  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [isAdding, setIsAdding] = useState(false);
  const [activeTab, setActiveTab] = useState<'specs' | 'reviews'>('specs');

  // Review submission state
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);
  const [reviewError, setReviewError] = useState<string | null>(null);
  const [reviewSuccess, setReviewSuccess] = useState<string | null>(null);

  useEffect(() => {
    if (product && product.variants && product.variants.length > 0) {
      setSelectedVariant(product.variants[0]);
    } else {
      setSelectedVariant(null);
    }
    setQuantity(1);
    setActiveTab('specs');
    setReviewError(null);
    setReviewSuccess(null);
  }, [product]);

  if (!product) return null;

  const formattedEffectivePrice = selectedVariant
    ? selectedVariant.formatted_effective_price
    : product.formatted_price;

  const currentStock = selectedVariant ? selectedVariant.stock_quantity : product.stock;
  const inStock = currentStock > 0;

  const handleAdd = async (openDrawer: boolean = false) => {
    if (!inStock || isAdding) return;
    try {
      setIsAdding(true);
      await addToCart(product.id, quantity);
      if (openDrawer) {
        onClose();
        setIsCartOpen(true);
      }
    } finally {
      setIsAdding(false);
    }
  };

  const handleReviewSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      setReviewError('Please sign in to submit a verified purchase review.');
      return;
    }
    setIsSubmittingReview(true);
    setReviewError(null);
    try {
      const res = await fetch(`/api/v1/products/${product.id}/reviews`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          rating: reviewRating,
          comment: reviewComment,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setReviewError(data.message || 'Failed to submit review.');
      } else {
        setReviewSuccess('Review published to archive successfully.');
        setReviewComment('');
      }
    } catch {
      setReviewError('Network error submitting review.');
    } finally {
      setIsSubmittingReview(false);
    }
  };

  return (
    <AnimatePresence>
      <div
        className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-sm overflow-y-auto"
        onClick={onClose}
      >
        <motion.div
          variants={modalEntranceVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
          onClick={(e) => e.stopPropagation()}
          className="relative w-full max-w-5xl bg-[#FAFAF8] text-[#1A1A1A] border border-[#E8E6E1] shadow-2xl my-auto overflow-hidden"
          role="dialog"
          aria-modal="true"
          aria-labelledby="garment-title"
        >
          {/* Close Button (Minimalist Upper-Right) */}
          <button
            type="button"
            onClick={onClose}
            className="absolute top-4 right-4 z-20 w-8 h-8 rounded-full border border-[#E8E6E1] bg-white text-[#1A1A1A] hover:bg-[#1A1A1A] hover:text-white flex items-center justify-center text-xs font-mono transition-colors cursor-pointer"
            aria-label="Close product view"
          >
            ✕
          </button>

          {/* TWO ZONES LAYOUT: Large Bleed-to-edge Gallery (Left) + Garment Tag Spec Module (Right) */}
          <div className="grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
            {/* ZONE 1: Bleed-to-edge product photography gallery (Left, 7 columns) */}
            <div className="lg:col-span-7 bg-[#F0EEE9] p-6 lg:p-8 flex flex-col justify-center border-b lg:border-b-0 lg:border-r border-[#E8E6E1]">
              <ProductImageGallery
                images={product.images || []}
                galleryImages={product.gallery_images}
                productName={product.name}
              />
            </div>

            {/* ZONE 2: Editorial & Garment Tag Spec Module (Right, 5 columns) */}
            <div className="lg:col-span-5 p-6 lg:p-8 flex flex-col justify-between overflow-y-auto max-h-[85vh]">
              <div>
                {/* Atelier / Maker Header */}
                <div className="flex items-center justify-between text-xs text-[#6B6B6B] mb-2 pb-2 border-b border-[#E8E6E1]">
                  <span className="font-mono uppercase tracking-widest text-[10px]">
                    {product.seller?.store_name || 'Independent Atelier'}
                  </span>
                  <span className="text-[10px] tracking-wider text-[#FF5A36] font-semibold">
                    Stripe Verified
                  </span>
                </div>

                {/* Garment Title & Price */}
                <h2
                  id="garment-title"
                  className="text-2xl sm:text-3xl font-serif font-medium tracking-tight text-[#1A1A1A] mb-2"
                >
                  {product.name}
                </h2>

                <div className="flex items-baseline gap-3 mb-4">
                  <span className="text-xl font-bold font-serif text-[#1A1A1A]">
                    {formattedEffectivePrice}
                  </span>
                  {product.stock <= 5 && product.stock > 0 && (
                    <span className="text-[11px] font-bold text-[#FF5A36] uppercase tracking-wider">
                      Only {product.stock} cut
                    </span>
                  )}
                </div>

                <p className="text-xs text-[#6B6B6B] leading-relaxed mb-4">
                  {product.description}
                </p>

                {/* Tab Switcher: Garment Tag Specs vs Reviews */}
                <div className="flex gap-4 border-b border-[#E8E6E1] text-xs font-bold uppercase tracking-wider mb-4">
                  <button
                    type="button"
                    onClick={() => setActiveTab('specs')}
                    className={`pb-2 transition-colors cursor-pointer ${
                      activeTab === 'specs'
                        ? 'text-[#1A1A1A] border-b-2 border-[#FF5A36]'
                        : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
                    }`}
                  >
                    Garment Specs
                  </button>
                  <button
                    type="button"
                    onClick={() => setActiveTab('reviews')}
                    className={`pb-2 transition-colors cursor-pointer ${
                      activeTab === 'reviews'
                        ? 'text-[#1A1A1A] border-b-2 border-[#FF5A36]'
                        : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
                    }`}
                  >
                    Atelier Notes ({product.reviews?.length || 0})
                  </button>
                </div>

                {/* TAB 1: Physical Garment Tag Module */}
                {activeTab === 'specs' ? (
                  <div className="space-y-4">
                    {/* The Physical Garment Tag Spec Box */}
                    <div className="garment-tag rounded-none shadow-sm">
                      <div className="garment-tag-header">
                        <div className="garment-tag-brand">
                          {product.seller?.store_name || 'MAISON COLLECTIVE'}
                        </div>
                        <div className="garment-tag-sku">
                          SKU #{selectedVariant?.sku || product.sku}
                        </div>
                      </div>

                      <div className="garment-tag-grid">
                        <div className="garment-tag-item">
                          <span>Material</span>
                          100% Ring-Spun Cotton
                        </div>
                        <div className="garment-tag-item">
                          <span>Silhouette</span>
                          Boxy Relaxed Cut
                        </div>
                        <div className="garment-tag-item">
                          <span>Origin</span>
                          Verified Maker
                        </div>
                        <div className="garment-tag-item">
                          <span>Care</span>
                          Cold Wash / Hang Dry
                        </div>
                      </div>

                      {/* Barcode Graphic */}
                      <div className="garment-tag-barcode" aria-hidden="true" />
                      <div className="text-[9px] text-center text-[#6B6B6B] font-mono tracking-widest uppercase">
                        ATELIER ARCHIVE SPEC
                      </div>
                    </div>

                    {/* Variant Selector (Sizes & Colorways) */}
                    <VariantSelector
                      variants={product.variants || []}
                      selectedVariant={selectedVariant}
                      onSelectVariant={setSelectedVariant}
                    />
                  </div>
                ) : (
                  /* TAB 2: Verified Reviews */
                  <div className="space-y-4 py-2">
                    {product.reviews && product.reviews.length > 0 ? (
                      <div className="space-y-3 max-h-60 overflow-y-auto pr-1">
                        {product.reviews.map((rev) => (
                          <div
                            key={rev.id}
                            className="p-3 bg-white border border-[#E8E6E1] text-xs space-y-1"
                          >
                            <div className="flex items-center justify-between">
                              <span className="font-semibold text-[#1A1A1A]">
                                {rev.user_name}
                              </span>
                              <span className="text-[#FF5A36] text-[10px]">
                                {'★'.repeat(rev.rating)}
                              </span>
                            </div>
                            <p className="text-[#6B6B6B] leading-relaxed">{rev.comment}</p>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-xs text-[#6B6B6B] py-4">
                        No reviews logged yet. Be the first verified buyer to leave feedback.
                      </p>
                    )}

                    {/* Post Review Form */}
                    <form onSubmit={handleReviewSubmit} className="pt-3 border-t border-[#E8E6E1] space-y-3">
                      <div className="text-xs font-bold uppercase tracking-wider text-[#1A1A1A]">
                        Record Verified Note
                      </div>

                      {reviewError && (
                        <div className="text-xs text-[#D14343] bg-red-50 p-2 border border-red-200">
                          {reviewError}
                        </div>
                      )}
                      {reviewSuccess && (
                        <div className="text-xs text-[#2E7D5B] bg-emerald-50 p-2 border border-emerald-200">
                          {reviewSuccess}
                        </div>
                      )}

                      <div className="flex items-center gap-3">
                        <label className="text-[11px] text-[#6B6B6B]">Rating:</label>
                        <select
                          value={reviewRating}
                          onChange={(e) => setReviewRating(Number(e.target.value))}
                          className="text-xs bg-white border border-[#E8E6E1] p-1 text-[#1A1A1A]"
                        >
                          <option value={5}>5 Stars — Masterpiece</option>
                          <option value={4}>4 Stars — High Quality</option>
                          <option value={3}>3 Stars — Satisfactory</option>
                          <option value={2}>2 Stars — Minor Flaws</option>
                          <option value={1}>1 Star — Disappointed</option>
                        </select>
                      </div>

                      <textarea
                        rows={2}
                        placeholder="Write your reflection on sizing, drape, and material..."
                        value={reviewComment}
                        onChange={(e) => setReviewComment(e.target.value)}
                        className="w-full text-xs p-2 bg-white border border-[#E8E6E1] text-[#1A1A1A] placeholder-[#6B6B6B] focus:outline-none focus:border-[#FF5A36]"
                        required
                      />

                      <button
                        type="submit"
                        disabled={isSubmittingReview}
                        className="px-4 py-1.5 text-xs font-semibold bg-[#1A1A1A] text-white hover:bg-black transition-colors cursor-pointer"
                      >
                        {isSubmittingReview ? 'Submitting...' : 'Post Note'}
                      </button>
                    </form>
                  </div>
                )}
              </div>

              {/* Bottom Actions: Quantity & Add to Bag CTA */}
              <div className="pt-6 mt-6 border-t border-[#E8E6E1] space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center border border-[#E8E6E1] bg-white">
                    <button
                      type="button"
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      className="px-3 py-1.5 text-xs text-[#1A1A1A] hover:bg-[#FAFAF8] cursor-pointer"
                    >
                      −
                    </button>
                    <span className="px-3 py-1.5 text-xs font-mono font-medium text-[#1A1A1A]">
                      {quantity}
                    </span>
                    <button
                      type="button"
                      onClick={() => setQuantity(Math.min(currentStock, quantity + 1))}
                      disabled={quantity >= currentStock}
                      className="px-3 py-1.5 text-xs text-[#1A1A1A] hover:bg-[#FAFAF8] cursor-pointer"
                    >
                      +
                    </button>
                  </div>

                  {onToggleWishlist && (
                    <button
                      type="button"
                      onClick={() => onToggleWishlist(product.id)}
                      className="text-xs text-[#6B6B6B] hover:text-[#1A1A1A] border-b border-[#E8E6E1] pb-0.5 cursor-pointer"
                    >
                      {isWishlisted ? '★ Saved in Wishlist' : '☆ Save to Wishlist'}
                    </button>
                  )}
                </div>

                {/* Primary CTA (Using coral #FF5A36 strictly for purchase signal) */}
                <button
                  type="button"
                  onClick={() => handleAdd(true)}
                  disabled={!inStock || isAdding}
                  className={`w-full py-3.5 px-6 text-xs font-bold uppercase tracking-widest transition-all cursor-pointer ${
                    !inStock
                      ? 'bg-[#E8E6E1] text-[#A0A0A0] cursor-not-allowed'
                      : 'bg-[#FF5A36] hover:bg-[#E64A28] active:bg-[#CC3F20] text-white shadow-sm'
                  }`}
                >
                  {isAdding
                    ? 'Cataloging...'
                    : !inStock
                    ? 'Archive Out of Stock'
                    : `Add to Bag — ${formattedEffectivePrice}`}
                </button>
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
