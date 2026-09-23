import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';

interface NavbarProps {
  onOpenAuth: () => void;
  onSelectCategory: (slug: string | null) => void;
  selectedCategory: string | null;
  currentSurface: 'storefront' | 'seller' | 'admin';
  onChangeSurface: (surface: 'storefront' | 'seller' | 'admin') => void;
  wishlistCount: number;
  onOpenWishlist: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({
  onOpenAuth,
  onSelectCategory,
  currentSurface,
  onChangeSurface,
  wishlistCount,
  onOpenWishlist,
}) => {
  const { user, isAuthenticated, logout } = useAuth();
  const { cart, setIsCartOpen } = useCart();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <header className="site-header" role="banner">
      {/* Brand Identity / Wordmark matching references/style.css */}
      <div className="flex items-center gap-6">
        <button
          type="button"
          onClick={() => {
            onSelectCategory(null);
            onChangeSurface('storefront');
          }}
          className="wordmark bg-transparent border-0 cursor-pointer text-left select-none text-[#1A1A1A]"
        >
          MAISON<span className="wordmark-dot">.</span>
        </button>

        {/* Main Nav Links (Editorial underline active states) */}
        <nav className="main-nav" aria-label="Main Navigation">
          <button
            type="button"
            onClick={() => onChangeSurface('storefront')}
            className={`nav-link bg-transparent border-0 cursor-pointer ${
              currentSurface === 'storefront' ? 'active' : ''
            }`}
          >
            The Collective
          </button>
          <button
            type="button"
            onClick={() => onChangeSurface('seller')}
            className={`nav-link bg-transparent border-0 cursor-pointer ${
              currentSurface === 'seller' ? 'active' : ''
            }`}
          >
            Seller Studio
          </button>
          <button
            type="button"
            onClick={() => onChangeSurface('admin')}
            className={`nav-link bg-transparent border-0 cursor-pointer ${
              currentSurface === 'admin' ? 'active' : ''
            }`}
          >
            Oversight
          </button>
        </nav>
      </div>

      {/* Header Actions (Search Icon, Wishlist, Bag, Auth) */}
      <div className="header-actions">
        {/* Wishlist Indicator */}
        <button
          type="button"
          onClick={onOpenWishlist}
          className="text-xs text-[#6B6B6B] hover:text-[#1A1A1A] transition-colors cursor-pointer flex items-center gap-1.5"
          title="Wishlist"
          aria-label="Wishlist"
        >
          <span className="text-xs">★</span>
          <span className="hidden sm:inline">Wishlist</span>
          {wishlistCount > 0 && (
            <span className="text-[10px] font-mono px-1 rounded-full bg-[#E8E6E1] text-[#1A1A1A]">
              {wishlistCount}
            </span>
          )}
        </button>

        {/* User Account / Auth */}
        {isAuthenticated ? (
          <div className="flex items-center gap-3">
            <span className="text-xs text-[#1A1A1A] font-medium hidden md:inline">
              {user?.name}
            </span>
            <button
              type="button"
              onClick={logout}
              className="text-xs text-[#6B6B6B] hover:text-[#FF5A36] border-b border-[#E8E6E1] pb-0.5 transition-colors cursor-pointer"
            >
              Sign out
            </button>
          </div>
        ) : (
          <button
            type="button"
            id="open-auth-btn"
            onClick={onOpenAuth}
            className="text-xs font-semibold text-[#1A1A1A] hover:text-[#FF5A36] border-b border-[#1A1A1A] hover:border-[#FF5A36] pb-0.5 transition-colors cursor-pointer"
          >
            Sign in
          </button>
        )}

        {/* Bag Button matching references/style.css */}
        <button
          type="button"
          id="open-cart-btn"
          onClick={() => setIsCartOpen(true)}
          className="bag-button"
          aria-label={`View shopping bag with ${cart.total_quantity} items`}
        >
          <span className="text-xs font-medium text-[#1A1A1A] hidden sm:inline">Bag</span>
          <b>{cart.total_quantity}</b>
        </button>

        {/* Mobile Menu Button */}
        <button
          type="button"
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          className="menu-button"
          aria-label="Toggle Navigation Menu"
        >
          <i />
          <i />
          <i />
        </button>
      </div>

      {/* Mobile Drawer Navigation */}
      {isMobileMenuOpen && (
        <div className="absolute top-[64px] left-0 w-full bg-[#FAFAF8] border-b border-[#E8E6E1] p-6 space-y-4 md:hidden z-50">
          <div className="flex flex-col gap-3 text-sm font-serif">
            <button
              type="button"
              onClick={() => {
                onChangeSurface('storefront');
                setIsMobileMenuOpen(false);
              }}
              className="text-left py-2 border-b border-[#E8E6E1]"
            >
              The Collective Storefront
            </button>
            <button
              type="button"
              onClick={() => {
                onChangeSurface('seller');
                setIsMobileMenuOpen(false);
              }}
              className="text-left py-2 border-b border-[#E8E6E1]"
            >
              Seller Studio & Payouts
            </button>
            <button
              type="button"
              onClick={() => {
                onChangeSurface('admin');
                setIsMobileMenuOpen(false);
              }}
              className="text-left py-2 border-b border-[#E8E6E1]"
            >
              Admin Oversight
            </button>
          </div>
        </div>
      )}
    </header>
  );
};
