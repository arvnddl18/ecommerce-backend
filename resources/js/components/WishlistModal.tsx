import React, { useState, useEffect } from 'react';
import { Product } from '../types';
import { useAuth } from '../context/AuthContext';
import { motion, AnimatePresence } from 'framer-motion';
import { modalEntranceVariants } from '../lib/motion';

interface WishlistModalProps {
  isOpen: boolean;
  onClose: () => void;
  onViewProduct: (product: Product) => void;
  wishlistIds: number[];
  onToggleWishlist: (productId: number) => void;
}

export const WishlistModal: React.FC<WishlistModalProps> = ({
  isOpen,
  onClose,
  onViewProduct,
  wishlistIds,
  onToggleWishlist,
}) => {
  const { token, isAuthenticated } = useAuth();
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  useEffect(() => {
    if (isOpen && isAuthenticated && token) {
      setIsLoading(true);
      fetch('/api/v1/wishlist', {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      })
        .then((res) => res.json())
        .then((data) => setProducts(data.data || []))
        .catch(() => {})
        .finally(() => setIsLoading(false));
    }
  }, [isOpen, isAuthenticated, token, wishlistIds]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div
        className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
      >
        <motion.div
          variants={modalEntranceVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
          onClick={(e) => e.stopPropagation()}
          className="relative w-full max-w-2xl max-h-[85vh] flex flex-col bg-[#FAFAF8] text-[#1A1A1A] border border-[#E8E6E1] shadow-2xl overflow-hidden"
          role="dialog"
          aria-modal="true"
        >
          {/* Header */}
          <div className="p-6 border-b border-[#E8E6E1] bg-white flex items-center justify-between">
            <div className="flex items-center gap-3">
              <span className="font-serif font-bold text-lg tracking-tight">Saved Archive</span>
              <span className="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#FAFAF8] border border-[#E8E6E1] text-[#6B6B6B]">
                {products.length} {products.length === 1 ? 'garment' : 'garments'}
              </span>
            </div>

            <button
              type="button"
              onClick={onClose}
              className="w-7 h-7 rounded-full border border-[#E8E6E1] bg-[#FAFAF8] text-[#1A1A1A] hover:bg-[#1A1A1A] hover:text-white flex items-center justify-center text-xs font-mono transition-colors cursor-pointer"
              aria-label="Close wishlist"
            >
              ✕
            </button>
          </div>

          {/* List Content */}
          <div className="flex-1 overflow-y-auto p-6 space-y-3">
            {isLoading ? (
              <div className="space-y-3">
                {[1, 2, 3].map((n) => (
                  <div key={n} className="h-20 bg-white border border-[#E8E6E1] animate-pulse" />
                ))}
              </div>
            ) : products.length > 0 ? (
              products.map((product) => (
                <div
                  key={product.id}
                  onClick={() => {
                    onViewProduct(product);
                    onClose();
                  }}
                  className="flex items-center justify-between p-3 bg-white border border-[#E8E6E1] hover:border-[#1A1A1A] transition-colors cursor-pointer"
                >
                  <div className="flex items-center gap-4 min-w-0">
                    <div className="w-14 h-16 bg-[#F0EEE9] shrink-0 border border-[#E8E6E1] overflow-hidden">
                      <img
                        src={product.images?.[0] || 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&q=80'}
                        alt={product.name}
                        className="w-full h-full object-cover"
                      />
                    </div>
                    <div className="min-w-0">
                      <h4 className="text-xs font-serif font-medium text-[#1A1A1A] truncate">
                        {product.name}
                      </h4>
                      <p className="text-[11px] text-[#6B6B6B]">
                        {product.seller?.store_name || product.category?.name}
                      </p>
                      <div className="text-xs font-mono font-bold text-[#1A1A1A] mt-1">
                        {product.formatted_price}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-3 shrink-0">
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        onToggleWishlist(product.id);
                      }}
                      className="text-[10px] text-[#6B6B6B] hover:text-[#D14343] transition-colors cursor-pointer"
                    >
                      Remove
                    </button>
                    <span className="text-xs font-bold text-[#FF5A36] border-b border-[#FF5A36] pb-0.5">
                      Inspect
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-16">
                <p className="text-xs text-[#6B6B6B]">
                  No pieces saved in your archive yet.
                </p>
              </div>
            )}
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
