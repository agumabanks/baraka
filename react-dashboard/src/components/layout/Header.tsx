import React from 'react';
import { Bell, Globe, Moon } from 'lucide-react';
import { Badge } from '../ui';
import MobileMenuToggle from './MobileMenuToggle';
import LanguageSelector from './LanguageSelector';
import UserMenu from './UserMenu';
import Breadcrumb from './Breadcrumb';
import type { HeaderProps } from '../../types/header';

/**
 * Header Component
 * Main navigation header with Steve Jobs monochrome design
 * Features: logo, mobile toggle, language selector, notifications, user menu, breadcrumbs
 */
const Header: React.FC<HeaderProps> = ({
  user,
  currentLanguage,
  languages,
  notifications,
  breadcrumbs,
  onLanguageChange,
  onNotificationClick,
  onToggleSidebar,
  onLogout,
  logoUrl,
  appName = 'Dashboard'
}) => {
  const unreadNotifications = notifications.filter(n => !n.read).length;

  return (
    <header className="bg-mono-white border-b border-mono-gray-200 shadow-sm sticky top-0 z-30">
      {/* Main navbar */}
      <nav className="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top admin-navbar">
        <div className="container-fluid">
          {/* Mobile menu button */}
          <MobileMenuToggle
            onToggle={onToggleSidebar}
            isOpen={false} // This will be managed by parent
          />

          {/* Logo */}
          <a
            className="navbar-brand d-flex align-items-center"
            href="/dashboard"
            aria-label="Go to dashboard"
          >
            {logoUrl && (
              <img
                src={logoUrl}
                className="logo me-2"
                alt={`${appName} logo`}
                style={{ height: '32px', width: 'auto' }}
              />
            )}
            <span className="fw-semibold text-mono-black">{appName}</span>
          </a>

          {/* Desktop Navigation Items */}
          <div className="d-none d-lg-flex ms-auto align-items-center">
            {/* Language Switcher */}
            <div className="me-3">
              <LanguageSelector
                currentLanguage={currentLanguage}
                languages={languages}
                onLanguageChange={onLanguageChange}
              />
            </div>

            {/* Dark Mode Toggle */}
            <button
              type="button"
              className="btn btn-link nav-link me-3 p-0"
              aria-label="Toggle dark mode"
              title="Toggle dark mode"
            >
              <Moon size={18} className="text-mono-gray-600" />
            </button>

            {/* Quick Actions Placeholder */}
            <div className="me-3">
              <div className="bg-mono-gray-50 border border-mono-gray-200 rounded-lg px-3 py-2">
                <span className="text-sm text-mono-gray-600">Quick Actions</span>
              </div>
            </div>

            {/* Frontend Link */}
            <a
              href="/"
              className="nav-link me-3"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Go to frontend"
            >
              <Globe size={18} className="text-mono-gray-600" />
            </a>

            {/* Notifications */}
            <div className="dropdown me-3">
              <button
                type="button"
                className="btn btn-link nav-link position-relative dropdown-toggle p-0"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label={`Notifications ${unreadNotifications > 0 ? `(${unreadNotifications} unread)` : ''}`}
              >
                <Bell size={18} className="text-mono-gray-600" />
                {unreadNotifications > 0 && (
                  <Badge
                    variant="solid"
                    className="position-absolute top-0 start-100 translate-middle bg-red-600 text-white"
                  >
                    {unreadNotifications > 9 ? '9+' : unreadNotifications}
                  </Badge>
                )}
              </button>
              <ul className="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg">
                <li>
                  <div className="notification-title px-4 py-3 border-b border-mono-gray-200">
                    <h6 className="mb-0 text-mono-black font-semibold">Notifications</h6>
                  </div>
                </li>
                <li>
                  <div className="notification-list max-h-64 overflow-y-auto">
                    {notifications.length > 0 ? (
                      notifications.slice(0, 5).map((notification) => (
                        <button
                          key={notification.id}
                          type="button"
                          className={`w-full text-left px-4 py-3 hover:bg-mono-gray-50 transition-colors border-b border-mono-gray-100 ${
                            !notification.read ? 'bg-mono-gray-25' : ''
                          }`}
                          onClick={() => onNotificationClick(notification)}
                        >
                          <div className="text-sm text-mono-gray-900 font-medium">
                            {notification.title}
                          </div>
                          <div className="text-xs text-mono-gray-600 mt-1">
                            {notification.message}
                          </div>
                        </button>
                      ))
                    ) : (
                      <div className="px-4 py-3 text-center text-mono-gray-500">
                        No notifications
                      </div>
                    )}
                  </div>
                </li>
              </ul>
            </div>

            {/* Todo Button Placeholder */}
            <button
              type="button"
              className="btn btn-primary btn-sm me-3"
              aria-label="Create todo"
            >
              <span className="text-sm">+ Todo</span>
            </button>

            {/* User Menu */}
            <UserMenu user={user} onLogout={onLogout} />
          </div>

          {/* Mobile Navigation Items */}
          <div className="d-lg-none d-flex align-items-center">
            {/* Mobile Quick Actions */}
            <div className="me-2">
              <div className="bg-mono-gray-50 border border-mono-gray-200 rounded px-2 py-1">
                <span className="text-xs text-mono-gray-600">Actions</span>
              </div>
            </div>

            <a
              href="/"
              className="nav-link me-2"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Go to frontend"
            >
              <Globe size={16} className="text-mono-gray-600" />
            </a>

            <div className="dropdown me-2">
              <button
                type="button"
                className="btn btn-link nav-link dropdown-toggle p-0"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label={`Notifications ${unreadNotifications > 0 ? `(${unreadNotifications} unread)` : ''}`}
              >
                <Bell size={16} className="text-mono-gray-600" />
                {unreadNotifications > 0 && (
                  <Badge variant="solid" className="ms-1 bg-red-600 text-white">
                    {unreadNotifications > 9 ? '9+' : unreadNotifications}
                  </Badge>
                )}
              </button>
              <ul className="dropdown-menu dropdown-menu-end shadow-lg">
                <li>
                  <div className="notification-title px-3 py-2 border-b border-mono-gray-200">
                    <span className="text-sm font-semibold text-mono-black">Notifications</span>
                  </div>
                </li>
                <li>
                  <div className="notification-list max-h-48 overflow-y-auto">
                    {notifications.length > 0 ? (
                      notifications.slice(0, 3).map((notification) => (
                        <button
                          key={notification.id}
                          type="button"
                          className="w-full text-left px-3 py-2 hover:bg-mono-gray-50 transition-colors text-sm"
                          onClick={() => onNotificationClick(notification)}
                        >
                          <div className="text-mono-gray-900 font-medium">
                            {notification.title}
                          </div>
                          <div className="text-mono-gray-600 text-xs mt-1">
                            {notification.message}
                          </div>
                        </button>
                      ))
                    ) : (
                      <div className="px-3 py-2 text-center text-mono-gray-500 text-sm">
                        No notifications
                      </div>
                    )}
                  </div>
                </li>
              </ul>
            </div>

            <button
              type="button"
              className="btn btn-primary btn-sm me-2"
              aria-label="Create todo"
            >
              <span className="text-xs">+ Todo</span>
            </button>

            <UserMenu user={user} onLogout={onLogout} />
          </div>
        </div>
      </nav>

      {/* Breadcrumb Section */}
      {breadcrumbs && breadcrumbs.length > 0 && (
        <div className="bg-mono-gray-50 border-b border-mono-gray-200 px-6 py-3">
          <Breadcrumb items={breadcrumbs} />
        </div>
      )}
    </header>
  );
};

export default Header;