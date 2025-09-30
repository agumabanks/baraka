/**
 * Theme Switcher for Enterprise Dashboard
 *
 * Handles dark/light mode toggling with localStorage persistence
 * and smooth transitions between themes.
 */

(function() {
    'use strict';

    const THEME_KEY = 'dashboard-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    class ThemeSwitcher {
        constructor() {
            this.root = document.documentElement;
            this.toggleBtn = null;
            this.iconElement = null;
            this.currentTheme = this.getStoredTheme();
            this.init();
        }

        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }

        setup() {
            this.toggleBtn = document.getElementById('theme-toggle');
            this.iconElement = document.getElementById('theme-icon');

            if (!this.toggleBtn || !this.iconElement) {
                console.warn('Theme switcher elements not found');
                return;
            }

            // Apply initial theme
            this.applyTheme(this.currentTheme);

            // Bind event listener
            this.toggleBtn.addEventListener('click', () => this.toggleTheme());

            // Listen for system theme changes (optional)
            this.listenForSystemThemeChanges();
        }

        getStoredTheme() {
            try {
                const stored = localStorage.getItem(THEME_KEY);
                if (stored === THEME_LIGHT || stored === THEME_DARK) {
                    return stored;
                }
            } catch (error) {
                console.warn('Failed to read theme from localStorage:', error);
            }

            // Default to light theme
            return THEME_LIGHT;
        }

        setStoredTheme(theme) {
            try {
                localStorage.setItem(THEME_KEY, theme);
            } catch (error) {
                console.warn('Failed to save theme to localStorage:', error);
            }
        }

        applyTheme(theme) {
            // Set data attribute for CSS selectors
            this.root.setAttribute('data-theme', theme);

            // Update icon
            this.updateIcon(theme);

            // Update button aria-label
            this.toggleBtn.setAttribute('aria-label',
                theme === THEME_DARK ? 'Switch to light mode' : 'Switch to dark mode');

            // Store preference
            this.setStoredTheme(theme);
            this.currentTheme = theme;
        }

        updateIcon(theme) {
            if (theme === THEME_DARK) {
                this.iconElement.className = 'fas fa-sun';
            } else {
                this.iconElement.className = 'fas fa-moon';
            }
        }

        toggleTheme() {
            const newTheme = this.currentTheme === THEME_LIGHT ? THEME_DARK : THEME_LIGHT;
            this.applyTheme(newTheme);

            // Optional: Add visual feedback
            this.addToggleAnimation();
        }

        addToggleAnimation() {
            // Add a subtle animation to the icon
            this.iconElement.style.transform = 'scale(0.8)';
            setTimeout(() => {
                this.iconElement.style.transform = 'scale(1)';
            }, 150);
        }

        listenForSystemThemeChanges() {
            // Optional: Listen for system theme preference changes
            // Only if user hasn't set a manual preference
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

                // Check if user has a stored preference
                const hasStoredPreference = localStorage.getItem(THEME_KEY) !== null;

                if (!hasStoredPreference) {
                    // Apply system preference initially
                    this.applyTheme(mediaQuery.matches ? THEME_DARK : THEME_LIGHT);
                }

                // Listen for changes (modern browsers)
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', (e) => {
                        // Only auto-switch if no manual preference is stored
                        if (!hasStoredPreference) {
                            this.applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                        }
                    });
                }
            }
        }

        // Public API for external control
        setTheme(theme) {
            if (theme === THEME_LIGHT || theme === THEME_DARK) {
                this.applyTheme(theme);
            }
        }

        getTheme() {
            return this.currentTheme;
        }
    }

    // Initialize theme switcher when DOM is ready
    window.themeSwitcher = new ThemeSwitcher();

})();