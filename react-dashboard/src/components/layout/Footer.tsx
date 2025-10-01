import React from 'react'
import type { FooterProps } from '../../types/footer'

const Footer: React.FC<FooterProps> = ({
  copyright,
  companyName = 'Dashboard',
  version = '1.0.0',
  links = [],
  className = ''
}) => {
  const currentYear = new Date().getFullYear()

  return (
    <footer
      className={`fixed bottom-0 left-0 right-0 bg-mono-white border-t border-mono-border shadow-mono-subtle z-10 ${className}`}
      role="contentinfo"
      aria-label="Site footer"
    >
      <div className="container-fluid px-4 py-3">
        <div className="flex flex-col sm:flex-row justify-between items-center gap-2">
          {/* Copyright Section */}
          <div className="text-center sm:text-left">
            <p className="text-sm text-mono-gray-600 mb-0">
              © {currentYear} {companyName}. {copyright}
            </p>
          </div>

          {/* Version and Links Section */}
          <div className="flex flex-col sm:flex-row items-center gap-4">
            {/* Version */}
            <span className="text-xs text-mono-gray-500 font-medium">
              v{version}
            </span>

            {/* Links */}
            {links.length > 0 && (
              <nav aria-label="Footer navigation">
                <ul className="flex items-center gap-4">
                  {links.map((link, index) => (
                    <li key={index}>
                      <a
                        href={link.href}
                        className="text-xs text-mono-gray-600 hover:text-mono-black transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 rounded-sm px-1 py-0.5"
                        {...(link.external && {
                          target: '_blank',
                          rel: 'noopener noreferrer',
                          'aria-label': `${link.label} (opens in new tab)`
                        })}
                      >
                        {link.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            )}
          </div>
        </div>
      </div>
    </footer>
  )
}

export default Footer