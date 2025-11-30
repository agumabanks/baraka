import React, { createContext, useContext, useState, useEffect } from 'react';
import type { ReactNode } from 'react';
import { authApi, redirectToLogin } from '../services/api';

interface UserRole {
  id: number | string;
  name: string;
  key?: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  role?: UserRole | null;
  roles?: UserRole[];
  permissions?: string[];
  project_ids?: Array<number | string>;
  preferred_language?: 'en' | 'fr' | 'sw';
  primary_branch_id?: number | null;
  primary_branch?: {
    id: number;
    name: string;
    code?: string | null;
    type?: string | null;
  } | null;
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  updateUser: (payload: Partial<User>) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const isAuthenticated = !!user;

  const login = async (email: string, password: string) => {
    try {
      const response = await authApi.login(email, password);

      if (response.success) {
        const { user: userData, token } = response.data;
        const resolvedLocale = (userData?.preferred_language as User['preferred_language']) ?? 'en';

        // Store token and user data
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('dashboard_locale', resolvedLocale);

        setUser(userData);
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  };

  const register = async (name: string, email: string, password: string, password_confirmation: string) => {
    try {
      const response = await authApi.register(name, email, password, password_confirmation);

      if (response.success) {
        const { user: userData, token } = response.data;
        const resolvedLocale = (userData?.preferred_language as User['preferred_language']) ?? 'en';

        // Store token and user data
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('dashboard_locale', resolvedLocale);

        setUser(userData);
      } else {
        throw new Error(response.message || 'Registration failed');
      }
    } catch (error) {
      console.error('Registration error:', error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authApi.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Always clear local storage and state
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      localStorage.removeItem('dashboard_locale');
      setUser(null);
      redirectToLogin();
    }
  };

  const checkAuth = async () => {
    const token = localStorage.getItem('auth_token');
    const storedUser = localStorage.getItem('user');
    const shouldRedirect = () => {
      if (typeof window === 'undefined') {
        return false;
      }
      const path = window.location.pathname;
      return path.startsWith('/admin/dashboard') || path.startsWith('/dashboard') || path.startsWith('/react-dashboard');
    };

    try {
      if (token) {
        // Verify token is still valid by fetching user data
        const response = await authApi.getUser();

        if (response?.success && response?.data?.user) {
          // Prefer server user payload; keep localStorage in sync
          const serverUser = response.data.user as User;
          setUser(serverUser);
          try {
            localStorage.setItem('user', JSON.stringify(serverUser));
            if (serverUser.preferred_language) {
              localStorage.setItem('dashboard_locale', serverUser.preferred_language);
            }
          } catch (storageError) {
            console.warn('Unable to persist user cache snapshot', storageError);
          }
        } else if (storedUser) {
          // Fallback to stored user if API shape differs
          const cachedUser = JSON.parse(storedUser) as User;
          setUser(cachedUser);
          try {
            if (cachedUser.preferred_language) {
              localStorage.setItem('dashboard_locale', cachedUser.preferred_language);
            }
          } catch (storageError) {
            console.warn('Unable to persist cached user locale', storageError);
          }
        } else {
          // Invalid token or missing user data
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
          setUser(null);
          if (shouldRedirect()) {
            redirectToLogin();
          }
        }
      } else if (shouldRedirect()) {
        redirectToLogin();
      }
    } catch (error) {
      console.error('Auth verification failed:', error);
      // Token is invalid, clear storage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      setUser(null);
      if (shouldRedirect()) {
        redirectToLogin();
      }
    }

    setIsLoading(false);
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const updateUser = (payload: Partial<User>) => {
    setUser((previous) => {
      if (!previous) {
        return previous;
      }

      const updated = { ...previous, ...payload };

      try {
        localStorage.setItem('user', JSON.stringify(updated));
        if (updated.preferred_language) {
          localStorage.setItem('dashboard_locale', updated.preferred_language);
        }
      } catch (error) {
        console.warn('Unable to cache updated user payload', error);
      }

      return updated;
    });
  };

  const value: AuthContextType = {
    user,
    isAuthenticated,
    isLoading,
    login,
    register,
    logout,
    checkAuth,
    updateUser,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
/* eslint-disable react-refresh/only-export-components */
