import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { AuthProvider, useAuth } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import { Navbar } from './components/Navbar';
import { ShopHeading } from './components/ShopHeading';
import { RackRailNav } from './components/RackRailNav';
import { ProductGrid } from './components/ProductGrid';
import { ProductDetailModal } from './components/ProductDetailModal';
import { CartDrawer } from './components/CartDrawer';
import { AuthModal } from './components/AuthModal';
import { WishlistModal } from './components/WishlistModal';
import { BuyerOrderHistoryModal } from './components/BuyerOrderHistoryModal';
import { SellerDashboard } from './components/SellerDashboard';
import { AdminPanel } from './components/AdminPanel';
import { OrderSuccess } from './components/OrderSuccess';
import { OrderCancel } from './components/OrderCancel';
import { Category, Product } from './types';

const MainApp: React.FC = () => {
  const { user, isAuthenticated, token } = useAuth();
  const [currentSurface, setCurrentSurface] = useState<'storefront' | 'seller' | 'admin'>('storefront');
  const [currentSort, setCurrentSort] = useState<string>('newest');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [activeProduct, setActiveProduct] = useState<Product | null>(null);
  const [authModalConfig, setAuthModalConfig] = useState<{
    isOpen: boolean;
    initialRole?: 'buyer' | 'seller';
    initialMode?: 'login' | 'register';
  }>({ isOpen: false });
  const [isWishlistOpen, setIsWishlistOpen] = useState<boolean>(false);
  const [isOrdersOpen, setIsOrdersOpen] = useState<boolean>(false);
  const [wishlistIds, setWishlistIds] = useState<number[]>([]);

  const [pathname, setPathname] = useState<string>(() => window.location.pathname);

  // Sync pathname on browser navigation (back/forward) and popstate events
  useEffect(() => {
    const handleLocationChange = () => {
      setPathname(window.location.pathname);
    };

    window.addEventListener('popstate', handleLocationChange);
    return () => {
      window.removeEventListener('popstate', handleLocationChange);
    };
  }, []);

  // Load Categories for Rack-Rail
  useEffect(() => {
    fetch('/api/v1/categories')
      .then((res) => res.json())
      .then((data) => {
        if (data && Array.isArray(data.data)) {
          setCategories(data.data);
        }
      })
      .catch(() => {});
  }, []);

  // Fetch wishlisted item IDs on auth change
  useEffect(() => {
    if (isAuthenticated && token) {
      fetch('/api/v1/wishlist', {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      })
        .then((res) => res.json())
        .then((data) => {
          if (data && Array.isArray(data.data)) {
            setWishlistIds(data.data.map((item: Product) => item.id));
          }
        })
        .catch(() => {});
    } else {
      setWishlistIds([]);
    }
  }, [isAuthenticated, token]);

  const openAuth = (mode: 'login' | 'register' = 'login', role: 'buyer' | 'seller' = 'buyer') => {
    setAuthModalConfig({ isOpen: true, initialMode: mode, initialRole: role });
  };

  const handleToggleWishlist = async (productId: number) => {
    if (!isAuthenticated || !token) {
      openAuth('login', 'buyer');
      return;
    }

    const isCurrentlyWishlisted = wishlistIds.includes(productId);
    if (isCurrentlyWishlisted) {
      setWishlistIds((prev) => prev.filter((id) => id !== productId));
    } else {
      setWishlistIds((prev) => [...prev, productId]);
    }

    try {
      const res = await fetch(`/api/v1/wishlist/${productId}/toggle`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });

      if (!res.ok) {
        if (isCurrentlyWishlisted) {
          setWishlistIds((prev) => [...prev, productId]);
        } else {
          setWishlistIds((prev) => prev.filter((id) => id !== productId));
        }
      }
    } catch {
      if (isCurrentlyWishlisted) {
        setWishlistIds((prev) => [...prev, productId]);
      } else {
        setWishlistIds((prev) => prev.filter((id) => id !== productId));
      }
    }
  };

  const navigateToCatalog = () => {
    window.history.pushState({}, '', '/');
    setPathname('/');
    setSelectedCategory(null);
    setCurrentSurface('storefront');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSurfaceChange = (surface: 'storefront' | 'seller' | 'admin') => {
    if (surface === 'storefront' && pathname !== '/') {
      window.history.pushState({}, '', '/');
      setPathname('/');
    }
    setCurrentSurface(surface);
  };

  const scrollToCatalog = () => {
    const el = document.getElementById('collection-section');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="min-h-screen bg-[#FAFAF8] text-[#1A1A1A] flex flex-col font-sans selection:bg-[#FF5A36] selection:text-white">
      {/* Site Header matching references/style.css */}
      <Navbar
        onOpenAuth={() => openAuth('login', 'buyer')}
        selectedCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        currentSurface={currentSurface}
        onChangeSurface={handleSurfaceChange}
        onNavigateHome={navigateToCatalog}
        wishlistCount={wishlistIds.length}
        onOpenWishlist={() => {
          if (!isAuthenticated) {
            openAuth('login', 'buyer');
          } else {
            setIsWishlistOpen(true);
          }
        }}
        onOpenOrders={() => {
          if (!isAuthenticated) {
            openAuth('login', 'buyer');
          } else {
            setIsOrdersOpen(true);
          }
        }}
      />

      {/* Main Viewport */}
      <main className="flex-1 overflow-hidden">
        {currentSurface === 'seller' ? (
          isAuthenticated && user?.role === 'seller' ? (
            <SellerDashboard onBackToStore={() => setCurrentSurface('storefront')} />
          ) : (
            <div className="max-w-xl mx-auto py-24 px-6 text-center">
              <span className="text-[10px] font-mono uppercase tracking-widest text-[#FF5A36] block mb-2">
                Restricted Workspace
              </span>
              <h2 className="text-3xl font-serif font-medium text-[#1A1A1A] mb-3">
                Atelier Authentication Required
              </h2>
              <p className="text-xs text-[#6B6B6B] mb-8 leading-relaxed">
                The Seller Studio is exclusively dedicated to registered and verified independent apparel ateliers. To manage listings, inventory, and Stripe payouts, please sign in with an Atelier account.
              </p>
              <div className="flex flex-col sm:flex-row justify-center gap-3">
                <button
                  type="button"
                  onClick={() => openAuth('login', 'seller')}
                  className="px-6 py-3 bg-[#FF5A36] text-white text-xs font-bold uppercase tracking-widest hover:bg-[#E64A28] transition-colors cursor-pointer"
                >
                  Sign in as Atelier
                </button>
                <button
                  type="button"
                  onClick={() => openAuth('register', 'seller')}
                  className="px-6 py-3 border border-[#1A1A1A] text-[#1A1A1A] text-xs font-bold uppercase tracking-widest hover:bg-[#1A1A1A] hover:text-white transition-colors cursor-pointer"
                >
                  Apply to Sell (16+)
                </button>
                <button
                  type="button"
                  onClick={() => setCurrentSurface('storefront')}
                  className="px-6 py-3 border border-[#E8E6E1] text-[#6B6B6B] text-xs font-bold uppercase tracking-widest hover:text-[#1A1A1A] transition-colors cursor-pointer"
                >
                  Return to Boutique
                </button>
              </div>
            </div>
          )
        ) : currentSurface === 'admin' ? (
          isAuthenticated && user?.role === 'admin' ? (
            <AdminPanel onBackToStore={() => setCurrentSurface('storefront')} />
          ) : (
            <div className="max-w-xl mx-auto py-24 px-6 text-center">
              <span className="text-[10px] font-mono uppercase tracking-widest text-[#D14343] block mb-2">
                Security Clearance
              </span>
              <h2 className="text-3xl font-serif font-medium text-[#1A1A1A] mb-3">
                Administrator Access Only
              </h2>
              <p className="text-xs text-[#6B6B6B] mb-8 leading-relaxed">
                This console is reserved exclusively for platform operations, seller dispute management, and platform metrics.
              </p>
              <button
                type="button"
                onClick={() => setCurrentSurface('storefront')}
                className="px-6 py-3 bg-[#1A1A1A] text-white text-xs font-bold uppercase tracking-widest hover:bg-[#333333] transition-colors cursor-pointer"
              >
                Return to Storefront
              </button>
            </div>
          )
        ) : pathname === '/order/success' ? (
          <OrderSuccess onReturnToStore={navigateToCatalog} />
        ) : pathname === '/order/cancel' ? (
          <OrderCancel onReturnToStore={navigateToCatalog} />
        ) : (
          <>
            {/* Shop Heading matching references/index.html */}
            <ShopHeading
              currentSort={currentSort}
              onSortChange={setCurrentSort}
              eyebrow={selectedCategory ? 'Collection Archive' : 'New arrivals'}
              title={selectedCategory ? selectedCategory.replace('-', ' ') : 'Shop the latest'}
            />

            {/* Horizontal Category Rack Rail */}
            <RackRailNav
              categories={categories}
              selectedCategory={selectedCategory}
              onSelectCategory={setSelectedCategory}
            />

            {/* 4-Column Product Grid & Story Section */}
            <ProductGrid
              onViewProduct={(product) => setActiveProduct(product)}
              selectedCategory={selectedCategory}
              onSelectCategory={setSelectedCategory}
              wishlistIds={wishlistIds}
              onToggleWishlist={handleToggleWishlist}
              currentSort={currentSort}
            />
          </>
        )}
      </main>

      {/* Slide-over Cart Drawer */}
      <CartDrawer />

      {/* Two-Zone Product Detail Modal (Bleed Gallery + Garment Tag Spec Module) */}
      <ProductDetailModal
        product={activeProduct}
        onClose={() => setActiveProduct(null)}
        isWishlisted={activeProduct ? wishlistIds.includes(activeProduct.id) : false}
        onToggleWishlist={handleToggleWishlist}
      />

      {/* Wishlist Modal */}
      <WishlistModal
        isOpen={isWishlistOpen}
        onClose={() => setIsWishlistOpen(false)}
        onViewProduct={(product) => {
          setActiveProduct(product);
          setIsWishlistOpen(false);
        }}
        wishlistIds={wishlistIds}
        onToggleWishlist={handleToggleWishlist}
      />

      {/* Buyer Order History Modal */}
      <BuyerOrderHistoryModal
        isOpen={isOrdersOpen}
        onClose={() => setIsOrdersOpen(false)}
        onReviewProduct={(productId) => {
          fetch(`/api/v1/products/${productId}`)
            .then((res) => res.json())
            .then((data) => {
              if (data?.data) {
                setActiveProduct(data.data);
              }
            })
            .catch(() => {});
        }}
      />

      {/* Auth Modal with Age Gate */}
      <AuthModal
        isOpen={authModalConfig.isOpen}
        onClose={() => setAuthModalConfig((prev) => ({ ...prev, isOpen: false }))}
        initialMode={authModalConfig.initialMode}
        initialRole={authModalConfig.initialRole}
      />

      {/* Site Footer matching references/index.html & references/style.css */}
      <footer className="site-footer">
        <a
          className="wordmark cursor-pointer"
          href="#top"
          onClick={(e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }}
        >
          FOLD<span>.</span>
        </a>
        <p>Good clothes, no noise.</p>

        <div className="footer-links">
          {isAuthenticated && user?.role === 'seller' ? (
            <button
              type="button"
              onClick={() => setCurrentSurface('seller')}
              className="hover:text-[#FF5A36] transition-colors cursor-pointer bg-transparent border-0"
            >
              Seller Studio
            </button>
          ) : (
            <button
              type="button"
              onClick={() => {
                if (isAuthenticated) {
                  alert('You are currently signed in as a Collector. To manage an atelier, please sign in with an Atelier account.');
                } else {
                  openAuth('register', 'seller');
                }
              }}
              className="hover:text-[#FF5A36] transition-colors cursor-pointer bg-transparent border-0"
            >
              Atelier Onboarding
            </button>
          )}

          {isAuthenticated && user?.role === 'admin' && (
            <button
              type="button"
              onClick={() => setCurrentSurface('admin')}
              className="hover:text-[#D14343] transition-colors cursor-pointer bg-transparent border-0 text-[#D14343]"
            >
              Platform Oversight
            </button>
          )}

          <a href="#" className="hover:text-[#FF5A36] transition-colors">
            Instagram
          </a>
          <a href="#" className="hover:text-[#FF5A36] transition-colors">
            Contact
          </a>
          <a href="#" className="hover:text-[#FF5A36] transition-colors">
            Shipping
          </a>
        </div>
      </footer>
    </div>
  );
};

export const App: React.FC = () => {
  return (
    <AuthProvider>
      <CartProvider>
        <MainApp />
      </CartProvider>
    </AuthProvider>
  );
};

const container = document.getElementById('app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}
