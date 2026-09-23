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
  onOpenOrders?: () => void;
  onNavigateHome?: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({
  onOpenAuth,
  onSelectCategory,
  currentSurface,
  onChangeSurface,
  wishlistCount,
  onOpenWishlist,
  onOpenOrders,
  onNavigateHome,
}) => {
  const { user, isAuthenticated, logout } = useAuth();
  const { cart, setIsCartOpen } = useCart();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <header className="site-header" role="banner">
      {/* Brand Identity / Wordmark matching references/index.html */}
      <div className="flex items-center gap-6">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => {
              onSelectCategory(null);
              onChangeSurface('storefront');
              if (onNavigateHome) onNavigateHome();
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }}
            className="wordmark bg-transparent border-0 cursor-pointer text-left select-none text-[#1A1A1A]"
          >
            FOLD<span>.</span>
          </button>
          {currentSurface === 'seller' && (
            <span className="text-[10px] font-mono uppercase tracking-widest text-[#FF5A36] px-2 py-0.5 border border-[#FF5A36]/30 bg-[#FF5A36]/5 hidden sm:inline-block">
              Atelier Studio
            </span>
          )}
          {currentSurface === 'admin' && (
            <span className="text-[10px] font-mono uppercase tracking-widest text-[#D14343] px-2 py-0.5 border border-[#D14343]/30 bg-[#D14343]/5 hidden sm:inline-block">
              Oversight
            </span>
          )}
        </div>

        {/* Main Nav Links (New arrivals / Shop all / Our edit) */}
        <nav className="main-nav" aria-label="Main Navigation">
          {currentSurface === 'storefront' ? (
            <>
              <button
                type="button"
                onClick={() => {
                  onSelectCategory(null);
                  if (onNavigateHome) onNavigateHome();
                  const el = document.getElementById('products');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                className="nav-link active bg-transparent border-0 cursor-pointer"
              >
                New arrivals
              </button>
              <button
                type="button"
                onClick={() => {
                  onSelectCategory(null);
                  if (onNavigateHome) onNavigateHome();
                  const el = document.getElementById('products');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                className="nav-link bg-transparent border-0 cursor-pointer"
              >
                Shop all
              </button>
              <button
                type="button"
                onClick={() => {
                  const el = document.getElementById('story');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                className="nav-link bg-transparent border-0 cursor-pointer"
              >
                Our edit
              </button>
            </>
          ) : (
            <button
              type="button"
              onClick={() => onChangeSurface('storefront')}
              className="nav-link active bg-transparent border-0 cursor-pointer flex items-center gap-1.5 hover:text-[#FF5A36]"
            >
              ← Return to Boutique Storefront
            </button>
          )}
        </nav>
      </div>

      {/* Header Actions (Search Button, Wishlist, Bag, Auth) */}
      <div className="header-actions">
        {currentSurface === 'storefront' && (
          <>
            {/* Search Icon Button matching references/index.html */}
            <button
              type="button"
              className="search-button"
              aria-label="Search"
              onClick={() => {
                const el = document.getElementById('search-input');
                if (el) {
                  el.focus();
                  el.scrollIntoView({ behavior: 'smooth' });
                }
              }}
            />

            {/* Wishlist Indicator */}
            <button
              type="button"
              onClick={onOpenWishlist}
              className="text-xs text-[#6B6B6B] hover:text-[#1A1A1A] transition-colors cursor-pointer flex items-center gap-1"
              title="Wishlist"
              aria-label="Wishlist"
            >
              <span className="text-xs">★</span>
              {wishlistCount > 0 && (
                <span className="text-[10px] font-mono px-1 rounded-full bg-[#E8E6E1] text-[#1A1A1A]">
                  {wishlistCount}
                </span>
              )}
            </button>
          </>
        )}

        {/* User Account / Auth */}
        {isAuthenticated ? (
          <div className="flex items-center gap-3">
            <span className="text-xs text-[#1A1A1A] font-medium hidden md:inline">
              {user?.name}
            </span>

            {currentSurface === 'storefront' && onOpenOrders && (
              <button
                type="button"
                onClick={onOpenOrders}
                className="text-xs text-[#6B6B6B] hover:text-[#1A1A1A] transition-colors cursor-pointer hidden sm:inline"
                title="View your previous purchases"
              >
                My Orders
              </button>
            )}

            {/* Role-gated portal switcher button (only visible to users with corresponding roles) */}
            {currentSurface === 'storefront' && user?.role === 'seller' && (
              <button
                type="button"
                onClick={() => onChangeSurface('seller')}
                className="text-[10px] font-mono uppercase tracking-wider px-2 py-1 border border-[#FF5A36] text-[#FF5A36] hover:bg-[#FF5A36] hover:text-white transition-colors cursor-pointer hidden sm:inline-flex items-center gap-1"
                title="Enter your seller management workspace"
              >
                Seller Studio ↗
              </button>
            )}

            {currentSurface === 'storefront' && user?.role === 'admin' && (
              <button
                type="button"
                onClick={() => onChangeSurface('admin')}
                className="text-[10px] font-mono uppercase tracking-wider px-2 py-1 border border-[#D14343] text-[#D14343] hover:bg-[#D14343] hover:text-white transition-colors cursor-pointer hidden sm:inline-flex items-center gap-1"
                title="Enter platform oversight console"
              >
                Oversight ↗
              </button>
            )}

            <button
              type="button"
              onClick={() => {
                logout();
                onChangeSurface('storefront');
              }}
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

        {/* Bag Button only visible on Storefront */}
        {currentSurface === 'storefront' && (
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
        )}

        {/* Mobile Menu Button (2 lines matching references/index.html) */}
        <button
          type="button"
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          className="menu-button"
          aria-label="Toggle Navigation Menu"
        >
          <i />
          <i />
        </button>
      </div>

      {/* Mobile Drawer Navigation */}
      {isMobileMenuOpen && (
        <div className="absolute top-[64px] left-0 w-full bg-[#FAFAF8] border-b border-[#E8E6E1] p-6 space-y-4 md:hidden z-50">
          <div className="flex flex-col gap-3 text-sm font-serif">
            {currentSurface === 'storefront' ? (
              <>
                <button
                  type="button"
                  onClick={() => {
                    onSelectCategory(null);
                    const el = document.getElementById('products');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                    setIsMobileMenuOpen(false);
                  }}
                  className="text-left py-2 border-b border-[#E8E6E1]"
                >
                  New arrivals
                </button>
                <button
                  type="button"
                  onClick={() => {
                    onSelectCategory(null);
                    const el = document.getElementById('products');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                    setIsMobileMenuOpen(false);
                  }}
                  className="text-left py-2 border-b border-[#E8E6E1]"
                >
                  Shop all
                </button>
                <button
                  type="button"
                  onClick={() => {
                    const el = document.getElementById('story');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                    setIsMobileMenuOpen(false);
                  }}
                  className="text-left py-2 border-b border-[#E8E6E1]"
                >
                  Our edit
                </button>

                {isAuthenticated && user?.role === 'seller' && (
                  <button
                    type="button"
                    onClick={() => {
                      onChangeSurface('seller');
                      setIsMobileMenuOpen(false);
                    }}
                    className="text-left py-2 border-b border-[#E8E6E1] text-[#FF5A36] font-mono text-xs uppercase"
                  >
                    Enter Seller Studio ↗
                  </button>
                )}

                {isAuthenticated && user?.role === 'admin' && (
                  <button
                    type="button"
                    onClick={() => {
                      onChangeSurface('admin');
                      setIsMobileMenuOpen(false);
                    }}
                    className="text-left py-2 border-b border-[#E8E6E1] text-[#D14343] font-mono text-xs uppercase"
                  >
                    Enter Admin Oversight ↗
                  </button>
                )}
              </>
            ) : (
              <button
                type="button"
                onClick={() => {
                  onChangeSurface('storefront');
                  setIsMobileMenuOpen(false);
                }}
                className="text-left py-2 border-b border-[#E8E6E1]"
              >
                ← Return to Boutique Storefront
              </button>
            )}
          </div>
        </div>
      )}
    </header>
  );
};
