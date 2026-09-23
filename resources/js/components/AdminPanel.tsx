import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { Shield, ArrowLeft, Check, X } from 'lucide-react';
import { motion } from 'framer-motion';

interface AdminAnalytics {
  total_gmv: number;
  formatted_gmv: string;
  total_orders: number;
  active_sellers: number;
  pending_sellers: number;
  total_buyers: number;
}

interface AdminPanelProps {
  onBackToStore: () => void;
}

export const AdminPanel: React.FC<AdminPanelProps> = ({ onBackToStore }) => {
  const { token } = useAuth();
  const [overview, setOverview] = useState<AdminAnalytics | null>(null);
  const [recentOrders, setRecentOrders] = useState<any[]>([]);
  const [sellers, setSellers] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'sellers'>('overview');

  useEffect(() => {
    fetchAdminData();
  }, [token]);

  const fetchAdminData = async () => {
    setIsLoading(true);
    try {
      const headers = {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      };

      const res = await fetch('/api/v1/admin/analytics', { headers });
      if (res.ok) {
        const data = await res.json();
        setOverview(data.overview);
        setRecentOrders(data.recent_orders || []);
      }

      const sellersRes = await fetch('/api/v1/admin/sellers', { headers });
      if (sellersRes.ok) {
        const sellersData = await sellersRes.json();
        setSellers(sellersData.data || []);
      }
    } catch (err) {
      console.error('Failed to load admin panel data', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleUpdateSellerStatus = async (sellerId: number, status: 'approved' | 'rejected') => {
    try {
      const res = await fetch(`/api/v1/admin/sellers/${sellerId}/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ status }),
      });

      if (res.ok) {
        setSellers((prev) =>
          prev.map((s) => (s.id === sellerId ? { ...s, verification_status: status } : s))
        );
      }
    } catch (err) {
      console.error('Failed to update seller status', err);
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
                Marketplace Oversight
              </h1>
              <span className="px-2 py-0.5 text-[10px] font-mono uppercase bg-[#1A1A1A] text-white">
                Admin
              </span>
            </div>
            <p className="text-xs text-[#6B6B6B] mt-0.5">
              Multi-Vendor Marketplace Governance, Dispute Oversight & Volume Metrics
            </p>
          </div>
        </div>

        {/* Tab Controls */}
        <div className="flex items-center gap-1 bg-white p-1 border border-[#E8E6E1]">
          <button
            onClick={() => setActiveTab('overview')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer ${
              activeTab === 'overview' ? 'bg-[#1A1A1A] text-white' : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Volume & Orders
          </button>
          <button
            onClick={() => setActiveTab('sellers')}
            className={`px-3 py-1.5 text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'sellers' ? 'bg-[#1A1A1A] text-white' : 'text-[#6B6B6B] hover:text-[#1A1A1A]'
            }`}
          >
            Atelier Moderation
            {overview && overview.pending_sellers > 0 && (
              <span className="px-1.5 py-0.2 bg-[#FF5A36] text-white font-mono rounded-full text-[10px]">
                {overview.pending_sellers}
              </span>
            )}
          </button>
        </div>
      </div>

      {isLoading ? (
        <div className="py-24 text-center text-[#6B6B6B]">Loading governance metrics...</div>
      ) : (
        <div className="mt-8">
          {/* TAB 1: OVERVIEW & GMV */}
          {activeTab === 'overview' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-8">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-2">
                    Gross Merchandise Value
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {overview?.formatted_gmv || '₱0.00'}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Total platform volume</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-2">
                    Total Orders Placed
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {overview?.total_orders || 0}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Checkout sessions completed</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-2">
                    Verified Ateliers
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {overview?.active_sellers || 0}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Active seller profiles</span>
                </div>

                <div className="bg-white border border-[#E8E6E1] p-5">
                  <div className="text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-2">
                    Verified Shoppers (16+)
                  </div>
                  <div className="text-2xl font-serif font-bold text-[#1A1A1A]">
                    {overview?.total_buyers || 0}
                  </div>
                  <span className="text-[11px] text-[#6B6B6B] mt-1 block">Registered buyer accounts</span>
                </div>
              </div>

              {/* Recent Orders Overview */}
              <div className="bg-white border border-[#E8E6E1] overflow-hidden">
                <div className="px-6 py-4 border-b border-[#E8E6E1]">
                  <h2 className="text-xs font-bold text-[#1A1A1A] uppercase tracking-wider font-serif">
                    Recent Marketplace Transactions
                  </h2>
                </div>
                <div className="divide-y divide-[#E8E6E1]">
                  {recentOrders.map((order) => (
                    <div key={order.id} className="px-6 py-4 flex items-center justify-between text-xs">
                      <div>
                        <span className="font-mono font-bold text-[#1A1A1A] block">{order.order_number}</span>
                        <span className="text-[#6B6B6B]">{order.customer_email} · {order.items?.length || 0} cut(s)</span>
                      </div>
                      <div className="text-right">
                        <span className="font-mono font-bold text-[#1A1A1A] block">
                          ₱{(order.total_amount / 100).toFixed(2)}
                        </span>
                        <span className="text-[10px] font-mono uppercase text-[#2E7D5B]">
                          {order.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 2: SELLER APPLICATION MODERATION */}
          {activeTab === 'sellers' && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-4">
              <h2 className="text-lg font-serif font-medium text-[#1A1A1A]">
                Atelier Onboarding Applications
              </h2>
              <div className="bg-white border border-[#E8E6E1] overflow-hidden">
                <table className="w-full text-left text-xs text-[#1A1A1A]">
                  <thead className="bg-[#FAFAF8] text-[#6B6B6B] uppercase text-[10px] tracking-wider border-b border-[#E8E6E1]">
                    <tr>
                      <th className="px-6 py-4 font-bold">Atelier Store Name</th>
                      <th className="px-6 py-4 font-bold">Applicant Email</th>
                      <th className="px-6 py-4 font-bold">Verification</th>
                      <th className="px-6 py-4 text-right font-bold">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#E8E6E1]">
                    {sellers.map((s) => (
                      <tr key={s.id} className="hover:bg-[#FAFAF8]">
                        <td className="px-6 py-4 font-medium text-[#1A1A1A]">{s.store_name}</td>
                        <td className="px-6 py-4 text-[#6B6B6B]">{s.user?.email || 'N/A'}</td>
                        <td className="px-6 py-4">
                          <span
                            className={`px-2 py-0.5 text-[10px] font-mono uppercase ${
                              s.verification_status === 'approved'
                                ? 'bg-emerald-50 text-[#2E7D5B] border border-emerald-200'
                                : s.verification_status === 'rejected'
                                ? 'bg-red-50 text-[#D14343] border border-red-200'
                                : 'bg-[#FFF5F2] text-[#FF5A36] border border-[#FF5A36]/30'
                            }`}
                          >
                            {s.verification_status}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-right space-x-2">
                          {s.verification_status !== 'approved' && (
                            <button
                              onClick={() => handleUpdateSellerStatus(s.id, 'approved')}
                              className="px-2.5 py-1 bg-[#2E7D5B] text-white font-medium text-xs inline-flex items-center gap-1 transition-colors cursor-pointer"
                            >
                              <Check className="w-3.5 h-3.5" />
                              <span>Approve</span>
                            </button>
                          )}
                          {s.verification_status !== 'rejected' && (
                            <button
                              onClick={() => handleUpdateSellerStatus(s.id, 'rejected')}
                              className="px-2.5 py-1 bg-[#D14343] text-white font-medium text-xs inline-flex items-center gap-1 transition-colors cursor-pointer"
                            >
                              <X className="w-3.5 h-3.5" />
                              <span>Reject</span>
                            </button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </motion.div>
          )}
        </div>
      )}
    </div>
  );
};
