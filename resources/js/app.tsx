import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { AuthProvider, useAuth } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import { Navbar } from './components/Navbar';
import { EditorialIntro } from './components/EditorialIntro';
import { RackRailNav } from './components/RackRailNav';
import { ProductGrid } from './components/ProductGrid';
import { ProductDetailModal } from './components/ProductDetailModal';
import { CartDrawer } from './components/CartDrawer';
import { AuthModal } from './components/AuthModal';
import { WishlistModal } from './components/WishlistModal';
import { SellerDashboard } from './components/SellerDashboard';
import { AdminPanel } from './components/AdminPanel';
import { OrderSuccess } from './components/OrderSuccess';
import { OrderCancel } from './components/OrderCancel';
import { Category, Product } from './types';

const MainApp: React.FC = () => {
  const { isAuthenticated, token } = useAuth();
  const [currentSurface, setCurrentSurface] = useState<'storefront' | 'seller' | 'admin'>('storefront');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [activeProduct, setActiveProduct] = useState<Product | null>(null);
  const [isAuthOpen, setIsAuthOpen] = useState<boolean>(false);
  const [isWishlistOpen, setIsWishlistOpen] = useState<boolean>(false);
  const [wishlistIds, setWishlistIds] = useState<number[]>([]);

  const pathname = window.location.pathname;

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

  const handleToggleWishlist = async (productId: number) => {
    if (!isAuthenticated || !token) {
      setIsAuthOpen(true);
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
    window.dispatchEvent(new PopStateEvent('popstate'));
    setSelectedCategory(null);
    setCurrentSurface('storefront');
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
        onOpenAuth={() => setIsAuthOpen(true)}
        selectedCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        currentSurface={currentSurface}
        onChangeSurface={setCurrentSurface}
        wishlistCount={wishlistIds.length}
        onOpenWishlist={() => {
          if (!isAuthenticated) {
            setIsAuthOpen(true);
          } else {
            setIsWishlistOpen(true);
          }
        }}
      />

      {/* Main Viewport */}
      <main className="flex-1 overflow-hidden">
        {currentSurface === 'seller' ? (
          <SellerDashboard onBackToStore={() => setCurrentSurface('storefront')} />
        ) : currentSurface === 'admin' ? (
          <AdminPanel onBackToStore={() => setCurrentSurface('storefront')} />
        ) : pathname === '/order/success' ? (
          <OrderSuccess onReturnToStore={navigateToCatalog} />
        ) : pathname === '/order/cancel' ? (
          <OrderCancel onReturnToStore={navigateToCatalog} />
        ) : (
          <>
            {/* The Rail & The Rack Storefront Layout */}
            <EditorialIntro onExploreClick={scrollToCatalog} />

            {/* Horizontal Scrollable Rack Rail Category Navigation */}
            <RackRailNav
              categories={categories}
              selectedCategory={selectedCategory}
              onSelectCategory={setSelectedCategory}
            />

            {/* Asymmetric Editorial Product Grid & Story Section */}
            <ProductGrid
              onViewProduct={(product) => setActiveProduct(product)}
              selectedCategory={selectedCategory}
              onSelectCategory={setSelectedCategory}
              wishlistIds={wishlistIds}
              onToggleWishlist={handleToggleWishlist}
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

      {/* Auth Modal with Age Gate */}
      <AuthModal
        isOpen={isAuthOpen}
        onClose={() => setIsAuthOpen(false)}
      />

      {/* Site Footer matching references/style.css */}
      <footer className="site-footer">
        <p className="font-serif">
          MAISON COLLECTIVE © {new Date().getFullYear()} — Multi-Vendor Apparel Archive
        </p>

        <div className="footer-links">
          <button
            type="button"
            onClick={() => setCurrentSurface('seller')}
            className="hover:text-[#FF5A36] transition-colors cursor-pointer bg-transparent border-0"
          >
            Atelier Onboarding
          </button>
          <button
            type="button"
            onClick={() => setCurrentSurface('admin')}
            className="hover:text-[#FF5A36] transition-colors cursor-pointer bg-transparent border-0"
          >
            Platform Oversight
          </button>
          <a
            href="https://github.com/arvnddl18/ecommerce-backend"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:text-[#FF5A36] transition-colors"
          >
            Repository
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
