@props([
    'currentRoute' => Route::currentRouteName(),
    'currentContext' => null
])

@php
// Determine context from current route
$context = $currentContext ?? 'dashboard';
if (str_contains($currentRoute, 'parcel')) {
    $context = 'parcel';
} elseif (str_contains($currentRoute, 'booking')) {
    $context = 'booking';
} elseif (str_contains($currentRoute, 'customer')) {
    $context = 'customer';
} elseif (str_contains($currentRoute, 'delivery')) {
    $context = 'delivery';
} elseif (str_contains($currentRoute, 'report')) {
    $context = 'reports';
}

// Define context-aware actions
$contextActions = [
    'dashboard' => [
        ['title' => 'Book Shipment', 'url' => route('admin.booking.step1'), 'icon' => '📦', 'permission' => 'booking_create', 'shortcut' => 'Ctrl+B'],
        ['title' => "Today's Queue", 'url' => route('dashboard.index'), 'icon' => '📋', 'permission' => null, 'shortcut' => 'Ctrl+Q'],
        ['title' => 'View Reports', 'url' => '#', 'icon' => '📊', 'permission' => null, 'shortcut' => 'Ctrl+R'],
    ],
    'parcel' => [
        ['title' => 'Create Parcel', 'url' => route('parcel.create'), 'icon' => '📦', 'permission' => 'parcel_create', 'shortcut' => 'Ctrl+N'],
        ['title' => 'Bulk Upload', 'url' => route('parcel.parcel-import'), 'icon' => '📤', 'permission' => 'parcel_create', 'shortcut' => 'Ctrl+U'],
        ['title' => 'Assign Delivery', 'url' => '#', 'icon' => '🚚', 'permission' => 'parcel_assign', 'shortcut' => 'Ctrl+A'],
        ['title' => 'Update Status', 'url' => '#', 'icon' => '🔄', 'permission' => 'parcel_status_update', 'shortcut' => 'Ctrl+S'],
    ],
    'booking' => [
        ['title' => 'New Booking', 'url' => route('admin.booking.step1'), 'icon' => '➕', 'permission' => 'booking_create', 'shortcut' => 'Ctrl+N'],
        ['title' => 'View All Bookings', 'url' => route('parcel.index'), 'icon' => '📋', 'permission' => 'parcel_list', 'shortcut' => 'Ctrl+L'],
    ],
    'delivery' => [
        ['title' => 'Assign Pickup', 'url' => '#', 'icon' => '📥', 'permission' => 'parcel_assign', 'shortcut' => 'Ctrl+P'],
        ['title' => 'Update Status', 'url' => '#', 'icon' => '🔄', 'permission' => 'parcel_status_update', 'shortcut' => 'Ctrl+S'],
        ['title' => 'View Manifest', 'url' => '#', 'icon' => '📄', 'permission' => null, 'shortcut' => 'Ctrl+M'],
    ],
    'customer' => [
        ['title' => 'Add Customer', 'url' => route('admin.customers.create'), 'icon' => '👤', 'permission' => 'customer_create', 'shortcut' => 'Ctrl+N'],
        ['title' => 'View All Customers', 'url' => route('admin.customers.index'), 'icon' => '👥', 'permission' => 'customer_list', 'shortcut' => 'Ctrl+L'],
    ],
    'reports' => [
        ['title' => 'Generate Report', 'url' => '#', 'icon' => '📊', 'permission' => null, 'shortcut' => 'Ctrl+G'],
        ['title' => 'Export Data', 'url' => '#', 'icon' => '💾', 'permission' => null, 'shortcut' => 'Ctrl+E'],
    ],
];

// Common actions available everywhere
$commonActions = [
    ['title' => 'Book Shipment', 'url' => route('admin.booking.step1'), 'icon' => '📦', 'permission' => 'booking_create', 'shortcut' => 'Ctrl+B'],
    ['title' => 'Add Customer', 'url' => route('admin.customers.create'), 'icon' => '👤', 'permission' => 'customer_create', 'shortcut' => 'Ctrl+Shift+C'],
    ['title' => 'Create Support Ticket', 'url' => route('support.add'), 'icon' => '💬', 'permission' => 'support_create', 'shortcut' => 'Ctrl+T'],
    ['title' => 'Bulk Upload Parcels', 'url' => route('parcel.parcel-import'), 'icon' => '📤', 'permission' => 'parcel_create', 'shortcut' => 'Ctrl+U'],
];

// Get recent actions from session
$recentActions = session('recent_actions', []);

// Filter actions based on permissions
$filteredContextActions = collect($contextActions[$context] ?? [])->filter(function($action) {
    return !$action['permission'] || hasPermission($action['permission']);
})->toArray();

$filteredCommonActions = collect($commonActions)->filter(function($action) {
    return !$action['permission'] || hasPermission($action['permission']);
})->take(4)->toArray();
@endphp

{{-- Quick Actions Dropdown --}}
<div class="nav-item dropdown quick-actions-container" {{ $attributes }}>
    <button class="btn btn-primary btn-sm dropdown-toggle quick-actions-trigger" 
            type="button"
            id="quickActionsDropdown" 
            data-bs-toggle="dropdown"
            aria-expanded="false" 
            aria-label="Quick actions menu - Press Ctrl+K to open"
            title="Quick Actions (Ctrl+K)">
        <i class="fas fa-bolt me-1" aria-hidden="true"></i>
        <span class="quick-actions-label">Quick Actions</span>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end quick-actions-dropdown" 
         aria-labelledby="quickActionsDropdown"
         role="menu">
        
        {{-- Search Input --}}
        <div class="quick-actions-search-container">
            <input type="search" 
                   class="form-control quick-actions-search" 
                   placeholder="Search actions..." 
                   aria-label="Search quick actions"
                   role="combobox"
                   aria-expanded="false"
                   aria-controls="quick-actions-results"
                   aria-autocomplete="list" />
            <i class="fas fa-search quick-actions-search-icon" aria-hidden="true"></i>
        </div>

        {{-- Recent Actions --}}
        @if(!empty($recentActions))
            <div class="quick-actions-section" data-section="recent">
                <h6 class="quick-actions-section-title">Recent Actions</h6>
                <ul class="quick-actions-list" role="list" id="recent-actions-list">
                    @foreach(array_slice($recentActions, 0, 3) as $action)
                        <li class="quick-action-item" role="menuitem" tabindex="0" data-action-url="{{ $action['url'] }}">
                            <span class="action-icon">{{ $action['icon'] }}</span>
                            <span class="action-label">{{ $action['title'] }}</span>
                            @if(isset($action['shortcut']))
                                <span class="action-shortcut">{{ $action['shortcut'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <hr class="dropdown-divider">
        @endif

        {{-- Context-Aware Actions --}}
        @if(!empty($filteredContextActions))
            <div class="quick-actions-section" data-section="context">
                <h6 class="quick-actions-section-title">{{ ucfirst($context) }} Actions</h6>
                <ul class="quick-actions-list" role="list" id="context-actions-list">
                    @foreach($filteredContextActions as $action)
                        <li class="quick-action-item" 
                            role="menuitem" 
                            tabindex="0" 
                            data-action-url="{{ $action['url'] }}"
                            data-action-title="{{ $action['title'] }}"
                            data-action-icon="{{ $action['icon'] }}"
                            data-action-shortcut="{{ $action['shortcut'] ?? '' }}">
                            <span class="action-icon">{{ $action['icon'] }}</span>
                            <span class="action-label">{{ $action['title'] }}</span>
                            @if(isset($action['shortcut']))
                                <span class="action-shortcut">{{ $action['shortcut'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <hr class="dropdown-divider">
        @endif

        {{-- Common Actions --}}
        <div class="quick-actions-section" data-section="common">
            <h6 class="quick-actions-section-title">Common Actions</h6>
            <ul class="quick-actions-list" role="list" id="common-actions-list">
                @foreach($filteredCommonActions as $action)
                    <li class="quick-action-item" 
                        role="menuitem" 
                        tabindex="0" 
                        data-action-url="{{ $action['url'] }}"
                        data-action-title="{{ $action['title'] }}"
                        data-action-icon="{{ $action['icon'] }}"
                        data-action-shortcut="{{ $action['shortcut'] ?? '' }}">
                        <span class="action-icon">{{ $action['icon'] }}</span>
                        <span class="action-label">{{ $action['title'] }}</span>
                        @if(isset($action['shortcut']))
                            <span class="action-shortcut">{{ $action['shortcut'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- No Results Message (initially hidden) --}}
        <div class="quick-actions-no-results" style="display: none;">
            <i class="fas fa-search" aria-hidden="true"></i>
            <p>No actions found</p>
        </div>
    </div>
</div>

{{-- Hidden form for tracking recent actions --}}
<form id="track-recent-action" method="POST" action="{{ route('dashboard.index') }}" style="display: none;">
    @csrf
    <input type="hidden" name="action_title" id="recent-action-title">
    <input type="hidden" name="action_url" id="recent-action-url">
    <input type="hidden" name="action_icon" id="recent-action-icon">
</form>

@push('styles')
<style>
/* Quick Actions Styles */
.quick-actions-container {
    position: relative;
}

.quick-actions-trigger {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--font-size-body-small);
    font-weight: 500;
    border: none;
    background: var(--primary-500);
    color: white;
    border-radius: var(--border-radius-small);
    transition: all 0.2s ease;
}

.quick-actions-trigger:hover {
    background: var(--primary-600);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.quick-actions-trigger:focus {
    outline: var(--focus-ring);
    outline-offset: 2px;
}

.quick-actions-dropdown {
    min-width: 350px;
    max-width: 400px;
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid var(--neutral-200);
    border-radius: var(--border-radius-base);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-sm);
    margin-top: var(--spacing-xs);
}

.quick-actions-search-container {
    position: relative;
    margin-bottom: var(--spacing-md);
}

.quick-actions-search {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.5rem;
    border: 1px solid var(--neutral-200);
    border-radius: var(--border-radius-small);
    font-size: var(--font-size-body-small);
    transition: all 0.2s ease;
}

.quick-actions-search:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(126, 0, 149, 0.1);
}

.quick-actions-search-icon {
    position: absolute;
    left: var(--spacing-md);
    top: 50%;
    transform: translateY(-50%);
    color: var(--neutral-500);
    pointer-events: none;
}

.quick-actions-section {
    margin-bottom: var(--spacing-md);
}

.quick-actions-section-title {
    font-size: var(--font-size-caption);
    font-weight: 600;
    color: var(--neutral-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--spacing-sm);
    padding: 0 var(--spacing-sm);
}

.quick-actions-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-action-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-sm) var(--spacing-md);
    color: var(--neutral-700);
    text-decoration: none;
    border-radius: var(--border-radius-small);
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
}

.quick-action-item:hover {
    background: var(--primary-50);
    color: var(--primary-600);
}

.quick-action-item:focus {
    background: var(--primary-50);
    color: var(--primary-600);
    box-shadow: inset 0 0 0 2px var(--primary-500);
}

.quick-action-item.hidden {
    display: none;
}

.action-icon {
    font-size: 1.25rem;
    min-width: 1.5rem;
    text-align: center;
}

.action-label {
    flex: 1;
    font-size: var(--font-size-body-small);
    font-weight: 500;
}

.action-shortcut {
    font-size: var(--font-size-caption);
    color: var(--neutral-500);
    background: var(--neutral-100);
    padding: 2px var(--spacing-xs);
    border-radius: var(--border-radius-small);
    font-family: monospace;
    white-space: nowrap;
}

.quick-actions-no-results {
    text-align: center;
    padding: var(--spacing-xl) var(--spacing-md);
    color: var(--neutral-500);
}

.quick-actions-no-results i {
    font-size: 2rem;
    margin-bottom: var(--spacing-sm);
    opacity: 0.5;
}

.quick-actions-no-results p {
    margin: 0;
    font-size: var(--font-size-body-small);
}

.dropdown-divider {
    margin: var(--spacing-sm) 0;
    border-color: var(--neutral-200);
}

/* Responsive Design */
@media (max-width: 768px) {
    .quick-actions-label {
        display: none;
    }
    
    .quick-actions-trigger {
        padding: var(--spacing-sm);
    }
    
    .quick-actions-dropdown {
        min-width: 300px;
        max-width: 90vw;
    }
}

@media (max-width: 480px) {
    .quick-actions-dropdown {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        min-width: 90vw;
        max-width: 90vw;
        max-height: 70vh;
    }
    
    .action-shortcut {
        display: none;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .quick-actions-trigger {
        border: 2px solid currentColor;
    }
    
    .quick-action-item:focus {
        box-shadow: inset 0 0 0 3px var(--primary-500);
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .quick-actions-trigger,
    .quick-action-item {
        transition: none;
    }
}

/* Dark Mode */
[data-theme="dark"] .quick-actions-dropdown {
    background: var(--neutral-800);
    border-color: var(--neutral-600);
}

[data-theme="dark"] .quick-actions-section-title {
    color: var(--neutral-400);
}

[data-theme="dark"] .quick-action-item {
    color: var(--neutral-200);
}

[data-theme="dark"] .quick-action-item:hover,
[data-theme="dark"] .quick-action-item:focus {
    background: var(--primary-900);
    color: var(--primary-300);
}

[data-theme="dark"] .quick-actions-search {
    background: var(--neutral-700);
    border-color: var(--neutral-600);
    color: var(--neutral-100);
}

[data-theme="dark"] .action-shortcut {
    background: var(--neutral-700);
    color: var(--neutral-400);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeQuickActions();
});

function initializeQuickActions() {
    const dropdown = document.querySelector('.quick-actions-dropdown');
    const trigger = document.querySelector('.quick-actions-trigger');
    const searchInput = document.querySelector('.quick-actions-search');
    const actionItems = document.querySelectorAll('.quick-action-item');
    const sections = document.querySelectorAll('.quick-actions-section');
    const noResults = document.querySelector('.quick-actions-no-results');
    
    if (!dropdown || !trigger || !searchInput) return;

    // Keyboard shortcut: Ctrl+K to open quick actions
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            trigger.click();
            setTimeout(() => searchInput.focus(), 100);
        }

        // Close on Escape
        if (e.key === 'Escape' && dropdown.classList.contains('show')) {
            trigger.click();
        }
    });

    // Search functionality
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Show all items and sections
            actionItems.forEach(item => item.classList.remove('hidden'));
            sections.forEach(section => section.style.display = 'block');
            noResults.style.display = 'none';
            return;
        }

        let hasResults = false;

        // Filter action items
        actionItems.forEach(item => {
            const label = item.querySelector('.action-label')?.textContent.toLowerCase() || '';
            const shortcut = item.dataset.actionShortcut?.toLowerCase() || '';
            
            if (label.includes(searchTerm) || shortcut.includes(searchTerm)) {
                item.classList.remove('hidden');
                hasResults = true;
            } else {
                item.classList.add('hidden');
            }
        });

        // Hide empty sections
        sections.forEach(section => {
            const visibleItems = section.querySelectorAll('.quick-action-item:not(.hidden)');
            section.style.display = visibleItems.length > 0 ? 'block' : 'none';
        });

        // Show/hide no results message
        noResults.style.display = hasResults ? 'none' : 'block';
    });

    // Keyboard navigation within dropdown
    let focusedIndex = -1;
    const focusableItems = Array.from(actionItems);

    searchInput.addEventListener('keydown', function(e) {
        const visibleItems = focusableItems.filter(item => !item.classList.contains('hidden'));
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusedIndex = Math.min(focusedIndex + 1, visibleItems.length - 1);
            if (visibleItems[focusedIndex]) {
                visibleItems[focusedIndex].focus();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (focusedIndex <= 0) {
                focusedIndex = -1;
                searchInput.focus();
            } else {
                focusedIndex--;
                visibleItems[focusedIndex].focus();
            }
        }
    });

    // Item navigation
    actionItems.forEach((item, index) => {
        item.addEventListener('keydown', function(e) {
            const visibleItems = focusableItems.filter(item => !item.classList.contains('hidden'));
            const currentIndex = visibleItems.indexOf(this);
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, visibleItems.length - 1);
                visibleItems[nextIndex]?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (currentIndex === 0) {
                    searchInput.focus();
                } else {
                    visibleItems[currentIndex - 1]?.focus();
                }
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });

        // Handle click/action
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.actionUrl;
            
            // Track recent action
            trackRecentAction({
                title: this.dataset.actionTitle || this.querySelector('.action-label')?.textContent,
                url: url,
                icon: this.dataset.actionIcon || this.querySelector('.action-icon')?.textContent,
                shortcut: this.dataset.actionShortcut
            });

            // Navigate to URL
            if (url && url !== '#') {
                window.location.href = url;
            }
        });
    });

    // Reset search when dropdown opens
    trigger.addEventListener('click', function() {
        setTimeout(() => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            focusedIndex = -1;
        }, 50);
    });

    // Focus search input when dropdown opens
    const dropdownElement = document.getElementById('quickActionsDropdown');
    if (dropdownElement) {
        dropdownElement.addEventListener('shown.bs.dropdown', function() {
            searchInput.focus();
        });
    }
}

// Track recent actions in session
function trackRecentAction(action) {
    // Store in localStorage for persistence
    let recentActions = JSON.parse(localStorage.getItem('recent_actions') || '[]');
    
    // Remove duplicate if exists
    recentActions = recentActions.filter(a => a.url !== action.url);
    
    // Add new action to the beginning
    recentActions.unshift(action);
    
    // Keep only last 5 actions
    recentActions = recentActions.slice(0, 5);
    
    // Save back to localStorage
    localStorage.setItem('recent_actions', JSON.stringify(recentActions));
    
    // Also update session via hidden form (optional)
    const form = document.getElementById('track-recent-action');
    if (form) {
        document.getElementById('recent-action-title').value = action.title;
        document.getElementById('recent-action-url').value = action.url;
        document.getElementById('recent-action-icon').value = action.icon;
        
        // Submit via fetch to avoid page reload
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(err => console.error('Failed to track action:', err));
    }
}

// Load recent actions from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentActionsFromStorage();
});

function loadRecentActionsFromStorage() {
    const recentActions = JSON.parse(localStorage.getItem('recent_actions') || '[]');
    const recentList = document.getElementById('recent-actions-list');
    
    if (recentActions.length > 0 && recentList) {
        // Update the recent actions section
        recentList.innerHTML = recentActions.slice(0, 3).map(action => `
            <li class="quick-action-item" 
                role="menuitem" 
                tabindex="0" 
                data-action-url="${action.url}"
                data-action-title="${action.title}"
                data-action-icon="${action.icon}"
                data-action-shortcut="${action.shortcut || ''}">
                <span class="action-icon">${action.icon}</span>
                <span class="action-label">${action.title}</span>
                ${action.shortcut ? `<span class="action-shortcut">${action.shortcut}</span>` : ''}
            </li>
        `).join('');
        
        // Re-attach event listeners to new items
        initializeQuickActions();
    }
}
</script>
@endpush