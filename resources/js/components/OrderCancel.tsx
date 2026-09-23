import React from 'react';
import { ShoppingBag, ArrowLeft } from 'lucide-react';
import { useCart } from '../context/CartContext';

export const OrderCancel: React.FC<{ onReturnToStore: () => void }> = ({ onReturnToStore }) => {
  const { setIsCartOpen } = useCart();

  return (
    <div className="max-w-md mx-auto px-4 py-20 text-center">
      <div className="inline-flex items-center justify-center w-12 h-12 rounded-full border border-[#E8E6E1] bg-white mb-5 shadow-xs">
        <ShoppingBag className="w-5 h-5 text-[#FF5A36]" />
      </div>

      <div className="text-[10px] font-mono uppercase tracking-[0.2em] text-[#FF5A36] mb-1">
        Checkout Interrupted
      </div>

      <h1 className="font-serif text-2xl md:text-3xl font-medium text-[#1A1A1A] tracking-tight">
        Checkout Abandoned
      </h1>

      <p className="mt-2 text-xs text-[#6B6B6B] leading-relaxed max-w-xs mx-auto">
        You were not charged. Your selected garments have been safely preserved in your bag.
      </p>

      <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <button
          type="button"
          onClick={() => {
            onReturnToStore();
            setIsCartOpen(true);
          }}
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-[#FF5A36] hover:bg-[#E64A28] text-white text-xs font-mono font-semibold uppercase tracking-[0.16em] transition-colors cursor-pointer"
        >
          <ShoppingBag className="w-3.5 h-3.5 text-white" />
          <span className="text-white">Resume Checkout</span>
        </button>

        <button
          type="button"
          onClick={onReturnToStore}
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white border border-[#E8E6E1] hover:border-[#1A1A1A] text-[#1A1A1A] text-xs font-mono font-semibold uppercase tracking-[0.16em] transition-colors cursor-pointer"
        >
          <ArrowLeft className="w-3.5 h-3.5 text-[#1A1A1A]" />
          <span className="text-[#1A1A1A]">Browse Garments</span>
        </button>
      </div>
    </div>
  );
};
