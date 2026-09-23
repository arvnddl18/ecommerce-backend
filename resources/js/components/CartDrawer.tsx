import React, { useState } from 'react';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { motion, AnimatePresence } from 'framer-motion';
import { cartDrawerVariants } from '../lib/motion';

export const CartDrawer: React.FC = () => {
  const { cart, isCartOpen, setIsCartOpen, updateQuantity, removeFromCart, applyCoupon, removeCoupon } = useCart();
  const { user } = useAuth();

  // 3-Step Checkout state: 1: Bag, 2: Delivery, 3: Confirmation
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [customerEmail, setCustomerEmail] = useState<string>(user?.email || '');
  const [shippingAddress, setShippingAddress] = useState({
    line1: '100 Mercer St',
    city: 'New York',
    province: 'NY',
    postal_code: '10012',
    country: 'US',
  });
  const [isCheckingOut, setIsCheckingOut] = useState<boolean>(false);
  const [checkoutError, setCheckoutError] = useState<string | null>(null);
  const [couponInput, setCouponInput] = useState<string>('');
  const [couponError, setCouponError] = useState<string | null>(null);
  const [isApplyingCoupon, setIsApplyingCoupon] = useState<boolean>(false);

  if (!isCartOpen) return null;

  const handleProceedToShipping = () => {
    setCheckoutError(null);
    if (!user && (!customerEmail || !customerEmail.includes('@'))) {
      setCheckoutError('Please provide a valid recipient email address.');
      return;
    }
    setStep(2);
  };

  const handleInitiateStripeCheckout = async () => {
    setCheckoutError(null);
    const emailToUse = user?.email || customerEmail.trim();

    try {
      setIsCheckingOut(true);
      const cartToken = localStorage.getItem('cart_token');

      const response = await fetch('/api/v1/checkout/session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Cart-Token': cartToken || '',
        },
        body: JSON.stringify({
          customer_email: emailToUse,
          customer_name: user?.name || emailToUse.split('@')[0],
          shipping_address: shippingAddress,
          cart_token: cartToken,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to create checkout session.');
      }

      if (data.checkout_url) {
        window.location.href = data.checkout_url;
      } else {
        throw new Error('No checkout URL returned from payment server.');
      }
    } catch (err: any) {
      setCheckoutError(err.message || 'An error occurred initiating checkout.');
      setIsCheckingOut(false);
    }
  };

  const handleApplyCoupon = async () => {
    if (!couponInput.trim()) return;
    setCouponError(null);
    setIsApplyingCoupon(true);
    try {
      await applyCoupon(couponInput.trim().toUpperCase());
      setCouponInput('');
    } catch (err: any) {
      setCouponError(err.message || 'Failed to apply coupon.');
    } finally {
      setIsApplyingCoupon(false);
    }
  };

  const handleRemoveCoupon = async () => {
    setCouponError(null);
    try {
      await removeCoupon();
    } catch (err: any) {
      setCouponError(err.message || 'Failed to remove coupon.');
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <AnimatePresence>
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={() => setIsCartOpen(false)}
          className="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
        />
      </AnimatePresence>

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-6 sm:pl-10">
        <motion.div
          variants={cartDrawerVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
          className="w-screen max-w-md bg-[#FAFAF8] text-[#1A1A1A] border-l border-[#E8E6E1] shadow-2xl flex flex-col justify-between"
        >
          {/* Header */}
          <div className="p-6 border-b border-[#E8E6E1] bg-white">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <span className="font-serif font-bold text-lg tracking-tight">Shopping Bag</span>
                <span className="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#FAFAF8] border border-[#E8E6E1] text-[#6B6B6B]">
                  {cart.total_quantity} {cart.total_quantity === 1 ? 'piece' : 'pieces'}
                </span>
              </div>

              <button
                type="button"
                onClick={() => setIsCartOpen(false)}
                className="w-7 h-7 rounded-full border border-[#E8E6E1] bg-[#FAFAF8] text-[#1A1A1A] hover:bg-[#1A1A1A] hover:text-white flex items-center justify-center text-xs font-mono transition-colors cursor-pointer"
                aria-label="Close cart"
              >
                ✕
              </button>
            </div>

            {/* Sequence Flow (No cliché numbered markers) */}
            {cart.items.length > 0 && (
              <div className="grid grid-cols-2 gap-3 pt-3 text-[10px] font-mono tracking-widest uppercase border-t border-[#E8E6E1]">
                <div
                  className={`pb-1 border-b transition-colors ${
                    step >= 1 ? 'border-[#1A1A1A] text-[#1A1A1A] font-bold' : 'border-transparent text-[#6B6B6B]'
                  }`}
                >
                  Selected Garments
                </div>
                <div
                  className={`pb-1 border-b transition-colors ${
                    step >= 2 ? 'border-[#FF5A36] text-[#FF5A36] font-bold' : 'border-transparent text-[#6B6B6B]'
                  }`}
                >
                  Delivery & Settlement
                </div>
              </div>
            )}
          </div>

          {/* Cart Drawer Body */}
          <div className="flex-1 overflow-y-auto p-6 space-y-4">
            {cart.items.length === 0 ? (
              <div className="h-full flex flex-col items-center justify-center text-center py-16">
                <div className="w-12 h-12 rounded-full border border-[#E8E6E1] flex items-center justify-center mb-3 text-[#6B6B6B] font-mono text-sm">
                  00
                </div>
                <h3 className="text-base font-serif font-medium text-[#1A1A1A]">Your bag is empty</h3>
                <p className="text-xs text-[#6B6B6B] mt-1 max-w-xs">
                  Discover curated silhouettes and seasonal drops on the garment rail.
                </p>
                <button
                  type="button"
                  onClick={() => setIsCartOpen(false)}
                  className="mt-6 text-xs font-bold text-[#FF5A36] border-b border-[#FF5A36] pb-1 cursor-pointer"
                >
                  Browse The Garment Rail
                </button>
              </div>
            ) : step === 1 ? (
              /* STEP 1: GARMENTS LIST */
              <div className="space-y-4">
                {cart.items.map((item) => (
                  <div
                    key={item.item_key || `${item.product_id}_${item.variant_id || 0}`}
                    className="flex gap-4 p-3 bg-white border border-[#E8E6E1] items-center justify-between"
                  >
                    <div className="w-16 h-20 bg-[#F0EEE9] shrink-0 border border-[#E8E6E1] overflow-hidden">
                      {item.image ? (
                        <img
                          src={item.image}
                          alt={item.name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-[10px] text-[#6B6B6B] font-mono">
                          N/A
                        </div>
                      )}
                    </div>

                    <div className="flex-1 min-w-0 pr-2">
                      <h4 className="text-xs font-medium font-serif text-[#1A1A1A] truncate">
                        {item.name}
                      </h4>
                      {(item.size || item.color) && (
                        <div className="text-[10px] font-mono text-[#FF5A36] mt-0.5">
                          {[item.size, item.color].filter(Boolean).join(' · ')}
                        </div>
                      )}
                      <div className="text-xs font-mono text-[#6B6B6B] mt-0.5">
                        {item.formatted_price}
                      </div>

                      {/* Quantity Controls */}
                      <div className="flex items-center gap-2 mt-2">
                        <div className="flex items-center border border-[#E8E6E1] bg-[#FAFAF8]">
                          <button
                            type="button"
                            onClick={() => updateQuantity(item.item_key || item.product_id, item.quantity - 1)}
                            disabled={item.quantity <= 1}
                            className="px-2 py-0.5 text-xs text-[#6B6B6B] hover:text-[#1A1A1A] disabled:opacity-30 cursor-pointer"
                          >
                            −
                          </button>
                          <span className="px-2 text-xs font-mono font-medium text-[#1A1A1A]">
                            {item.quantity}
                          </span>
                          <button
                            type="button"
                            onClick={() => updateQuantity(item.item_key || item.product_id, item.quantity + 1)}
                            disabled={item.quantity >= item.stock}
                            className="px-2 py-0.5 text-xs text-[#6B6B6B] hover:text-[#1A1A1A] disabled:opacity-30 cursor-pointer"
                          >
                            +
                          </button>
                        </div>

                        <button
                          type="button"
                          onClick={() => removeFromCart(item.item_key || item.product_id)}
                          className="text-[10px] text-[#6B6B6B] hover:text-[#D14343] transition-colors cursor-pointer ml-1"
                        >
                          Remove
                        </button>
                      </div>
                    </div>

                    <div className="text-right shrink-0">
                      <div className="text-xs font-bold font-mono text-[#1A1A1A]">
                        {item.formatted_total}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              /* STEP 2: SHIPPING ADDRESS */
              <div className="space-y-4">
                <div className="text-xs font-bold uppercase tracking-wider text-[#1A1A1A] pb-2 border-b border-[#E8E6E1]">
                  Delivery Address
                </div>

                <div className="space-y-3 text-xs">
                  <div>
                    <label className="text-[#6B6B6B] block mb-1">Street Address</label>
                    <input
                      type="text"
                      value={shippingAddress.line1}
                      onChange={(e) =>
                        setShippingAddress({ ...shippingAddress, line1: e.target.value })
                      }
                      className="w-full p-2.5 bg-white border border-[#E8E6E1] text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-[#6B6B6B] block mb-1">City</label>
                      <input
                        type="text"
                        value={shippingAddress.city}
                        onChange={(e) =>
                          setShippingAddress({ ...shippingAddress, city: e.target.value })
                        }
                        className="w-full p-2.5 bg-white border border-[#E8E6E1] text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                      />
                    </div>
                    <div>
                      <label className="text-[#6B6B6B] block mb-1">State / Region</label>
                      <input
                        type="text"
                        value={shippingAddress.province}
                        onChange={(e) =>
                          setShippingAddress({ ...shippingAddress, province: e.target.value })
                        }
                        className="w-full p-2.5 bg-white border border-[#E8E6E1] text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-[#6B6B6B] block mb-1">Postal Code</label>
                      <input
                        type="text"
                        value={shippingAddress.postal_code}
                        onChange={(e) =>
                          setShippingAddress({
                            ...shippingAddress,
                            postal_code: e.target.value,
                          })
                        }
                        className="w-full p-2.5 bg-white border border-[#E8E6E1] text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                      />
                    </div>
                    <div>
                      <label className="text-[#6B6B6B] block mb-1">Country</label>
                      <select
                        value={shippingAddress.country}
                        onChange={(e) =>
                          setShippingAddress({ ...shippingAddress, country: e.target.value })
                        }
                        className="w-full p-2.5 bg-white border border-[#E8E6E1] text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A] cursor-pointer"
                      >
                        <option value="US">United States (US)</option>
                        <option value="CA">Canada (CA)</option>
                        <option value="GB">United Kingdom (GB)</option>
                        <option value="FR">France (FR)</option>
                        <option value="DE">Germany (DE)</option>
                        <option value="JP">Japan (JP)</option>
                        <option value="PH">Philippines (PH)</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Footer Actions */}
          {cart.items.length > 0 && (
            <div className="p-6 border-t border-[#E8E6E1] bg-white space-y-4">
              <div className="space-y-2 text-xs text-[#6B6B6B]">
                <div className="flex justify-between">
                  <span>Bag Subtotal</span>
                  <span className="font-mono font-medium text-[#1A1A1A]">
                    {cart.formatted_subtotal}
                  </span>
                </div>
                {cart.coupon && (
                  <div className="flex justify-between text-[#2E7D5B] font-medium">
                    <span>Voucher ({cart.coupon.code})</span>
                    <span className="font-mono">-{cart.formatted_discount || '$0.00'}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span>Standard Fulfillment</span>
                  <span className="text-[#2E7D5B] font-medium">Complimentary</span>
                </div>
                <div className="flex justify-between pt-2 border-t border-[#E8E6E1] text-sm font-bold text-[#1A1A1A]">
                  <span>Total Amount</span>
                  <span className="font-mono text-base">{cart.formatted_total || cart.formatted_subtotal}</span>
                </div>
              </div>

              {/* Coupon Code Section */}
              <div className="pt-2 border-t border-[#E8E6E1]">
                {cart.coupon ? (
                  <div className="flex items-center justify-between p-2 bg-[#F0FAF4] border border-[#BDE5CE] text-xs">
                    <span className="text-[#2E7D5B] font-mono font-semibold">
                      Applied: {cart.coupon.code} (-{cart.formatted_discount})
                    </span>
                    <button
                      type="button"
                      onClick={handleRemoveCoupon}
                      className="text-[10px] text-[#6B6B6B] hover:text-[#D14343] underline cursor-pointer"
                    >
                      Remove
                    </button>
                  </div>
                ) : (
                  <div className="space-y-1">
                    <div className="flex gap-2">
                      <input
                        type="text"
                        placeholder="VOUCHER / PROMO CODE"
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value.toUpperCase())}
                        className="flex-1 px-3 py-1.5 text-xs bg-[#FAFAF8] border border-[#E8E6E1] text-[#1A1A1A] font-mono uppercase focus:outline-none focus:border-[#1A1A1A]"
                      />
                      <button
                        type="button"
                        onClick={handleApplyCoupon}
                        disabled={isApplyingCoupon || !couponInput.trim()}
                        className="px-3 py-1.5 bg-[#1A1A1A] text-white text-xs font-mono disabled:opacity-40 hover:bg-[#333] transition-colors cursor-pointer"
                      >
                        {isApplyingCoupon ? '...' : 'Apply'}
                      </button>
                    </div>
                    {couponError && (
                      <p className="text-[11px] text-[#D14343] font-sans">{couponError}</p>
                    )}
                  </div>
                )}
              </div>

              {!user && step === 1 && (
                <div className="space-y-1">
                  <label className="text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B]">
                    Dispatch Receipt Email (16+)
                  </label>
                  <input
                    type="email"
                    required
                    placeholder="name@atelier.com"
                    value={customerEmail}
                    onChange={(e) => setCustomerEmail(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#FAFAF8] border border-[#E8E6E1] text-[#1A1A1A] placeholder-[#6B6B6B] focus:outline-none focus:border-[#1A1A1A]"
                  />
                </div>
              )}

              {checkoutError && (
                <div className="p-3 bg-red-50 border border-red-200 text-xs text-[#D14343]">
                  {checkoutError}
                </div>
              )}

              {/* Step Navigation Buttons */}
              {step === 1 ? (
                <button
                  type="button"
                  onClick={handleProceedToShipping}
                  className="w-full py-3.5 bg-[#FF5A36] hover:bg-[#E64A28] active:bg-[#CC3F20] text-white font-bold text-xs uppercase tracking-widest transition-colors cursor-pointer"
                >
                  Proceed to Delivery
                </button>
              ) : (
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setStep(1)}
                    className="px-4 py-3 bg-[#FAFAF8] border border-[#E8E6E1] hover:bg-[#E8E6E1] text-[#1A1A1A] text-xs font-medium cursor-pointer"
                  >
                    Back
                  </button>
                  <button
                    type="button"
                    onClick={handleInitiateStripeCheckout}
                    disabled={isCheckingOut}
                    className="flex-1 py-3 bg-[#FF5A36] hover:bg-[#E64A28] disabled:opacity-50 text-white font-bold text-xs uppercase tracking-widest transition-colors cursor-pointer"
                  >
                    {isCheckingOut ? 'Opening Stripe Checkout...' : 'Confirm Order via Stripe'}
                  </button>
                </div>
              )}

              <div className="text-center text-[10px] text-[#6B6B6B] tracking-wider uppercase">
                Stripe Connect Transfer Group · Atomic Inventory Lock
              </div>
            </div>
          )}
        </motion.div>
      </div>
    </div>
  );
};
