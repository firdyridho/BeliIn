export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
  errors?: Record<string, string[]>;
}

export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  status: 'active' | 'suspended';
  created_at: string;
}

export interface UserAddress {
  id: number;
  user_id: number;
  receiver_name: string;
  phone_number: string;
  full_address: string;
  postal_code: string;
  city: string;
  is_default: boolean;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  products_count?: number;
}

export interface Store {
  id: number;
  seller_id: number;
  name: string;
  slug: string;
  city: string;
  description?: string;
  status: string;
}

export interface ProductVariant {
  id: number;
  product_id: number;
  sku: string;
  name: string;
  price: number;
  weight_grams: number;
  stock: number;
}

export interface ProductImage {
  id: number;
  product_id: number;
  image_url: string;
  is_primary: boolean;
  sort_order: number;
}

export interface Product {
  id: number;
  store_id: number;
  category_id: number;
  name: string;
  slug: string;
  description?: string;
  base_price: number;
  min_price?: number;
  max_price?: number;
  total_stock?: number;
  is_active: boolean;
  store?: Store;
  category?: Category;
  variants?: ProductVariant[];
  images?: ProductImage[];
}

export interface CartItem {
  id: number;
  cart_id: number;
  product_id: number;
  product_variant_id: number;
  quantity: number;
  product?: Product;
  variant?: ProductVariant;
}

export interface Cart {
  id: number;
  user_id: number;
  items: CartItem[];
}

export interface ShippingServiceOption {
  service_code: string;
  service_name: string;
  provider_name: string;
  etd: string;
  rate: number;
}

export interface CheckoutStorePayload {
  store_id: number;
  service_code: string;
  buyer_notes?: string;
}

export interface CheckoutRequest {
  shipping_address_id: number;
  stores: CheckoutStorePayload[];
}

export type OrderStatus = 'pending' | 'paid' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'refunded';
export type SellerOrderStatus = 'pending' | 'accepted' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
export type PaymentStatus = 'pending' | 'settled' | 'expired' | 'failed' | 'refunded';
export type ShipmentStatus = 'pending' | 'picked_up' | 'in_transit' | 'out_for_delivery' | 'delivered' | 'failed';

export interface OrderItem {
  id: number;
  seller_order_id: number;
  product_id: number;
  product_variant_id: number;
  product_name: string;
  variant_name: string;
  price: number;
  quantity: number;
  subtotal: number;
}

export interface ShipmentTrackingEvent {
  id: number;
  shipment_id: number;
  status: ShipmentStatus;
  location: string;
  description: string;
  event_time: string;
}

export interface Shipment {
  id: number;
  seller_order_id: number;
  shipping_provider_id: number;
  shipping_service_id: number;
  tracking_number: string;
  status: ShipmentStatus;
  shipping_cost: number;
  recipient_name: string;
  recipient_phone: string;
  recipient_address: string;
  shipped_at?: string;
  delivered_at?: string;
  provider?: {
    id: number;
    code: string;
    name: string;
  };
  service?: {
    id: number;
    service_code: string;
    service_name: string;
  };
  tracking_events?: ShipmentTrackingEvent[];
}

export interface SellerOrder {
  id: number;
  order_id: number;
  store_id: number;
  seller_order_number: string;
  subtotal_amount: number;
  shipping_cost: number;
  total_amount: number;
  status: SellerOrderStatus;
  buyer_notes?: string;
  store?: Store;
  items?: OrderItem[];
  shipment?: Shipment;
}

export interface Payment {
  id: number;
  order_id: number;
  payment_reference: string;
  payment_method: string;
  channel: string;
  amount: number;
  status: PaymentStatus;
  checkout_url?: string;
  qr_code?: string;
  va_number?: string;
  expires_at: string;
  paid_at?: string;
}

export interface Order {
  id: number;
  user_id: number;
  order_number: string;
  subtotal_amount: number;
  shipping_amount: number;
  service_fee: number;
  discount_amount: number;
  total_amount: number;
  status: OrderStatus;
  user?: User;
  shipping_address?: UserAddress;
  seller_orders?: SellerOrder[];
  payment?: Payment;
  created_at: string;
}
