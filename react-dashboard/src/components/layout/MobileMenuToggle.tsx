import React from 'react';
import { Menu, X } from 'lucide-react';
import type { MobileMenuToggleProps } from '../../types/header';

/**
 * Mobile Menu Toggle Component
 * Button to toggle sidebar on mobile devices
 */
const MobileMenuToggle: React.FC<MobileMenuToggleProps> = ({
  onToggle,
  isOpen
}) => {
  return (
    <button
      type="button"
      className="lg:hidden p-2 rounded-lg bg-mono-gray-100 hover:bg-mono-gray-200 text-mono-gray-700 hover:text-mono-black transition-all duration-200 hover:scale-105"
      onClick={onToggle}
      aria-label={isOpen ? 'Close sidebar' : 'Open sidebar'}
      aria-expanded={isOpen}
    >
      {isOpen ? (
        <X size={20} className="transition-transform duration-200" />
      ) : (
        <Menu size={20} className="transition-transform duration-200" />
      )}
    </button>
  );
};

export default MobileMenuToggle;