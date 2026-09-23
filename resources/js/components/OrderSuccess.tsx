import React, { useEffect, useState } from 'react';
import { CheckCircle2, PackageCheck, ArrowLeft, Loader2, ShieldCheck } from 'lucide-react';
import { Order } from '../types';

export const OrderSuccess: React.FC<{ onReturnToStore: () => void }> = ({ onReturnToStore }) => {
  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const sessionId = params.get('session_id');

    if (!sessionId) {
      setIsLoading(false);
      return;
    }

    fetch(`/api/v1/checkout/orders/${sessionId}`)
      .then((res) => {
        if (!res.ok) throw new Error('Order lookup failed');
        return res.json();
      })
      .then((data) => setOrder(data.data))
      .catch((err) => setError(err.message))
      .finally(() => setIsLoading(false));
  }, []);

  return (
    <div className="max-w-2xl mx-auto px-4 py-16 text-center">
      <div className="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-500/20">
        <CheckCircle2 className="w-10 h-10" />
      </div>

      <h1 className="text-3xl font-extrabold text-white tracking-tight">
        Payment Confirmed!
      </h1>

      <p className="mt-2 text-sm text-slate-400">
        Thank you for your purchase. Your payment has been securely processed via Stripe.
      </p>

      {isLoading ? (
        <div className="mt-8 flex justify-center items-center gap-2 text-slate-400 text-sm">
          <Loader2 className="w-5 h-5 animate-spin text-indigo-400" />
          <span>Verifying order records...</span>
        </div>
      ) : order ? (
        <div className="mt-8 p-6 rounded-3xl bg-slate-900 border border-slate-800 text-left space-y-4">
          <div className="flex justify-between items-center pb-4 border-b border-slate-800">
            <div>
              <span className="text-[10px] uppercase font-bold text-slate-500">Order Number</span>
              <div className="text-sm font-mono font-bold text-white">{order.order_number}</div>
            </div>
            <span className="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
              Status: {order.status.toUpperCase()}
            </span>
          </div>

          <div className="text-xs text-slate-400 space-y-1">
            <div>Customer Email: <span className="text-white font-medium">{order.customer_email}</span></div>
            <div>Total Paid: <span className="text-white font-bold text-sm">{order.formatted_total}</span></div>
          </div>

          {order.items && order.items.length > 0 && (
            <div className="pt-4 border-t border-slate-800">
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Purchased Items</span>
              <div className="space-y-2">
                {order.items.map((item) => (
                  <div key={item.id} className="flex justify-between text-xs text-slate-300">
                    <span>{item.quantity}x {item.product_name}</span>
                    <span className="font-semibold text-white">{item.formatted_total_price}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="pt-4 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
            <span className="flex items-center gap-1.5">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
              Verified via Stripe Webhook HMAC
            </span>
            <span>Date: {new Date(order.created_at).toLocaleDateString()}</span>
          </div>
        </div>
      ) : null}

      <div className="mt-8">
        <button
          onClick={onReturnToStore}
          className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all cursor-pointer"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Return to Catalog</span>
        </button>
      </div>
    </div>
  );
};
