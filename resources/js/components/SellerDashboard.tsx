import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { DollarSign, Truck, AlertTriangle, ArrowLeft } from 'lucide-react';
import { motion } from 'framer-motion';

interface SellerStats {
  total_revenue: number;
  formatted_revenue: string;
  pending_fulfillment: number;
  items_sold: number;
  total_listings: number;
  low_stock_count: number;
}

interface SellerProfileData {
  id: number;
  store_name: string;
  slug: string;
  verification_status: string;
  stripe_account_id?: string | null;
  bio?: string | null;
}

interface SellerDashboardProps {
  onBackToStore: () => void;
}

export const SellerDashboard: React.FC<SellerDashboardProps> = ({ onBackToStore }) => {
  const { user, token } = useAuth();
  const [stats, setStats] = useState<SellerStats | null>(null);
  const [seller, setSeller] = useState<SellerProfileData | null>(null);
  const [lowStockAlerts, setLowStockAlerts] = useState<any[]>([]);
  const [orders, setOrders] = useState<any[]>([]);
  const [sellerProducts, setSellerProducts] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'products' | 'orders' | 'payouts'>('overview');
  const [isUpdatingOrder, setIsUpdatingOrder] = useState<number | null>(null);
  const [payoutSuccess, setPayoutSuccess] = useState<string | null>(null);
  const [editingProduct, setEditingProduct] = useState<any | null>(null);

  useEffect(() => {
    fetchDashboardData();
  }, [token]);

  const fetchDashboardData = async () => {
    setIsLoading(true);
    try {
      const headers = {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      };

      const res = await fetch('/api/v1/seller/dashboard', { headers });
      if (res.ok) {
        const data = await res.json();
        setStats(data.stats);
        setSeller(data.seller);
        setLowStockAlerts(data.low_stock_alerts || []);
      }

      const ordersRes = await fetch('/api/v1/seller/orders', { headers });
      if (ordersRes.ok) {
        const ordersData = await ordersRes.json();
        setOrders(ordersData.data || []);
      }

      const productsRes = await fetch('/api/v1/seller/products', { headers });
      if (productsRes.ok) {
        const productsData = await productsRes.json();
        setSellerProducts(productsData.data || []);
      }
    } catch (err) {
      console.error('Failed to load seller dashboard', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleArchiveProduct = async (productId: number) => {
    if (!confirm('Are you sure you want to archive this garment cut?')) return;
    try {
      const res = await fetch(`/api/v1/seller/products/${productId}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });
      if (res.ok) {
        setSellerProducts((prev) =>
          prev.map((p) => (p.id === productId ? { ...p, status: 'archived', is_active: false } : p))
        );
      }
    } catch (err) {
      console.error('Failed to archive product', err);
    }
  };

  const handleSaveProductEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingProduct) return;

    try {
      const res = await fetch(`/api/v1/seller/products/${editingProduct.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          name: editingProduct.name,
          price: parseInt(editingProduct.price, 10),
          stock: parseInt(editingProduct.stock, 10),
          description: editingProduct.description,
        }),
      });

      if (res.ok) {
        const updated = await res.json();
        setSellerProducts((prev) =>
          prev.map((p) => (p.id === editingProduct.id ? updated.product : p))
        );
        setEditingProduct(null);
      }
    } catch (err) {
      console.error('Failed to update product', err);
    }
  };

  const handleUpdateFulfillment = async (orderItemId: number, status: string) => {
    setIsUpdatingOrder(orderItemId);
    try {
      const res = await fetch(`/api/v1/seller/orders/${orderItemId}/fulfillment`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ status }),
      });

      if (res.ok) {
        setOrders((prev) =>
          prev.map((item) =>
            item.id === orderItemId ? { ...item, fulfillment_status: status } : item
          )
        );
      }
    } catch (err) {
      console.error('Failed to update fulfillment', err);
    } finally {
      setIsUpdatingOrder(null);
    }
  };

  const handleSetupPayout = async () => {
    try {
      const res = await fetch('/api/v1/seller/payout-setup', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({}),
      });

      if (res.ok) {
        const data = await res.json();
        setPayoutSuccess(`Connected Stripe Account: ${data.stripe_account_id}`);
        if (seller) {
          setSeller({ ...seller, stripe_account_id: data.stripe_account_id });
        }
        if (data.onboarding_url) {
          window.open(data.onboarding_url, '_blank');
        }
      }
    } catch (err) {
      console.error('Payout setup failed', err);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
      {/* Top Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-[#E8E6E1]">
        <div className="flex items-center gap-3">
          <button
            onClick={onBackToStore}
            className="p-2 border border-[#E8E6E1] bg-white text-[#1A1A1A] hover:bg-[#FAFAF8] transition-colors cursor-pointer"
            aria-label="Back to Storefront"
          >
            <ArrowLeft className="w-4 h-4" />
          </button>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-2xl font-serif font-medium text-[#1A1A1A] tracking-tight">
                {seller?.store_name || 'Atelier Studio'}
              </h1>
              <span className="px-2 py-0.5 text-[10px] font-mono uppercase bg-[#2E7D5B]/10 text-[#2E7D5B] border border-[#2E7D5B]/20">
                Verified Atelier
              </span>
            </div>
            <p className="text-xs text-[#6B6B6B] mt-0.5">
              Decentralized Merchant Operations & Stripe Connect Settlement
            </p>
          </div>
        </div>

        {/* Navigation Tabs */}
        <div className="flex items-center gap-1 bg-white p-1 border border-[#E8E6E1]">
          <button
            onClick={() => setActiveTab('overview')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer ${
              activeTab === 'overview'
                ? 'bg-[#1A1A1A] text-white'
                : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Overview
          </button>
          <button
            onClick={() => setActiveTab('products')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer ${
              activeTab === 'products'
                ? 'bg-[#1A1A1A] text-white'
                : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Cuts & Inventory
          </button>
          <button
            onClick={() => setActiveTab('orders')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'orders'
                ? 'bg-[#1A1A1A] text-white'
                : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Dispatch Queue
            {stats && stats.pending_fulfillment > 0 && (
              <span className="px-1.5 py-0.2 bg-[#FF5A36] text-white font-mono rounded-full text-[10px]">
                {stats.pending_fulfillment}
              </span>
            )}
          </button>
          <button
            onClick={() => setActiveTab('payouts')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer ${
              activeTab === 'payouts'
                ? 'bg-[#1A1A1A] text-white'
                : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Stripe Payouts
          </button>
        </div>
      </div>

      {isLoading ? (
        <div className="py-24 text-center text-[#6B6B6B]">Loading atelier operations...</div>
      ) : (
        <div className="mt-8">
          {/* TAB 1: OVERVIEW METRICS */}
          {activeTab === 'overview' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-8">
              {/* Stat Cards Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="flex items-center justify-between text-[#6B6B6B] mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Gross Sales</span>
                    <DollarSign className="w-4 h-4 text-[#2E7D5B]" />
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {stats?.formatted_revenue || '₱0.00'}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Live Stripe Connect earnings</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="flex items-center justify-between text-[#6B6B6B] mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Orders to Dispatch</span>
                    <Truck className="w-4 h-4 text-[#FF5A36]" />
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {stats?.pending_fulfillment || 0}
                  </div>
                  <span className="text-[11px] text-[#FF5A36] mt-1 block">Awaiting package fulfillment</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="flex items-center justify-between text-[#6B6B6B] mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Pieces Dispatched</span>
                    <span className="text-xs font-mono text-[#1A1A1A]">QTY</span>
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {stats?.items_sold || 0}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Completed garment sales</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="flex items-center justify-between text-[#6B6B6B] mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Cataloged Cuts</span>
                    <span className="text-xs font-mono text-[#1A1A1A]">SKUs</span>
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {stats?.total_listings || 0}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Active silhouettes on the rail</span>
                </div>
              </div>

              {/* Low Stock Warning Banner */}
              {lowStockAlerts.length > 0 && (
                <div className="p-4 bg-[#FFF5F2] border border-[#FF5A36]/30">
                  <div className="flex items-center gap-2 mb-2 text-[#CC3F20]">
                    <AlertTriangle className="w-4 h-4" />
                    <h3 className="text-xs font-bold uppercase tracking-wider">
                      Inventory Depletion Alerts ({lowStockAlerts.length})
                    </h3>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    {lowStockAlerts.map((item, idx) => (
                      <div key={idx} className="p-3 bg-white border border-[#E8E6E1] text-xs">
                        <div className="font-medium text-[#1A1A1A] truncate">{item.product_name}</div>
                        <div className="text-[#6B6B6B] text-[11px] mt-0.5">
                          Cut: {item.size} / {item.color}
                        </div>
                        <div className="text-[#CC3F20] font-mono font-bold mt-1">
                          Only {item.stock_quantity} remaining
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </motion.div>
          )}

          {/* TAB: CUTS & INVENTORY */}
          {activeTab === 'products' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <h2 className="text-lg font-serif font-medium text-[#1A1A1A]">Atelier Garment Cuts</h2>
                  <p className="text-xs text-[#6B6B6B] mt-0.5">Manage live silhouettes, adjust prices, edit inventory or archive items.</p>
                </div>
              </div>

              {sellerProducts.length === 0 ? (
                <div className="py-16 text-center bg-white border border-[#E8E6E1] text-[#6B6B6B] text-xs">
                  No products currently cataloged.
                </div>
              ) : (
                <div className="border border-[#E8E6E1] bg-white divide-y divide-[#E8E6E1]">
                  {sellerProducts.map((p) => (
                    <div key={p.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div>
                        <div className="flex items-center gap-2">
                          <h4 className="font-['Space_Grotesk'] font-bold text-sm text-[#1A1A1A]">{p.name}</h4>
                          <span
                            className={`px-1.5 py-0.5 text-[10px] font-mono uppercase tracking-wider rounded-sm ${
                              p.status === 'active'
                                ? 'bg-emerald-50 text-[#2E7D5B] border border-emerald-200'
                                : 'bg-neutral-100 text-neutral-500 border border-neutral-200'
                            }`}
                          >
                            {p.status}
                          </span>
                        </div>
                        <div className="text-xs text-[#6B6B6B] mt-1 font-mono">
                          SKU: {p.sku} · Base Stock: {p.stock} units · {p.variants?.length || 0} variants
                        </div>
                      </div>

                      <div className="flex items-center gap-3">
                        <span className="font-mono text-sm font-bold text-[#1A1A1A]">
                          ₱{(p.price / 100).toFixed(2)}
                        </span>

                        <button
                          type="button"
                          onClick={() => setEditingProduct({ ...p })}
                          className="px-2.5 py-1 text-xs font-mono border border-[#E8E6E1] text-[#1A1A1A] hover:border-[#1A1A1A] transition-colors"
                        >
                          Edit
                        </button>

                        {p.status !== 'archived' && (
                          <button
                            type="button"
                            onClick={() => handleArchiveProduct(p.id)}
                            className="px-2.5 py-1 text-xs font-mono text-[#D14343] hover:underline"
                          >
                            Archive
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </motion.div>
          )}

          {/* EDIT PRODUCT MODAL */}
          {editingProduct && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
              <div className="relative w-full max-w-lg bg-[#FAFAF8] text-[#1A1A1A] border border-[#E8E6E1] p-6 shadow-2xl">
                <div className="flex items-center justify-between pb-4 border-b border-[#E8E6E1] mb-4">
                  <h3 className="font-['Space_Grotesk'] font-bold text-base">Edit Garment: {editingProduct.name}</h3>
                  <button
                    onClick={() => setEditingProduct(null)}
                    className="text-[#6B6B6B] hover:text-[#1A1A1A] font-mono text-sm"
                  >
                    ✕
                  </button>
                </div>

                <form onSubmit={handleSaveProductEdit} className="space-y-4">
                  <div>
                    <label className="block text-xs font-mono text-[#6B6B6B] mb-1">Product Title</label>
                    <input
                      type="text"
                      value={editingProduct.name}
                      onChange={(e) => setEditingProduct({ ...editingProduct, name: e.target.value })}
                      className="w-full px-3 py-2 text-sm bg-white border border-[#E8E6E1] focus:border-[#1A1A1A] outline-none"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-mono text-[#6B6B6B] mb-1">Price (Centavos)</label>
                      <input
                        type="number"
                        value={editingProduct.price}
                        onChange={(e) => setEditingProduct({ ...editingProduct, price: e.target.value })}
                        className="w-full px-3 py-2 text-sm bg-white border border-[#E8E6E1] focus:border-[#1A1A1A] outline-none"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-mono text-[#6B6B6B] mb-1">Stock Quantity</label>
                      <input
                        type="number"
                        value={editingProduct.stock}
                        onChange={(e) => setEditingProduct({ ...editingProduct, stock: e.target.value })}
                        className="w-full px-3 py-2 text-sm bg-white border border-[#E8E6E1] focus:border-[#1A1A1A] outline-none"
                        required
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-mono text-[#6B6B6B] mb-1">Description</label>
                    <textarea
                      value={editingProduct.description || ''}
                      onChange={(e) => setEditingProduct({ ...editingProduct, description: e.target.value })}
                      rows={3}
                      className="w-full px-3 py-2 text-sm bg-white border border-[#E8E6E1] focus:border-[#1A1A1A] outline-none"
                    />
                  </div>

                  <div className="flex justify-end gap-3 pt-3 border-t border-[#E8E6E1]">
                    <button
                      type="button"
                      onClick={() => setEditingProduct(null)}
                      className="px-4 py-2 text-xs font-mono text-[#6B6B6B] hover:text-[#1A1A1A]"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 text-xs font-bold uppercase tracking-wider bg-[#1A1A1A] text-white hover:bg-black transition-colors"
                    >
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

          {/* TAB 2: FULFILLMENT QUEUE */}
          {activeTab === 'orders' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-4">
              <h2 className="text-lg font-serif font-medium text-[#1A1A1A]">Line-Item Dispatch Queue</h2>
              {orders.length === 0 ? (
                <div className="py-16 text-center bg-white border border-[#E8E6E1] text-[#6B6B6B] text-xs">
                  All orders have been dispatched. No pending shipments.
                </div>
              ) : (
                <div className="space-y-3">
                  {orders.map((order) => (
                    <div
                      key={order.id}
                      className="p-4 bg-white border border-[#E8E6E1] flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-xs font-bold text-[#1A1A1A]">
                            Order #{order.order_number}
                          </span>
                          <span
                            className={`px-2 py-0.5 text-[10px] font-mono uppercase ${
                              order.order_status === 'paid'
                                ? 'bg-emerald-50 text-[#2E7D5B] border border-emerald-200'
                                : 'bg-amber-50 text-amber-700 border border-amber-200'
                            }`}
                          >
                            {order.order_status === 'paid' ? 'Payment Confirmed' : 'Payment Pending'}
                          </span>
                          <span
                            className={`px-2 py-0.5 text-[10px] font-mono uppercase ${
                              order.fulfillment_status === 'shipped'
                                ? 'bg-emerald-50 text-[#2E7D5B] border border-emerald-200'
                                : 'bg-[#FFF5F2] text-[#FF5A36] border border-[#FF5A36]/30'
                            }`}
                          >
                            {order.fulfillment_status}
                          </span>
                        </div>
                        <div className="text-xs text-[#1A1A1A] mt-1 font-medium">
                          {order.product_name} — Qty: {order.quantity}
                        </div>
                        <div className="text-[11px] text-[#6B6B6B]">
                          SKU: {order.sku} · Size: {order.size} · Color: {order.color}
                        </div>
                      </div>

                      <div className="flex items-center gap-3">
                        <div className="text-right">
                          <div className="font-mono text-xs font-bold text-[#1A1A1A]">
                            {order.formatted_total}
                          </div>
                          <div className="text-[10px] text-[#6B6B6B]">{order.created_at}</div>
                        </div>

                        {order.order_status !== 'paid' ? (
                          <span className="px-2.5 py-1 text-[10px] font-mono uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200">
                            Awaiting Payment
                          </span>
                        ) : order.fulfillment_status !== 'shipped' && (
                          <button
                            type="button"
                            onClick={() => handleUpdateFulfillment(order.id, 'shipped')}
                            disabled={isUpdatingOrder === order.id}
                            className="px-3 py-1.5 text-xs font-bold uppercase tracking-wider bg-[#FF5A36] text-white hover:bg-[#E64A28] transition-colors cursor-pointer"
                          >
                            {isUpdatingOrder === order.id ? 'Updating...' : 'Mark Dispatched'}
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </motion.div>
          )}

          {/* TAB 3: STRIPE PAYOUTS */}
          {activeTab === 'payouts' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-6">
              <div className="p-6 bg-white border border-[#E8E6E1] space-y-4">
                <h2 className="text-lg font-serif font-medium text-[#1A1A1A]">
                  Stripe Connect Payout Settlement
                </h2>
                <p className="text-xs text-[#6B6B6B] leading-relaxed max-w-xl">
                  Each purchase processed through the collective uses a Stripe Connect transfer group.
                  Your merchant share is atomically allocated upon customer payment confirmation.
                </p>

                {seller?.stripe_account_id ? (
                  <div className="p-4 bg-[#FAFAF8] border border-[#E8E6E1] text-xs space-y-1">
                    <span className="text-[10px] font-mono uppercase text-[#2E7D5B] font-bold block">
                      Connected Account Active
                    </span>
                    <div className="font-mono font-semibold text-[#1A1A1A]">
                      {seller.stripe_account_id}
                    </div>
                    <p className="text-[11px] text-[#6B6B6B]">
                      Transfers automatically clear to your designated financial account.
                    </p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    <button
                      type="button"
                      onClick={handleSetupPayout}
                      className="px-5 py-2.5 bg-[#FF5A36] hover:bg-[#E64A28] text-white text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer"
                    >
                      Connect Stripe Express Account
                    </button>
                    {payoutSuccess && (
                      <div className="p-3 bg-emerald-50 border border-emerald-200 text-xs text-[#2E7D5B]">
                        {payoutSuccess}
                      </div>
                    )}
                  </div>
                )}
              </div>
            </motion.div>
          )}
        </div>
      )}
    </div>
  );
};
