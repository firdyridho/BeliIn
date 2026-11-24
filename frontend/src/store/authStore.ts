import { create } from 'zustand';
import { User, UserAddress } from '../types';

interface AuthState {
  token: string | null;
  user: User | null;
  selectedAddress: UserAddress | null;
  isAuthenticated: boolean;
  login: (token: string, user: User) => void;
  setUser: (user: User) => void;
  setSelectedAddress: (address: UserAddress | null) => void;
  logout: () => void;
}

const initialToken = localStorage.getItem('beliin_token');
const initialUserJson = localStorage.getItem('beliin_user');
let initialUser: User | null = null;
if (initialUserJson) {
  try {
    initialUser = JSON.parse(initialUserJson);
  } catch (e) {
    initialUser = null;
  }
}

export const useAuthStore = create<AuthState>((set) => ({
  token: initialToken,
  user: initialUser,
  selectedAddress: null,
  isAuthenticated: !!initialToken,

  login: (token, user) => {
    localStorage.setItem('beliin_token', token);
    localStorage.setItem('beliin_user', JSON.stringify(user));
    set({ token, user, isAuthenticated: true });
  },

  setUser: (user) => {
    localStorage.setItem('beliin_user', JSON.stringify(user));
    set({ user });
  },

  setSelectedAddress: (address) => {
    set({ selectedAddress: address });
  },

  logout: () => {
    localStorage.removeItem('beliin_token');
    localStorage.removeItem('beliin_user');
    set({ token: null, user: null, selectedAddress: null, isAuthenticated: false });
  },
}));

// Listen for global 401 unauthorized event
if (typeof window !== 'undefined') {
  window.addEventListener('auth:unauthorized', () => {
    useAuthStore.getState().logout();
  });
}
