import React from 'react';
import { ShoppingBag, ArrowLeft, AlertCircle } from 'lucide-react';
import { useCart } from '../context/CartContext';

export const OrderCancel: React.FC<{ onReturnToStore: () => void }> = ({ onReturnToStore }) => {
  const { setIsCartOpen } = useCart();

  return (
    <div className="max-w-md mx-auto px-4 py-20 text-center">
      <div className="w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-6">
        <AlertCircle className="w-10 h-10" />
      </div>

      <h1 className="text-2xl font-extrabold text-white tracking-tight">
        Checkout Abandoned
      </h1>

      <p className="mt-2 text-sm text-slate-400">
        You were not charged. Your shopping cart has been safely preserved in Redis.
      </p>

      <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <button
          onClick={() => {
            onReturnToStore();
            setIsCartOpen(true);
          }}
          className="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md shadow-indigo-600/30 transition-all cursor-pointer"
        >
          <ShoppingBag className="w-4 h-4" />
          <span>Resume Checkout</span>
        </button>

        <button
          onClick={onReturnToStore}
          className="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-sm font-medium transition-all cursor-pointer"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Continue Browsing</span>
        </button>
      </div>
    </div>
  );
};
