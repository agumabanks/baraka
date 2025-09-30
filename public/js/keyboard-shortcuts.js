/**
 * Keyboard Shortcuts Handler for Enterprise Dashboard
 *
 * Provides global keyboard shortcuts for improved accessibility and efficiency.
 * Handles navigation, actions, search, and modal controls.
 */

(function() {
    'use strict';

    class KeyboardShortcuts {
        constructor() {
            this.shortcuts = new Map();
            this.searchModal = null;
            this.sequenceBuffer = [];
            this.sequenceTimeout = null;
            this.selectedListIndex = -1;
            this.init();
        }

        init() {
            this.registerShortcuts();
            this.setupEventListeners();
            this.findElements();
        }

        registerShortcuts() {
            // Action shortcuts
            this.shortcuts.set('ctrl+k', () => this.openSearch());
            this.shortcuts.set('cmd+k', () => this.openSearch());
            this.shortcuts.set('ctrl+b', () => this.bookNewShipment());
            this.shortcuts.set('ctrl+s', () => this.saveCurrentForm());
            this.shortcuts.set('ctrl+f', () => this.focusSearch());
            this.shortcuts.set('escape', () => this.handleEscape());
            this.shortcuts.set('?', () => this.showShortcutsHelp());
            
            // List/Table navigation
            this.shortcuts.set('j', () => this.navigateListDown());
            this.shortcuts.set('k', () => this.navigateListUp());
            this.shortcuts.set('enter', () => this.openSelectedItem());
            this.shortcuts.set('ctrl+a', () => this.selectAll());
            this.shortcuts.set('ctrl+d', () => this.deselectAll());
        }

        setupEventListeners() {
            document.addEventListener('keydown', (e) => this.handleKeyDown(e));

            // Re-find elements when DOM changes (for dynamic content)
            document.addEventListener('DOMContentLoaded', () => this.findElements());
            
            // Listen for list item hover to update selected index
            document.addEventListener('mouseover', (e) => {
                const listItem = e.target.closest('tr[tabindex], .list-item[tabindex]');
                if (listItem) {
                    this.selectedListIndex = Array.from(listItem.parentElement.children).indexOf(listItem);
                }
            });
        }

        findElements() {
            // Cache frequently accessed elements
            this.searchInput = document.querySelector('#global-search-input');
            this.notificationsBtn = document.querySelector('#notificationDropdown');
            this.sidebarToggle = document.querySelector('[data-bs-target="#offcanvasDarkNavbar"]');
        }

        handleKeyDown(event) {
            // Skip if user is typing in an input
            if (this.isInputFocused() && !['escape', 'ctrl+s', 'ctrl+k', 'cmd+k'].some(k => k === this.normalizeKey(event))) {
                return;
            }

            // Handle sequential shortcuts (G then D, G then P, etc.)
            if (event.key.toLowerCase() === 'g' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                if (!this.isInputFocused()) {
                    this.sequenceBuffer = ['g'];
                    clearTimeout(this.sequenceTimeout);
                    this.sequenceTimeout = setTimeout(() => this.sequenceBuffer = [], 1000);
                    event.preventDefault();
                    return;
                }
            }

            // Check for second key in sequence
            if (this.sequenceBuffer.length > 0 && this.sequenceBuffer[0] === 'g') {
                const secondKey = event.key.toLowerCase();
                const navShortcuts = {
                    'd': '/dashboard',
                    'p': '/parcels',
                    'm': '/merchants',
                    'h': '/hubs',
                    't': '/todo'
                };
                
                if (navShortcuts[secondKey]) {
                    window.location.href = navShortcuts[secondKey];
                    this.sequenceBuffer = [];
                    event.preventDefault();
                    return;
                }
                this.sequenceBuffer = [];
            }

            const key = this.normalizeKey(event);

            if (this.shortcuts.has(key)) {
                // Don't prevent Ctrl+A and Ctrl+D if in input
                if (this.isInputFocused() && (key === 'ctrl+a' || key === 'ctrl+d')) {
                    return;
                }
                
                // Prevent default behavior for our shortcuts
                event.preventDefault();
                event.stopPropagation();

                // Execute the shortcut action
                this.shortcuts.get(key)();
            }
        }
        
        isInputFocused() {
            const activeElement = document.activeElement;
            return activeElement && (
                activeElement.tagName === 'INPUT' ||
                activeElement.tagName === 'TEXTAREA' ||
                activeElement.tagName === 'SELECT' ||
                activeElement.isContentEditable
            );
        }

        normalizeKey(event) {
            const parts = [];

            if (event.ctrlKey || event.metaKey) {
                parts.push(event.metaKey ? 'cmd' : 'ctrl');
            }

            if (event.altKey) {
                parts.push('alt');
            }

            if (event.shiftKey && event.key !== '?') {
                parts.push('shift');
            }

            // Handle special keys
            let key = event.key.toLowerCase();
            switch (key) {
                case ' ': key = 'space'; break;
                case 'arrowup': key = 'up'; break;
                case 'arrowdown': key = 'down'; break;
                case 'arrowleft': key = 'left'; break;
                case 'arrowright': key = 'right'; break;
                case 'escape': key = 'escape'; break;
                case 'enter': key = 'enter'; break;
                case 'tab': key = 'tab'; break;
            }

            parts.push(key);
            return parts.join('+');
        }

        openSearch() {
            // Create or show global search modal
            if (!this.searchModal) {
                this.createSearchModal();
            }

            // Show the modal
            const modal = new bootstrap.Modal(this.searchModal);
            modal.show();

            // Focus the search input
            setTimeout(() => {
                const input = this.searchModal.querySelector('#global-search-input');
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        }

        createSearchModal() {
            const modalHtml = `
                <div class="modal fade" id="globalSearchModal" tabindex="-1" aria-labelledby="globalSearchLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="globalSearchLabel">
                                    <i class="fas fa-search me-2"></i>Global Search
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="input-group mb-3">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="global-search-input"
                                           placeholder="Search parcels, customers, drivers..." autocomplete="off">
                                </div>
                                <div id="search-results" class="search-results">
                                    <div class="text-muted text-center py-4">
                                        <i class="fas fa-search fa-2x mb-3 opacity-50"></i>
                                        <p>Start typing to search...</p>
                                        <small class="text-muted">Search parcels by tracking number, customer name, or phone</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <small class="text-muted me-auto">
                                    <kbd>↑↓</kbd> Navigate • <kbd>Enter</kbd> Select • <kbd>Esc</kbd> Close
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            this.searchModal = document.getElementById('globalSearchModal');

            // Setup search functionality
            this.setupSearchFunctionality();
        }

        setupSearchFunctionality() {
            const input = this.searchModal.querySelector('#global-search-input');
            const results = this.searchModal.querySelector('#search-results');

            let searchTimeout;
            let selectedIndex = -1;

            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();

                if (query.length < 2) {
                    this.showEmptyState(results);
                    return;
                }

                searchTimeout = setTimeout(() => {
                    this.performSearch(query, results);
                }, 300);
            });

            // Keyboard navigation in results
            input.addEventListener('keydown', (e) => {
                const resultItems = results.querySelectorAll('.search-result-item');

                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, resultItems.length - 1);
                        this.updateSelection(resultItems, selectedIndex);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        this.updateSelection(resultItems, selectedIndex);
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (selectedIndex >= 0 && resultItems[selectedIndex]) {
                            resultItems[selectedIndex].click();
                        }
                        break;
                }
            });
        }

        performSearch(query, resultsContainer) {
            // Show loading state
            resultsContainer.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Searching...</span>
                    </div>
                    <p class="text-muted mt-2">Searching...</p>
                </div>
            `;

            // Simulate API call (replace with actual API call)
            setTimeout(() => {
                // Mock search results - replace with real API call
                const mockResults = this.getMockSearchResults(query);

                if (mockResults.length === 0) {
                    resultsContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-2x mb-3 opacity-50"></i>
                            <p class="text-muted">No results found for "${query}"</p>
                        </div>
                    `;
                    return;
                }

                const resultsHtml = mockResults.map((result, index) => `
                    <div class="search-result-item p-3 border-bottom" data-index="${index}"
                         style="cursor: pointer;" onclick="window.location.href='${result.url}'">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas ${result.icon} fa-lg text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${result.title}</div>
                                <small class="text-muted">${result.subtitle}</small>
                            </div>
                            <div class="flex-shrink-0">
                                <small class="badge bg-${result.badgeColor}">${result.type}</small>
                            </div>
                        </div>
                    </div>
                `).join('');

                resultsContainer.innerHTML = resultsHtml;
            }, 500);
        }

        getMockSearchResults(query) {
            // Mock data - replace with actual API call
            return [
                {
                    title: 'BRK001234567',
                    subtitle: 'John Doe - Delivered',
                    icon: 'fa-box',
                    type: 'Parcel',
                    badgeColor: 'success',
                    url: '/parcels/123'
                },
                {
                    title: 'Jane Smith',
                    subtitle: '+1 234 567 8900',
                    icon: 'fa-user',
                    type: 'Customer',
                    badgeColor: 'info',
                    url: '/customers/456'
                },
                {
                    title: 'Mike Johnson',
                    subtitle: 'Driver - Active',
                    icon: 'fa-truck',
                    type: 'Driver',
                    badgeColor: 'warning',
                    url: '/drivers/789'
                }
            ].filter(item =>
                item.title.toLowerCase().includes(query.toLowerCase()) ||
                item.subtitle.toLowerCase().includes(query.toLowerCase())
            );
        }

        updateSelection(items, selectedIndex) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('bg-light');
                } else {
                    item.classList.remove('bg-light');
                }
            });
        }

        showEmptyState(container) {
            container.innerHTML = `
                <div class="text-muted text-center py-4">
                    <i class="fas fa-search fa-2x mb-3 opacity-50"></i>
                    <p>Start typing to search...</p>
                    <small class="text-muted">Search parcels by tracking number, customer name, or phone</small>
                </div>
            `;
        }

        focusSearch() {
            // Focus the search input if it exists on the current page
            const searchInput = document.querySelector('input[type="search"], input[placeholder*="search" i]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            } else {
                // Open global search if no search input found
                this.openSearch();
            }
        }

        bookNewShipment() {
            // Navigate to create parcel/shipment page
            const createLink = document.querySelector('a[href*="/parcel/create"], a[href*="/parcels/create"], a[href*="/shipment/create"]');
            if (createLink) {
                window.location.href = createLink.href;
            }
        }
        
        saveCurrentForm() {
            // Find and submit the current form
            const form = document.querySelector('form');
            if (form) {
                // Trigger form submit or find submit button
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                } else {
                    form.submit();
                }
            }
        }
        
        navigateListDown() {
            if (this.isInputFocused()) return;
            
            const listItems = document.querySelectorAll('tbody tr[tabindex], .list-item[tabindex]');
            if (listItems.length === 0) return;
            
            this.selectedListIndex = Math.min(this.selectedListIndex + 1, listItems.length - 1);
            listItems[this.selectedListIndex]?.focus();
        }
        
        navigateListUp() {
            if (this.isInputFocused()) return;
            
            const listItems = document.querySelectorAll('tbody tr[tabindex], .list-item[tabindex]');
            if (listItems.length === 0) return;
            
            this.selectedListIndex = Math.max(this.selectedListIndex - 1, 0);
            listItems[this.selectedListIndex]?.focus();
        }
        
        openSelectedItem() {
            const listItems = document.querySelectorAll('tbody tr[tabindex], .list-item[tabindex]');
            if (this.selectedListIndex >= 0 && listItems[this.selectedListIndex]) {
                const link = listItems[this.selectedListIndex].querySelector('a');
                if (link) {
                    link.click();
                }
            }
        }
        
        selectAll() {
            if (this.isInputFocused()) return;
            
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:not([disabled])');
            checkboxes.forEach(cb => cb.checked = true);
            
            // Announce to screen readers
            this.announceToScreenReader(`Selected ${checkboxes.length} items`);
        }
        
        deselectAll() {
            if (this.isInputFocused()) return;
            
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:not([disabled])');
            checkboxes.forEach(cb => cb.checked = false);
            
            // Announce to screen readers
            this.announceToScreenReader('Deselected all items');
        }

        handleEscape() {
            // Close shortcuts help first
            const shortcutsHelp = document.getElementById('shortcuts-help');
            if (shortcutsHelp && shortcutsHelp.style.display !== 'none') {
                this.hideShortcutsHelp();
                return;
            }
            
            // Close modals, dropdowns, or go back
            const openModal = document.querySelector('.modal.show');
            const openDropdown = document.querySelector('.dropdown-menu.show');

            if (openModal) {
                const modal = bootstrap.Modal.getInstance(openModal);
                if (modal) modal.hide();
            } else if (openDropdown) {
                const dropdown = bootstrap.Dropdown.getInstance(openDropdown.previousElementSibling);
                if (dropdown) dropdown.hide();
            }
        }

        showShortcutsHelp() {
            const helpOverlay = document.getElementById('shortcuts-help');
            if (helpOverlay) {
                helpOverlay.style.display = 'flex';
                // Focus the search input
                const searchInput = helpOverlay.querySelector('.shortcuts-search');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
                // Announce to screen readers
                this.announceToScreenReader('Keyboard shortcuts help opened');
            }
        }
        
        hideShortcutsHelp() {
            const helpOverlay = document.getElementById('shortcuts-help');
            if (helpOverlay) {
                helpOverlay.style.display = 'none';
                this.announceToScreenReader('Keyboard shortcuts help closed');
            }
        }
        
        announceToScreenReader(message) {
            const announcement = document.createElement('div');
            announcement.setAttribute('role', 'status');
            announcement.setAttribute('aria-live', 'polite');
            announcement.className = 'sr-only';
            announcement.textContent = message;
            document.body.appendChild(announcement);
            
            setTimeout(() => announcement.remove(), 1000);
        }
    }

    // Initialize keyboard shortcuts when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.keyboardShortcuts = new KeyboardShortcuts();
        });
    } else {
        window.keyboardShortcuts = new KeyboardShortcuts();
    }

})();