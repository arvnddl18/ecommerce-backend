import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { CartData } from '../types';
import { useAuth } from './AuthContext';

interface CartContextType {
  cart: CartData;
  isLoading: boolean;
  isCartOpen: boolean;
  setIsCartOpen: (open: boolean) => void;
  addToCart: (productId: number, quantity?: number) => Promise<void>;
  updateQuantity: (productId: number, quantity: number) => Promise<void>;
  removeFromCart: (productId: number) => Promise<void>;
  clearCart: () => Promise<void>;
  refreshCart: () => Promise<void>;
}

const initialCart: CartData = {
  items: [],
  total_quantity: 0,
  subtotal: 0,
  formatted_subtotal: '$0.00',
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

  const addToCart = async (productId: number, quantity: number = 1) => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/v1/cart/items', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ product_id: productId, quantity }),
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

  const updateQuantity = async (productId: number, quantity: number) => {
    try {
      const res = await fetch(`/api/v1/cart/items/${productId}`, {
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

  const removeFromCart = async (productId: number) => {
    try {
      const res = await fetch(`/api/v1/cart/items/${productId}`, {
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
