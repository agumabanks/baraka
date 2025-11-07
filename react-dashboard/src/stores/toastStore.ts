import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

export type ToastIntent = 'success' | 'error' | 'info' | 'warning';

export interface ToastItem {
  id: string;
  intent: ToastIntent;
  title: string;
  description?: string;
  duration: number;
  createdAt: number;
}

export type ToastConfig = Omit<ToastItem, 'id' | 'createdAt' | 'duration'> & { id?: string; duration?: number };

interface ToastStore {
  toasts: ToastItem[];
  addToast: (toast: ToastConfig) => string;
  removeToast: (id: string) => void;
  clear: () => void;
}

const DEFAULT_DURATION = 5000;

const createId = (): string => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  return `toast-${Math.random().toString(36).slice(2, 10)}`;
};

const scheduleDismiss = (id: string, duration: number) => {
  if (typeof window === 'undefined' || duration <= 0) {
    return;
  }

  window.setTimeout(() => {
    useToastStore.getState().removeToast(id);
  }, duration);
};

export const useToastStore = create<ToastStore>()(
  devtools(
    (set) => ({
      toasts: [],
      addToast: (toast: ToastConfig) => {
        const id = toast.id ?? createId();
        const item: ToastItem = {
          ...toast,
          id,
          duration: toast.duration ?? DEFAULT_DURATION,
          createdAt: Date.now(),
        };

        set((state) => ({ toasts: [...state.toasts, item] }));
        scheduleDismiss(id, item.duration);
        return id;
      },
      removeToast: (id) => set((state) => ({ toasts: state.toasts.filter((toast) => toast.id !== id) })),
      clear: () => set({ toasts: [] }),
    }),
    { name: 'toast-store' },
  ),
);

const createToast = (intent: ToastIntent) => (config: Omit<ToastConfig, 'intent'>) =>
  useToastStore.getState().addToast({ ...config, intent });

export const toast = {
  success: createToast('success'),
  error: createToast('error'),
  info: createToast('info'),
  warning: createToast('warning'),
};
