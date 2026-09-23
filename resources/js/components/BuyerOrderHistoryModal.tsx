import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { motion, AnimatePresence } from 'framer-motion';
import { modalEntranceVariants } from '../lib/motion';

interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  fulfillment_status: string;
  variant_details?: {
    size?: string;
    color?: string;
    sku?: string;
  } | null;
  product?: {
    id: number;
    name: string;
    gallery_images?: { url: string }[];
  };
}

interface Order {
  id: number;
  order_number: string;
  status: string;
  total_amount: number;
  currency: string;
  created_at: string;
  shipping_address?: {
    line1?: string;
    city?: string;
    province?: string;
    postal_code?: string;
    country?: string;
  } | null;
  items: OrderItem[];
}

interface BuyerOrderHistoryModalProps {
  isOpen: boolean;
  onClose: () => void;
  onReviewProduct?: (productId: number) => void;
}

export const BuyerOrderHistoryModal: React.FC<BuyerOrderHistoryModalProps> = ({
  isOpen,
  onClose,
  onReviewProduct,
}) => {
  const { token, isAuthenticated } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useEffect(() => {
    if (isOpen && isAuthenticated && token) {
      setIsLoading(true);
      fetch('/api/v1/orders', {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      })
        .then((res) => res.json())
        .then((data) => {
          setOrders(data.data || []);
        })
        .catch(() => {})
        .finally(() => setIsLoading(false));
    }
  }, [isOpen, isAuthenticated, token]);

  if (!isOpen) return null;

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'paid':
      case 'completed':
        return 'bg-[#4A7C59]/10 text-[#4A7C59] border-[#4A7C59]/30';
      case 'processing':
      case 'shipped':
        return 'bg-[#FF5A36]/10 text-[#FF5A36] border-[#FF5A36]/30';
      case 'cancelled':
      case 'refunded':
        return 'bg-red-50 text-red-600 border-red-200';
      default:
        return 'bg-neutral-100 text-neutral-600 border-neutral-200';
    }
  };

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
          className="relative w-full max-w-3xl max-h-[85vh] flex flex-col bg-[#FAFAF8] text-[#1A1A1A] border border-[#E8E6E1] shadow-2xl overflow-hidden"
          role="dialog"
          aria-modal="true"
        >
          {/* Header */}
          <div className="flex items-center justify-between px-6 py-4 border-b border-[#E8E6E1] bg-white">
            <div className="flex items-center gap-3">
              <span className="font-mono text-xs tracking-widest text-[#FF5A36] uppercase">
                Customer Purchases
              </span>
              <span className="text-[#8C827A]">·</span>
              <h2 className="font-['Space_Grotesk'] text-lg font-bold text-[#1A1A1A]">
                {selectedOrder ? `Order #${selectedOrder.order_number}` : 'Order History'}
              </h2>
            </div>
            <button
              onClick={selectedOrder ? () => setSelectedOrder(null) : onClose}
              className="text-[#8C827A] hover:text-[#1A1A1A] text-sm tracking-wider uppercase font-mono transition-colors"
            >
              {selectedOrder ? '← Back to List' : 'Close ✕'}
            </button>
          </div>

          {/* Content Area */}
          <div className="flex-1 overflow-y-auto p-6">
            {isLoading ? (
              <div className="py-16 text-center text-[#8C827A] font-mono text-sm">
                Retrieving order records...
              </div>
            ) : selectedOrder ? (
              /* Single Order View */
              <div className="space-y-6">
                <div className="p-4 bg-white border border-[#E8E6E1] flex flex-wrap justify-between items-center gap-4">
                  <div>
                    <span className="text-xs font-mono text-[#8C827A]">Order Placed</span>
                    <p className="text-sm font-medium">
                      {new Date(selectedOrder.created_at).toLocaleDateString(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                      })}
                    </p>
                  </div>
                  <div>
                    <span className="text-xs font-mono text-[#8C827A]">Order Status</span>
                    <p className="mt-1">
                      <span
                        className={`text-xs px-2 py-0.5 border font-mono uppercase tracking-wider rounded-sm ${getStatusBadge(
                          selectedOrder.status
                        )}`}
                      >
                        {selectedOrder.status}
                      </span>
                    </p>
                  </div>
                  <div>
                    <span className="text-xs font-mono text-[#8C827A]">Total Amount</span>
                    <p className="font-['Space_Grotesk'] font-bold text-lg text-[#1A1A1A]">
                      ₱{(selectedOrder.total_amount / 100).toFixed(2)}
                    </p>
                  </div>
                </div>

                {/* Items */}
                <div className="border border-[#E8E6E1] bg-white divide-y divide-[#E8E6E1]">
                  {selectedOrder.items?.map((item) => (
                    <div key={item.id} className="p-4 flex items-center justify-between gap-4">
                      <div className="flex-1">
                        <h4 className="font-['Space_Grotesk'] font-semibold text-sm text-[#1A1A1A]">
                          {item.product_name}
                        </h4>
                        <div className="flex items-center gap-2 mt-1">
                          <span className="font-mono text-xs text-[#8C827A]">
                            Qty: {item.quantity} · ₱{(item.unit_price / 100).toFixed(2)} ea
                          </span>
                          {item.variant_details && (
                            <span className="inline-flex items-center px-1.5 py-0.5 bg-[#F0EFEA] text-[11px] font-mono text-[#5A524C]">
                              {item.variant_details.size && `Size: ${item.variant_details.size}`}
                              {item.variant_details.color && ` · ${item.variant_details.color}`}
                            </span>
                          )}
                        </div>
                      </div>
                      <div className="text-right">
                        <span className="font-mono text-sm font-semibold text-[#1A1A1A]">
                          ₱{(item.total_price / 100).toFixed(2)}
                        </span>
                        {onReviewProduct && (
                          <div className="mt-2">
                            <button
                              onClick={() => {
                                onReviewProduct(item.product_id);
                                onClose();
                              }}
                              className="text-[11px] font-mono text-[#FF5A36] hover:underline"
                            >
                              ★ Write Review
                            </button>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : orders.length === 0 ? (
              <div className="py-16 text-center text-[#8C827A] space-y-2">
                <p className="font-['Space_Grotesk'] font-medium text-base text-[#1A1A1A]">
                  No orders placed yet.
                </p>
                <p className="text-xs font-mono">
                  Your receipts, shipping statuses, and order line items will appear here.
                </p>
              </div>
            ) : (
              /* Order List */
              <div className="space-y-3">
                {orders.map((order) => (
                  <div
                    key={order.id}
                    onClick={() => setSelectedOrder(order)}
                    className="p-4 bg-white border border-[#E8E6E1] hover:border-[#1A1A1A] transition-all cursor-pointer flex items-center justify-between gap-4"
                  >
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-['Space_Grotesk'] font-bold text-sm text-[#1A1A1A]">
                          #{order.order_number}
                        </span>
                        <span
                          className={`text-[10px] px-1.5 py-0.2 border font-mono uppercase tracking-wider rounded-sm ${getStatusBadge(
                            order.status
                          )}`}
                        >
                          {order.status}
                        </span>
                      </div>
                      <p className="text-xs font-mono text-[#8C827A] mt-1">
                        {new Date(order.created_at).toLocaleDateString()} · {order.items?.length || 0} items
                      </p>
                    </div>

                    <div className="flex items-center gap-4">
                      <span className="font-['Space_Grotesk'] font-bold text-base text-[#1A1A1A]">
                        ₱{(order.total_amount / 100).toFixed(2)}
                      </span>
                      <span className="text-[#8C827A] text-xs font-mono">View →</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
