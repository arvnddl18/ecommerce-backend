export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  parent_id: number | null;
  products_count?: number;
}

export interface SellerProfile {
  id: number;
  store_name: string;
  slug: string;
  verification_status: 'pending' | 'approved' | 'rejected';
  stripe_account_id?: string | null;
  bio?: string | null;
  logo_url?: string | null;
}

export interface ProductVariant {
  id: number;
  size: string;
  color: string;
  sku: string;
  stock_quantity: number;
  price_override?: number | null;
  effective_price: number; // in cents
  formatted_effective_price: string;
}

export interface ProductImage {
  id: number;
  url: string;
  sort_order: number;
}

export interface Review {
  id: number;
  user_name: string;
  rating: number;
  comment: string;
  created_at?: string;
}

export interface Product {
  id: number;
  category_id: number;
  category?: Category;
  seller?: SellerProfile | null;
  name: string;
  slug: string;
  description: string;
  price: number; // In cents
  formatted_price: string;
  stock: number;
  in_stock: boolean;
  sku: string;
  images: string[];
  variants?: ProductVariant[];
  gallery_images?: ProductImage[];
  average_rating?: number;
  reviews_count?: number;
  reviews?: Review[];
  is_active: boolean;
  status?: string;
}

export interface CartItem {
  product_id: number;
  variant_id?: number;
  size?: string;
  color?: string;
  name: string;
  slug: string;
  sku: string;
  price: number; // in cents
  formatted_price: string;
  quantity: number;
  stock: number;
  total: number;
  formatted_total: string;
  image: string | null;
}

export interface CartData {
  items: CartItem[];
  total_quantity: number;
  subtotal: number; // in cents
  formatted_subtotal: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'buyer' | 'seller' | 'admin';
  age_verified: boolean;
  seller_profile?: SellerProfile | null;
  created_at?: string;
}

export interface Address {
  id: number;
  line1: string;
  line2?: string | null;
  city: string;
  province: string;
  postal_code: string;
  country: string;
  is_default: boolean;
}

export interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  unit_price: number;
  formatted_unit_price: string;
  quantity: number;
  total_price: number;
  formatted_total_price: string;
  fulfillment_status?: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  seller_id?: number;
  variant?: ProductVariant;
}

export interface Order {
  id: number;
  order_number: string;
  status: 'pending' | 'paid' | 'processing' | 'completed' | 'cancelled' | 'refunded';
  total_amount: number;
  formatted_total: string;
  currency: string;
  customer_email: string;
  customer_name: string | null;
  stripe_session_id: string | null;
  items?: OrderItem[];
  created_at: string;
}
