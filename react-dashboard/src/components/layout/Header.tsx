import React, { useMemo, useState } from 'react';
import { Bell, ArrowUpRight } from 'lucide-react';
import MobileMenuToggle from './MobileMenuToggle';
import LanguageSelector from './LanguageSelector';
import UserMenu from './UserMenu';
import Breadcrumb from './Breadcrumb';
import type { HeaderProps } from '../../types/header';
import { Badge } from '../ui';

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
  appName = 'Dashboard',
  isSidebarOpen = false,
}) => {
  const unreadNotifications = useMemo(
    () => notifications.filter((notification) => !notification.read).length,
    [notifications]
  );

  const [notificationsOpen, setNotificationsOpen] = useState(false);

  const handleNotificationToggle = () => {
    setNotificationsOpen((prev) => !prev);
  };

  const handleNotificationClick = (notificationId: string) => {
    const notification = notifications.find((item) => item.id === notificationId);
    if (notification) {
      onNotificationClick(notification);
      setNotificationsOpen(false);
    }
  };

  return (
    <header className="sticky top-0 z-40 border-b border-mono-gray-200 bg-mono-white/90 backdrop-blur">
      <div className="flex items-center justify-between px-6 py-4 md:px-10">
        {/* Left cluster: menu + brand + breadcrumbs (desktop) */}
        <div className="flex flex-1 items-center gap-4">
          <MobileMenuToggle onToggle={onToggleSidebar} isOpen={isSidebarOpen} />

          <div className="flex items-center gap-3">
            {logoUrl && (
              <img
                src={logoUrl}
                alt={`${appName} logo`}
                className="hidden h-8 w-auto object-contain sm:block"
              />
            )}
            <div className="leading-tight">
              <p className="text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-500">
                {appName}
              </p>
              <p className="text-sm font-medium text-mono-black">Operations Control Centre</p>
            </div>
          </div>

          <div className="hidden flex-1 items-center justify-start pl-6 md:flex">
            <Breadcrumb items={breadcrumbs ?? []} />
          </div>
        </div>

        {/* Right cluster: controls */}
        <div className="flex items-center gap-3 md:gap-4">
          <LanguageSelector
            currentLanguage={currentLanguage}
            languages={languages}
            onLanguageChange={onLanguageChange}
          />

          <div className="relative">
            <button
              type="button"
              className="relative flex h-10 w-10 items-center justify-center rounded-full border border-mono-gray-200 bg-mono-white text-mono-gray-600 transition-colors hover:border-mono-gray-900 hover:text-mono-black"
              aria-label={
                unreadNotifications > 0
                  ? `Notifications (${unreadNotifications} unread)`
                  : 'Notifications'
              }
              onClick={handleNotificationToggle}
            >
              <Bell size={18} />
              {unreadNotifications > 0 && (
                <Badge className="absolute -right-1 -top-1 bg-mono-black text-[0.65rem] text-mono-white">
                  {unreadNotifications > 9 ? '9+' : unreadNotifications}
                </Badge>
              )}
            </button>

            {notificationsOpen && (
              <>
                <div
                  className="fixed inset-0 z-10"
                  onClick={() => setNotificationsOpen(false)}
                  aria-hidden="true"
                />
                <div className="absolute right-0 z-20 mt-3 w-72 rounded-2xl border border-mono-gray-200 bg-mono-white shadow-xl">
                  <div className="flex items-center justify-between border-b border-mono-gray-200 px-4 py-3">
                    <p className="text-sm font-semibold text-mono-black">Notifications</p>
                    <button
                    type="button"
                    className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 hover:text-mono-black"
                    onClick={() => setNotificationsOpen(false)}
                  >
                    Close
                  </button>
                </div>
                  <div className="max-h-64 overflow-y-auto">
                    {notifications.length === 0 && (
                      <p className="px-4 py-6 text-center text-sm text-mono-gray-500">
                        No notifications yet
                      </p>
                    )}
                    {notifications.map((notification) => (
                    <button
                      key={notification.id}
                      type="button"
                      className={`flex w-full flex-col gap-1 px-4 py-3 text-left transition-colors ${
                        notification.read
                          ? 'text-mono-gray-500 hover:bg-mono-gray-50'
                          : 'bg-mono-gray-25 text-mono-black hover:bg-mono-gray-100'
                      }`}
                      onClick={() => handleNotificationClick(notification.id)}
                    >
                      <span className="text-sm font-medium">{notification.title}</span>
                      <span className="text-xs text-mono-gray-600">{notification.message}</span>
                    </button>
                    ))}
                  </div>
                </div>
              </>
            )}
          </div>

          <a
            href="/"
            target="_blank"
            rel="noopener noreferrer"
            className="hidden items-center gap-2 rounded-full border border-mono-gray-300 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-700 transition-colors hover:border-mono-black hover:text-mono-black md:inline-flex"
          >
            Frontend
            <ArrowUpRight size={14} />
          </a>

          <UserMenu user={user} onLogout={onLogout} />
        </div>
      </div>

      <div className="border-t border-mono-gray-200 px-6 pb-4 pt-2 md:hidden">
        <Breadcrumb items={breadcrumbs ?? []} />
      </div>
    </header>
  );
};

export default Header;
