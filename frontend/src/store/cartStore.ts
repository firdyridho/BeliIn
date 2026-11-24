import { create } from 'zustand';
import { Cart } from '../types';
import { cartApi } from '../api/cart';

interface CartState {
  cart: Cart | null;
  itemCount: number;
  isLoading: boolean;
  setCart: (cart: Cart | null) => void;
  fetchCart: () => Promise<void>;
}

export const useCartStore = create<CartState>((set) => ({
  cart: null,
  itemCount: 0,
  isLoading: false,

  setCart: (cart) => {
    const totalItems = cart?.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
    set({ cart, itemCount: totalItems });
  },

  fetchCart: async () => {
    const token = localStorage.getItem('beliin_token');
    if (!token) {
      set({ cart: null, itemCount: 0 });
      return;
    }
    set({ isLoading: true });
    try {
      const res = await cartApi.getCart();
      const cart = res.data;
      const totalItems = cart?.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
      set({ cart, itemCount: totalItems, isLoading: false });
    } catch (err) {
      set({ cart: null, itemCount: 0, isLoading: false });
    }
  },
}));
