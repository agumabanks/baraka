import React, { useState } from 'react';
import { ChevronDown, User, Key, LogOut } from 'lucide-react';
import { Avatar } from '../ui';
import type { UserMenuProps } from '../../types/header';

/**
 * User Menu Component
 * Dropdown with profile options, settings, and logout
 */
const UserMenu: React.FC<UserMenuProps> = ({ user, onLogout }) => {
  const [isOpen, setIsOpen] = useState(false);

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      setIsOpen(false);
    }
  };

  const menuItems = [
    {
      label: 'Profile',
      icon: User,
      href: `/profile/${user.id}`,
      action: () => {
        // Navigate to profile
        console.log('Navigate to profile');
        setIsOpen(false);
      }
    },
    {
      label: 'Change Password',
      icon: Key,
      href: `/password/change/${user.id}`,
      action: () => {
        // Navigate to change password
        console.log('Navigate to change password');
        setIsOpen(false);
      }
    }
  ];

  return (
    <div className="relative">
      <button
        type="button"
        className="flex items-center gap-3 px-3 py-2 text-sm font-medium text-mono-gray-700 hover:text-mono-black transition-colors rounded-lg hover:bg-mono-gray-50"
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={handleKeyDown}
        aria-expanded={isOpen}
        aria-haspopup="menu"
        aria-label="User menu"
      >
        <Avatar
          src={user.avatar}
          alt={user.name}
          fallback={user.name.charAt(0).toUpperCase()}
          size="sm"
        />
        <div className="hidden md:block text-left">
          <div className="text-sm font-medium text-mono-black">{user.name}</div>
          <div className="text-xs text-mono-gray-500">{user.role}</div>
        </div>
        <ChevronDown
          size={16}
          className={`transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
        />
      </button>

      {isOpen && (
        <>
          {/* Overlay for mobile */}
          <div
            className="fixed inset-0 z-10 lg:hidden"
            onClick={() => setIsOpen(false)}
            aria-hidden="true"
          />

          {/* Dropdown menu */}
          <div
            className="absolute right-0 mt-2 w-64 bg-mono-white border border-mono-gray-200 rounded-lg shadow-lg z-20"
            role="menu"
            aria-label="User menu options"
          >
            {/* User info header */}
            <div className="px-4 py-3 border-b border-mono-gray-200">
              <div className="flex items-center gap-3">
                <Avatar
                  src={user.avatar}
                  alt={user.name}
                  fallback={user.name.charAt(0).toUpperCase()}
                  size="md"
                />
                <div>
                  <div className="text-sm font-medium text-mono-black">{user.name}</div>
                  <div className="text-xs text-mono-gray-500">{user.email}</div>
                  <div className="text-xs text-mono-gray-400">{user.role}</div>
                </div>
              </div>
            </div>

            {/* Menu items */}
            <div className="py-2">
              {menuItems.map((item, index) => {
                const Icon = item.icon;
                return (
                  <button
                    key={index}
                    type="button"
                    className="w-full flex items-center gap-3 px-4 py-2 text-left text-mono-gray-700 hover:bg-mono-gray-50 hover:text-mono-black transition-colors"
                    onClick={item.action}
                    role="menuitem"
                  >
                    <Icon size={16} />
                    <span className="text-sm">{item.label}</span>
                  </button>
                );
              })}
            </div>

            {/* Divider */}
            <div className="border-t border-mono-gray-200" />

            {/* Logout */}
            <div className="py-2">
              <button
                type="button"
                className="w-full flex items-center gap-3 px-4 py-2 text-left text-mono-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors"
                onClick={() => {
                  onLogout();
                  setIsOpen(false);
                }}
                role="menuitem"
              >
                <LogOut size={16} />
                <span className="text-sm">Logout</span>
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default UserMenu;