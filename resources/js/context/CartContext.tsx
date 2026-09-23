import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { CartData } from '../types';
import { useAuth } from './AuthContext';

interface CartContextType {
  cart: CartData;
  isLoading: boolean;
  isCartOpen: boolean;
  setIsCartOpen: (open: boolean) => void;
  addToCart: (productId: number, quantity?: number, variantId?: number | null) => Promise<void>;
  updateQuantity: (itemKeyOrProductId: string | number, quantity: number) => Promise<void>;
  removeFromCart: (itemKeyOrProductId: string | number) => Promise<void>;
  clearCart: () => Promise<void>;
  refreshCart: () => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => Promise<void>;
}

const initialCart: CartData = {
  items: [],
  total_quantity: 0,
  subtotal: 0,
  formatted_subtotal: '₱0.00',
};

const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { token } = useAuth();
  const [cart, setCart] = useState<CartData>(initialCart);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isCartOpen, setIsCartOpen] = useState<boolean>(false);
  const [cartToken, setCartToken] = useState<string>(() => {
    let stored = localStorage.getItem('cart_token');
    if (!stored) {
      stored = 'guest_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
      localStorage.setItem('cart_token', stored);
    }
    return stored;
  });

  const getHeaders = useCallback(() => {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Cart-Token': cartToken,
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
  }, [cartToken, token]);

  const refreshCart = useCallback(async () => {
    try {
      setIsLoading(true);
      const res = await fetch('/api/v1/cart', {
        headers: getHeaders(),
      });
      if (res.ok) {
        const json = await res.json();
        if (json.cart_token && json.cart_token !== cartToken) {
          localStorage.setItem('cart_token', json.cart_token);
          setCartToken(json.cart_token);
        }
        setCart(json.data || initialCart);
      }
    } catch {
      // Ignore network fail
    } finally {
      setIsLoading(false);
    }
  }, [getHeaders, cartToken]);

  useEffect(() => {
    refreshCart();
  }, [refreshCart]);

  const addToCart = async (productId: number, quantity: number = 1, variantId: number | null = null) => {
    setIsLoading(true);
    try {
      const payload: Record<string, any> = { product_id: productId, quantity };
      if (variantId !== null && variantId !== undefined) {
        payload.variant_id = variantId;
      }

      const res = await fetch('/api/v1/cart/items', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(payload),
      });

      const json = await res.json();
      if (!res.ok) {
        throw new Error(json.message || 'Failed to add item to cart');
      }

      setCart(json.data);
      setIsCartOpen(true);
    } finally {
      setIsLoading(false);
    }
  };

  const updateQuantity = async (itemKeyOrProductId: string | number, quantity: number) => {
    try {
      const res = await fetch(`/api/v1/cart/items/${itemKeyOrProductId}`, {
        method: 'PUT',
        headers: getHeaders(),
        body: JSON.stringify({ quantity }),
      });

      const json = await res.json();
      if (!res.ok) {
        throw new Error(json.message || 'Failed to update quantity');
      }

      setCart(json.data);
    } catch (err: any) {
      alert(err.message || 'Could not update item quantity');
    }
  };

  const removeFromCart = async (itemKeyOrProductId: string | number) => {
    try {
      const res = await fetch(`/api/v1/cart/items/${itemKeyOrProductId}`, {
        method: 'DELETE',
        headers: getHeaders(),
      });

      const json = await res.json();
      if (res.ok) {
        setCart(json.data);
      }
    } catch {
      // Ignore error
    }
  };

  const clearCart = async () => {
    try {
      const res = await fetch('/api/v1/cart', {
        method: 'DELETE',
        headers: getHeaders(),
      });

      const json = await res.json();
      if (res.ok) {
        setCart(json.data || initialCart);
      }
    } catch {
      // Ignore error
    }
  };

  const applyCoupon = async (code: string) => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/v1/cart/coupon', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ code }),
      });

      const json = await res.json();
      if (!res.ok) {
        throw new Error(json.message || 'Failed to apply coupon');
      }

      setCart(json.data);
    } finally {
      setIsLoading(false);
    }
  };

  const removeCoupon = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/v1/cart/coupon', {
        method: 'DELETE',
        headers: getHeaders(),
      });

      const json = await res.json();
      if (res.ok) {
        setCart(json.data);
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <CartContext.Provider
      value={{
        cart,
        isLoading,
        isCartOpen,
        setIsCartOpen,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        refreshCart,
        applyCoupon,
        removeCoupon,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};
