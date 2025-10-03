import React, { useEffect, useMemo, useState } from 'react';
import { Bell, ArrowUpRight, Globe2, ListChecks, Moon, Sun, Zap } from 'lucide-react';
import MobileMenuToggle from './MobileMenuToggle';
import LanguageSelector from './LanguageSelector';
import UserMenu from './UserMenu';
import Breadcrumb from './Breadcrumb';
import type { HeaderProps, HeaderQuickAction } from '../../types/header';
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
  appName = 'Dashboard sanaa',
  isSidebarOpen = false,
  quickActions,
  onQuickAction,
  theme,
  onThemeToggle,
  canCreateTodo,
  onCreateTodo,
  todoLabel = 'To-Do',
  frontendUrl = '/',
  showFrontendLink = true,
}) => {
  const unreadNotifications = useMemo(
    () => notifications.filter((notification) => !notification.read).length,
    [notifications]
  );

  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [quickActionsOpen, setQuickActionsOpen] = useState(false);
  const [internalTheme, setInternalTheme] = useState<'light' | 'dark'>(theme ?? 'light');

  useEffect(() => {
    if (theme) {
      setInternalTheme(theme);
    }
  }, [theme]); 

  const resolvedTheme = theme ?? internalTheme;
  const hasQuickActions = (quickActions?.length ?? 0) > 0;
  const showTodoButton = Boolean(canCreateTodo && onCreateTodo);
  const shouldShowFrontend = showFrontendLink !== false;

  const handleNotificationToggle = () => {
    setNotificationsOpen((prev) => {
      const next = !prev;
      if (!prev) {
        setQuickActionsOpen(false);
      }
      return next;
    });
  };

  const handleNotificationClick = (notificationId: string) => {
    const notification = notifications.find((item) => item.id === notificationId);
    if (notification) {
      onNotificationClick(notification);
      setNotificationsOpen(false);
    }
  };

  const toggleTheme = () => {
    const nextTheme = resolvedTheme === 'dark' ? 'light' : 'dark';
    onThemeToggle?.(nextTheme);
    if (!theme) {
      setInternalTheme(nextTheme);
    }
  };

  const handleQuickActionToggle = () => {
    setQuickActionsOpen((prev) => {
      const next = !prev;
      if (!prev) {
        setNotificationsOpen(false);
      }
      return next;
    });
  };

  const handleQuickActionSelect = (action: HeaderQuickAction) => {
    if (onQuickAction) {
      onQuickAction(action);
    } else if (action.href) {
      window.location.assign(action.href);
    }
    setQuickActionsOpen(false);
  };

  return (
    <header className="sticky top-0 z-40 border-b border-mono-gray-200 bg-mono-white/90 backdrop-blur">
      <div className="flex items-center justify-between px-6 py-4 md:px-10">
        {/* Left cluster: menu + brand + breadcrumbs (desktop) */}
        <div className="flex flex-1 items-center gap-4">
          <MobileMenuToggle onToggle={onToggleSidebar} isOpen={isSidebarOpen} />

          <div className="hidden items-center gap-3 md:flex">
            <span className="text-sm font-semibold tracking-[0.35em] uppercase text-mono-gray-700">
              {appName}
            </span>
          </div>


          <div className="hidden flex-1 items-center justify-start pl-6 md:flex">
            <Breadcrumb items={breadcrumbs ?? []} />
          </div>
        </div>

        {/* Right cluster: controls */}
        <div className="flex items-center gap-3 md:gap-4">
          <div className="hidden md:block">
            <LanguageSelector
              currentLanguage={currentLanguage}
              languages={languages}
              onLanguageChange={onLanguageChange}
            />
          </div>

          <button
            type="button"
            className="hidden h-10 w-10 items-center justify-center rounded-full border border-mono-gray-200 bg-mono-white text-mono-gray-600 transition-colors hover:border-mono-gray-900 hover:text-mono-black md:flex"
            aria-label="Toggle theme"
            onClick={toggleTheme}
          >
            {resolvedTheme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          {hasQuickActions && (
            <div className="relative">
              <button
                type="button"
                className="flex items-center gap-2 rounded-full border border-mono-gray-300 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-700 transition-colors hover:border-mono-black hover:text-mono-black"
                onClick={handleQuickActionToggle}
                aria-haspopup="menu"
                aria-expanded={quickActionsOpen}
                aria-label="Open quick actions"
              >
                <Zap size={16} />
                <span className="hidden md:inline">Quick Actions</span>
              </button>

              {quickActionsOpen && (
                <>
                  <div
                    className="fixed inset-0 z-10"
                    onClick={() => setQuickActionsOpen(false)}
                    aria-hidden="true"
                  />
                  <div className="absolute right-0 z-20 mt-3 w-72 rounded-2xl border border-mono-gray-200 bg-mono-white shadow-xl">
                    <div className="flex items-center justify-between border-b border-mono-gray-200 px-4 py-3">
                      <p className="text-sm font-semibold text-mono-black">Quick Actions</p>
                      <button
                        type="button"
                        className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 hover:text-mono-black"
                        onClick={() => setQuickActionsOpen(false)}
                      >
                        Close
                      </button>
                    </div>
                    <ul className="max-h-64 overflow-y-auto py-2">
                      {quickActions?.map((action) => (
                        <li key={action.id}>
                          <button
                            type="button"
                            className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-mono-gray-50"
                            onClick={() => handleQuickActionSelect(action)}
                          >
                            <div className="flex flex-col">
                              <span className="text-sm font-medium text-mono-black">{action.label}</span>
                              {action.description && (
                                <span className="text-xs text-mono-gray-500">{action.description}</span>
                              )}
                            </div>
                            <div className="flex items-center gap-2 text-mono-gray-500">
                              {action.shortcut && (
                                <span className="rounded-full bg-mono-gray-100 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-[0.2em]">
                                  {action.shortcut}
                                </span>
                              )}
                              {action.icon && <i className={`${action.icon} text-xs`} aria-hidden="true" />}
                            </div>
                          </button>
                        </li>
                      ))}
                    </ul>
                  </div>
                </>
              )}
            </div>
          )}

          {shouldShowFrontend && (
            <>
              <a
                href={frontendUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="hidden items-center gap-2 rounded-full border border-mono-gray-300 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-700 transition-colors hover:border-mono-black hover:text-mono-black md:inline-flex"
              >
                Frontend
                <ArrowUpRight size={14} />
              </a>
              <a
                href={frontendUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex h-10 w-10 items-center justify-center rounded-full border border-mono-gray-200 bg-mono-white text-mono-gray-600 transition-colors hover:border-mono-gray-900 hover:text-mono-black md:hidden"
                aria-label="Open frontend"
              >
                <Globe2 size={18} />
              </a>
            </>
          )}

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

          {showTodoButton && (
            <>
              <button
                type="button"
                onClick={onCreateTodo}
                className="hidden items-center gap-2 rounded-full bg-mono-black px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-white transition-colors hover:bg-mono-gray-900 md:inline-flex"
              >
                <ListChecks size={14} />
                {todoLabel}
              </button>
              <button
                type="button"
                onClick={onCreateTodo}
                className="flex h-10 w-10 items-center justify-center rounded-full bg-mono-black text-mono-white transition-colors hover:bg-mono-gray-900 md:hidden"
                aria-label={todoLabel}
              >
                <ListChecks size={18} />
              </button>
            </>
          )}

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
