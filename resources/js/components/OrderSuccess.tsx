import React, { useEffect, useState } from 'react';
import { ArrowLeft, Loader2, ShieldCheck, Clock, Check, Printer } from 'lucide-react';
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

    let isMounted = true;
    let pollCount = 0;
    const maxPolls = 6;

    const fetchOrder = () => {
      fetch(`/api/v1/checkout/orders/${sessionId}`)
        .then((res) => {
          if (!res.ok) throw new Error('Order lookup failed');
          return res.json();
        })
        .then((data) => {
          if (!isMounted) return;
          const orderData: Order = data.data;
          setOrder(orderData);
          if (orderData?.status === 'pending' && pollCount < maxPolls) {
            pollCount++;
            setTimeout(fetchOrder, 2500);
          }
        })
        .catch((err) => {
          if (isMounted) setError(err.message);
        })
        .finally(() => {
          if (isMounted) setIsLoading(false);
        });
    };

    fetchOrder();

    return () => {
      isMounted = false;
    };
  }, []);

  const isPaid = order?.status === 'paid' || order?.status === 'completed';

  return (
    <div className="max-w-2xl mx-auto px-4 py-16 text-center">
      {/* Editorial Status Badge & Eyebrow */}
      <div className="inline-flex items-center justify-center w-12 h-12 rounded-full border border-[#E8E6E1] bg-white mb-4 shadow-xs">
        {isPaid ? (
          <Check className="w-5 h-5 text-[#2E7D5B]" />
        ) : (
          <Clock className="w-5 h-5 text-[#FF5A36]" />
        )}
      </div>

      <div className="text-[10px] font-mono uppercase tracking-[0.2em] text-[#FF5A36] mb-1">
        {isPaid ? 'Acquisition Confirmed · Stripe Settlement' : 'Transaction Submitted · Verifying Webhook'}
      </div>

      <h1 className="font-serif text-3xl md:text-4xl font-medium text-[#1A1A1A] tracking-tight">
        {isPaid ? 'Payment Confirmed' : 'Order Placed'}
      </h1>

      <p className="mt-2 text-xs text-[#6B6B6B] max-w-md mx-auto leading-relaxed">
        {isPaid
          ? 'Thank you for your acquisition. Your payment has been securely confirmed via Stripe Connect.'
          : 'Thank you for your purchase. We are confirming your settlement with Stripe.'}
      </p>

      {isLoading ? (
        <div className="mt-10 py-12 flex justify-center items-center gap-2.5 text-[#6B6B6B] text-xs font-mono">
          <Loader2 className="w-4 h-4 animate-spin text-[#FF5A36]" />
          <span>Synchronizing order records...</span>
        </div>
      ) : error ? (
        <div className="mt-8 p-6 bg-white border border-[#E8E6E1] text-xs text-[#D14343]">
          {error}
        </div>
      ) : order ? (
        /* The Boutique Docket / Packing Slip */
        <div className="mt-8 bg-white border border-[#E8E6E1] text-left shadow-xs overflow-hidden">
          {/* Slip Header */}
          <div className="p-6 border-b border-[#E8E6E1] bg-[#FAFAF8]/50 flex flex-wrap items-center justify-between gap-4">
            <div>
              <span className="text-[10px] font-mono uppercase tracking-widest text-[#6B6B6B] block">
                Order Reference
              </span>
              <div className="text-sm font-mono font-bold text-[#1A1A1A] mt-0.5 tracking-wider">
                {order.order_number}
              </div>
            </div>

            <div>
              {isPaid ? (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-mono uppercase tracking-wider font-semibold bg-[#2E7D5B]/10 text-[#2E7D5B] border border-[#2E7D5B]/20">
                  <span className="w-1.5 h-1.5 rounded-full bg-[#2E7D5B]"></span>
                  Status: Settled
                </span>
              ) : (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-mono uppercase tracking-wider font-semibold bg-[#FFC94D]/30 text-[#855B00] border border-[#FFC94D]/60">
                  <span className="w-1.5 h-1.5 rounded-full bg-[#855B00] animate-pulse"></span>
                  Status: Pending Webhook
                </span>
              )}
            </div>
          </div>

          {/* Slip Body Details */}
          <div className="p-6 space-y-5">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div>
                <span className="text-[10px] font-mono uppercase tracking-widest text-[#6B6B6B] block mb-1">
                  Customer Email
                </span>
                <span className="text-[#1A1A1A] font-medium break-all">{order.customer_email}</span>
              </div>
              <div>
                <span className="text-[10px] font-mono uppercase tracking-widest text-[#6B6B6B] block mb-1">
                  Placement Date
                </span>
                <span className="text-[#1A1A1A] font-mono">
                  {new Date(order.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                  })}
                </span>
              </div>
            </div>

            {/* Line Items */}
            {order.items && order.items.length > 0 && (
              <div className="pt-5 border-t border-[#E8E6E1]">
                <span className="text-[10px] font-mono uppercase tracking-widest text-[#6B6B6B] block mb-3">
                  Purchased Garments ({order.items.reduce((acc, it) => acc + it.quantity, 0)})
                </span>
                <div className="divide-y divide-[#E8E6E1]/60">
                  {order.items.map((item) => (
                    <div key={item.id} className="py-2.5 flex items-center justify-between text-xs">
                      <div className="flex items-center gap-2.5 min-w-0 pr-4">
                        <span className="font-mono text-[10px] text-[#6B6B6B] border border-[#E8E6E1] px-1.5 py-0.5 bg-[#FAFAF8] shrink-0">
                          {item.quantity}×
                        </span>
                        <span className="text-[#1A1A1A] font-medium truncate">
                          {item.product_name}
                        </span>
                      </div>
                      <span className="font-mono font-semibold text-[#1A1A1A] shrink-0">
                        {item.formatted_total_price}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Total Row */}
            <div className="pt-4 border-t border-[#E8E6E1] flex items-baseline justify-between">
              <span className="text-xs font-mono uppercase tracking-widest text-[#6B6B6B]">
                Total Amount Paid
              </span>
              <span className="font-serif text-xl font-bold text-[#1A1A1A]">
                {order.formatted_total}
              </span>
            </div>

            {/* Verification Footer */}
            <div className="pt-4 border-t border-[#E8E6E1] flex flex-wrap items-center justify-between gap-2 text-[10px] font-mono text-[#6B6B6B]">
              <span className="flex items-center gap-1.5">
                <ShieldCheck className="w-3.5 h-3.5 text-[#2E7D5B]" />
                {isPaid ? 'Verified via Stripe Webhook HMAC' : 'Stripe Connect Atomic Session'}
              </span>
              <span>256-Bit SSL · Authenticated</span>
            </div>
          </div>
        </div>
      ) : null}

      {/* Action Buttons */}
      <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          type="button"
          onClick={onReturnToStore}
          className="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-[#1A1A1A] hover:bg-[#2C2C2C] active:bg-[#000000] text-white text-xs font-mono font-semibold uppercase tracking-[0.16em] transition-all duration-150 cursor-pointer shadow-sm hover:shadow-md border border-neutral-900"
        >
          <ArrowLeft className="w-4 h-4 text-white transition-transform duration-150 group-hover:-translate-x-1 shrink-0" />
          <span className="text-white">Return to Catalog</span>
        </button>

        <button
          type="button"
          onClick={() => window.print()}
          className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white hover:bg-[#FAFAF8] text-[#1A1A1A] text-xs font-mono font-medium uppercase tracking-[0.16em] transition-colors cursor-pointer border border-[#E8E6E1] hover:border-[#1A1A1A]"
        >
          <Printer className="w-3.5 h-3.5 text-[#6B6B6B]" />
          <span>Print Receipt</span>
        </button>
      </div>
    </div>
  );
};
