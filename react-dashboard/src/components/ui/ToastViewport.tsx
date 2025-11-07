import React from 'react';
import { useToastStore } from '../../stores/toastStore';

const intentStyles: Record<string, string> = {
  success: 'border-green-200 bg-green-50 text-green-800',
  error: 'border-red-200 bg-red-50 text-red-800',
  info: 'border-blue-200 bg-blue-50 text-blue-800',
  warning: 'border-amber-200 bg-amber-50 text-amber-800',
};

const intentIcon: Record<string, string> = {
  success: 'fas fa-check-circle',
  error: 'fas fa-times-circle',
  info: 'fas fa-info-circle',
  warning: 'fas fa-exclamation-circle',
};

const ToastViewport: React.FC = () => {
  const toasts = useToastStore((state) => state.toasts);
  const removeToast = useToastStore((state) => state.removeToast);

  if (toasts.length === 0) {
    return null;
  }

  return (
    <div className="pointer-events-none fixed bottom-6 right-6 z-[3000] flex max-w-sm flex-col gap-3" aria-live="assertive" aria-atomic="true">
      {toasts.map((toast) => {
        const intent = intentStyles[toast.intent] ?? intentStyles.info;
        const icon = intentIcon[toast.intent] ?? intentIcon.info;

        return (
          <div
            key={toast.id}
            className={`pointer-events-auto rounded-2xl border px-4 py-3 shadow-xl transition-all duration-200 ${intent}`}
            role="status"
          >
            <div className="flex items-start gap-3">
              <span className={`mt-1 text-sm ${icon}`} aria-hidden="true" />
              <div className="flex-1 space-y-1">
                <p className="text-sm font-semibold">{toast.title}</p>
                {toast.description && <p className="text-xs leading-relaxed">{toast.description}</p>}
              </div>
              <button
                type="button"
                className="text-xs uppercase tracking-[0.2em] text-current opacity-70 transition-opacity hover:opacity-100"
                onClick={() => removeToast(toast.id)}
              >
                Close
              </button>
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default ToastViewport;
