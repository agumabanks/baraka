@props([
    'breadcrumbs' => [],
    'showQuickActions' => true,
    'maxVisible' => 3,
    'contextualActions' => []
])

@php
    $totalBreadcrumbs = count($breadcrumbs);
    $shouldCollapse = $totalBreadcrumbs > $maxVisible;
    $visibleBreadcrumbs = $shouldCollapse ? array_slice($breadcrumbs, -$maxVisible) : $breadcrumbs;
    $hiddenCount = $shouldCollapse ? $totalBreadcrumbs - $maxVisible : 0;
@endphp

{{-- Breadcrumb Navigation --}}
<nav aria-label="{{ __('navigation.breadcrumb') }}" class="breadcrumb-container" role="navigation">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">

        {{-- Hidden breadcrumbs for screen readers --}}
        @if($shouldCollapse && $hiddenCount > 0)
            <li class="sr-only" aria-live="polite">
                {{ trans_choice('navigation.showing_last_breadcrumbs', $maxVisible, ['count' => $maxVisible, 'total' => $totalBreadcrumbs]) }}
            </li>
        @endif

        {{-- Collapsed indicator --}}
        @if($shouldCollapse)
            <li class="breadcrumb-item breadcrumb-collapsed" aria-hidden="true">
                <button type="button"
                        class="breadcrumb-expand-btn"
                        aria-label="{{ __('navigation.expand_breadcrumb', ['count' => $hiddenCount]) }}"
                        title="{{ __('navigation.expand_breadcrumb', ['count' => $hiddenCount]) }}">
                    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ __('navigation.expand_breadcrumb', ['count' => $hiddenCount]) }}</span>
                </button>
                <ol class="breadcrumb-dropdown" role="list">
                    @foreach(array_slice($breadcrumbs, 0, $hiddenCount) as $index => $crumb)
                        <li class="breadcrumb-dropdown-item" role="listitem">
                            <a href="{{ $crumb['url'] ?? '#' }}"
                               class="breadcrumb-dropdown-link {{ $crumb['active'] ?? false ? 'active' : '' }}"
                               @if($crumb['active'] ?? false) aria-current="page" @endif>
                                <i class="{{ $crumb['icon'] ?? 'fas fa-folder' }}" aria-hidden="true"></i>
                                {{ $crumb['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </li>
        @endif

        {{-- Visible breadcrumbs --}}
        @foreach($visibleBreadcrumbs as $index => $breadcrumb)
            @php
                $isLast = $index === count($visibleBreadcrumbs) - 1;
                $position = $shouldCollapse ? $totalBreadcrumbs - $maxVisible + $index + 1 : $index + 1;
            @endphp

            <li class="breadcrumb-item {{ $breadcrumb['active'] ?? false ? 'active' : '' }}"
                itemprop="itemListElement"
                itemscope
                itemtype="https://schema.org/ListItem">

                @if($breadcrumb['active'] ?? false)
                    {{-- Current page - not clickable --}}
                    <span class="breadcrumb-current"
                          itemprop="name"
                          aria-current="page">
                        @if($breadcrumb['icon'] ?? false)
                            <i class="{{ $breadcrumb['icon'] }}" aria-hidden="true"></i>
                        @endif
                        {{ $breadcrumb['title'] }}
                    </span>
                @else
                    {{-- Clickable breadcrumb --}}
                    <a href="{{ $breadcrumb['url'] ?? '#' }}"
                       class="breadcrumb-link"
                       itemprop="item"
                       @if($breadcrumb['data'] ?? false)
                           @foreach($breadcrumb['data'] as $key => $value)
                               data-{{ $key }}="{{ $value }}"
                           @endforeach
                       @endif
                       @if($breadcrumb['onclick'] ?? false)
                           onclick="{{ $breadcrumb['onclick'] }}"
                       @endif>
                        <span itemprop="name">
                            @if($breadcrumb['icon'] ?? false)
                                <i class="{{ $breadcrumb['icon'] }}" aria-hidden="true"></i>
                            @endif
                            {{ $breadcrumb['title'] }}
                        </span>
                    </a>
                @endif

                {{-- Schema.org position meta --}}
                <meta itemprop="position" content="{{ $position }}" />
            </li>
        @endforeach
    </ol>

    {{-- Contextual Quick Actions --}}
    @if($showQuickActions && !empty($contextualActions))
        <div class="breadcrumb-actions">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary breadcrumb-actions-btn"
                        type="button"
                        id="breadcrumbActionsDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="{{ __('navigation.quick_actions') }}">
                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ __('navigation.quick_actions') }}</span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end breadcrumb-actions-dropdown"
                    aria-labelledby="breadcrumbActionsDropdown">
                    @foreach($contextualActions as $action)
                        <li>
                            @if($action['type'] ?? 'link' === 'divider')
                                <hr class="dropdown-divider">
                            @else
                                <a href="{{ $action['url'] ?? '#' }}"
                                   class="dropdown-item breadcrumb-action-item {{ $action['class'] ?? '' }}"
                                   @if($action['onclick'] ?? false)
                                       onclick="{{ $action['onclick'] }}"
                                   @endif
                                   @if($action['data'] ?? false)
                                       @foreach($action['data'] as $key => $value)
                                           data-{{ $key }}="{{ $value }}"
                                       @endforeach
                                   @endif
                                   @if($action['target'] ?? false)
                                       target="{{ $action['target'] }}"
                                   @endif>
                                    @if($action['icon'] ?? false)
                                        <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                    @endif
                                    {{ $action['title'] }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</nav>

{{-- Breadcrumb Styles --}}
<style>
.breadcrumb-container {
    background: var(--neutral-50);
    border-bottom: 1px solid var(--neutral-200);
    padding: var(--spacing-sm) 0;
    margin-bottom: var(--spacing-lg);
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin: 0;
    padding: 0;
    list-style: none;
    gap: var(--spacing-xs);
    font-size: var(--font-size-body-small);
    line-height: 1.4;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    color: var(--neutral-500);
    position: relative;
}

.breadcrumb-item:not(:last-child)::after {
    content: '/';
    margin: 0 var(--spacing-xs);
    color: var(--neutral-300);
    font-weight: 300;
}

.breadcrumb-item.active {
    color: var(--primary-600);
    font-weight: 600;
}

.breadcrumb-link {
    color: var(--neutral-500);
    text-decoration: none;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-small);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    position: relative;
}

.breadcrumb-link:hover {
    color: var(--primary-600);
    background: var(--primary-50);
    text-decoration: none;
}

.breadcrumb-link:focus {
    outline: var(--focus-ring);
    outline-offset: 2px;
}

.breadcrumb-current {
    color: var(--primary-600);
    font-weight: 600;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-small);
    background: var(--primary-50);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.breadcrumb-collapsed {
    position: relative;
}

.breadcrumb-expand-btn {
    background: none;
    border: 1px solid var(--neutral-300);
    color: var(--neutral-500);
    padding: var(--spacing-xs);
    border-radius: var(--border-radius-small);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: var(--font-size-caption);
    line-height: 1;
}

.breadcrumb-expand-btn:hover {
    background: var(--neutral-100);
    border-color: var(--neutral-400);
    color: var(--neutral-700);
}

.breadcrumb-expand-btn:focus {
    outline: var(--focus-ring);
    outline-offset: 2px;
}

.breadcrumb-dropdown {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--border-radius-base);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-sm);
    margin-top: var(--spacing-xs);
    list-style: none;
    min-width: 200px;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}

.breadcrumb-collapsed:hover .breadcrumb-dropdown,
.breadcrumb-expand-btn:focus + .breadcrumb-dropdown {
    opacity: 1;
    visibility: visible;
}

.breadcrumb-dropdown-item {
    margin-bottom: var(--spacing-xs);
}

.breadcrumb-dropdown-item:last-child {
    margin-bottom: 0;
}

.breadcrumb-dropdown-link {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    color: var(--neutral-600);
    text-decoration: none;
    border-radius: var(--border-radius-small);
    transition: all 0.2s ease;
    font-size: var(--font-size-body-small);
}

.breadcrumb-dropdown-link:hover {
    background: var(--primary-50);
    color: var(--primary-600);
}

.breadcrumb-dropdown-link.active {
    background: var(--primary-500);
    color: white;
}

.breadcrumb-actions {
    margin-left: auto;
    display: flex;
    align-items: center;
}

.breadcrumb-actions-btn {
    border-color: var(--neutral-300);
    color: var(--neutral-500);
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-caption);
}

.breadcrumb-actions-btn:hover {
    background: var(--primary-50);
    border-color: var(--primary-500);
    color: var(--primary-600);
}

.breadcrumb-actions-dropdown {
    min-width: 200px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--neutral-200);
    padding: var(--spacing-sm) 0;
}

.breadcrumb-action-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-base);
    font-size: var(--font-size-body-small);
    color: var(--neutral-700);
    transition: all 0.2s ease;
}

.breadcrumb-action-item:hover {
    background: var(--primary-50);
    color: var(--primary-600);
}

.breadcrumb-action-item:focus {
    outline: var(--focus-ring);
    outline-offset: -2px;
}

/* Screen reader only content */
.sr-only,
.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

/* Responsive design */
@media (max-width: 768px) {
    .breadcrumb-container {
        padding: var(--spacing-xs) 0;
        margin-bottom: var(--spacing-md);
    }

    .breadcrumb {
        font-size: var(--font-size-caption);
        gap: var(--spacing-xs);
    }

    .breadcrumb-link,
    .breadcrumb-current {
        padding: var(--spacing-xs);
    }

    .breadcrumb-actions {
        order: -1;
        margin-left: 0;
        margin-right: var(--spacing-sm);
    }

    .breadcrumb-actions-btn {
        padding: var(--spacing-xs);
        min-width: auto;
    }

    /* Hide text on mobile for space */
    .breadcrumb-actions-btn .visually-hidden {
        display: none;
    }
}

@media (max-width: 480px) {
    .breadcrumb {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-xs);
    }

    .breadcrumb-item:not(:last-child)::after {
        display: none;
    }

    .breadcrumb-actions {
        order: -1;
        align-self: flex-end;
        margin: 0;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .breadcrumb-container {
        border-bottom: 2px solid var(--neutral-900);
    }

    .breadcrumb-link {
        border: 1px solid transparent;
    }

    .breadcrumb-link:focus {
        border-color: var(--neutral-900);
    }

    .breadcrumb-current {
        border: 1px solid var(--primary-600);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .breadcrumb-link,
    .breadcrumb-expand-btn,
    .breadcrumb-dropdown,
    .breadcrumb-action-item {
        transition: none;
    }

    .breadcrumb-dropdown {
        transition: opacity 0.1s ease;
    }
}

/* Dark mode support */
[data-theme="dark"] .breadcrumb-container {
    background: var(--neutral-900);
    border-bottom-color: var(--neutral-700);
}

[data-theme="dark"] .breadcrumb-item {
    color: var(--neutral-400);
}

[data-theme="dark"] .breadcrumb-item.active {
    color: var(--primary-400);
}

[data-theme="dark"] .breadcrumb-link {
    color: var(--neutral-400);
}

[data-theme="dark"] .breadcrumb-link:hover {
    background: var(--primary-900);
    color: var(--primary-300);
}

[data-theme="dark"] .breadcrumb-current {
    background: var(--primary-900);
    color: var(--primary-300);
}

[data-theme="dark"] .breadcrumb-expand-btn {
    background: var(--neutral-800);
    border-color: var(--neutral-600);
    color: var(--neutral-300);
}

[data-theme="dark"] .breadcrumb-dropdown {
    background: var(--neutral-800);
    border-color: var(--neutral-600);
}

[data-theme="dark"] .breadcrumb-dropdown-link {
    color: var(--neutral-300);
}

[data-theme="dark"] .breadcrumb-dropdown-link:hover {
    background: var(--primary-900);
    color: var(--primary-300);
}
</style>

{{-- Breadcrumb JavaScript --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Breadcrumb drill-down functionality
    initializeBreadcrumbDrilldown();

    // Keyboard navigation
    initializeBreadcrumbKeyboardNav();

    // Progressive disclosure
    initializeProgressiveDisclosure();
});

function initializeBreadcrumbDrilldown() {
    // Handle breadcrumb link clicks for drill-down
    document.querySelectorAll('.breadcrumb-link[data-drilldown]').forEach(link => {
        link.addEventListener('click', function(e) {
            const drilldownData = this.dataset.drilldown;
            if (drilldownData) {
                e.preventDefault();

                // Update breadcrumb state
                updateBreadcrumbState(this);

                // Trigger drill-down action
                if (window.BreadcrumbManager) {
                    window.BreadcrumbManager.drillDown(JSON.parse(drilldownData));
                }
            }
        });
    });

    // Handle expand/collapse functionality
    document.querySelectorAll('.breadcrumb-expand-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            toggleBreadcrumbDropdown(this);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!btn.contains(e.target) && !btn.nextElementSibling?.contains(e.target)) {
                closeBreadcrumbDropdown(btn.nextElementSibling);
            }
        });
    });
}

function initializeBreadcrumbKeyboardNav() {
    document.querySelectorAll('.breadcrumb').forEach(breadcrumb => {
        const items = breadcrumb.querySelectorAll('.breadcrumb-link, .breadcrumb-current, .breadcrumb-expand-btn');

        items.forEach((item, index) => {
            item.addEventListener('keydown', function(e) {
                switch(e.key) {
                    case 'ArrowRight':
                        e.preventDefault();
                        focusNextBreadcrumbItem(items, index);
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        focusPrevBreadcrumbItem(items, index);
                        break;
                    case 'Home':
                        e.preventDefault();
                        items[0]?.focus();
                        break;
                    case 'End':
                        e.preventDefault();
                        items[items.length - 1]?.focus();
                        break;
                    case 'Enter':
                    case ' ':
                        if (this.classList.contains('breadcrumb-expand-btn')) {
                            e.preventDefault();
                            toggleBreadcrumbDropdown(this);
                        }
                        break;
                    case 'Escape':
                        if (this.classList.contains('breadcrumb-expand-btn')) {
                            closeBreadcrumbDropdown(this.nextElementSibling);
                        }
                        break;
                }
            });
        });
    });
}

function initializeProgressiveDisclosure() {
    // Show/hide breadcrumb actions based on available space
    if ('ResizeObserver' in window) {
        const breadcrumbContainers = document.querySelectorAll('.breadcrumb-container');

        breadcrumbContainers.forEach(container => {
            const observer = new ResizeObserver(entries => {
                entries.forEach(entry => {
                    const actions = entry.target.querySelector('.breadcrumb-actions');
                    if (actions) {
                        if (entry.contentRect.width < 600) {
                            actions.style.display = 'none';
                        } else {
                            actions.style.display = 'flex';
                        }
                    }
                });
            });

            observer.observe(container);
        });
    }
}

function updateBreadcrumbState(clickedLink) {
    // Remove active state from all breadcrumbs
    document.querySelectorAll('.breadcrumb-item').forEach(item => {
        item.classList.remove('active');
    });

    // Add active state to parent items
    let parent = clickedLink.closest('.breadcrumb-item');
    while (parent) {
        parent.classList.add('active');
        parent = parent.previousElementSibling;
    }

    // Update current page indicator
    const currentIndicator = document.querySelector('.breadcrumb-current');
    if (currentIndicator) {
        currentIndicator.textContent = clickedLink.textContent.trim();
    }
}

function toggleBreadcrumbDropdown(button) {
    const dropdown = button.nextElementSibling;
    if (dropdown) {
        const isVisible = dropdown.style.opacity === '1';
        if (isVisible) {
            closeBreadcrumbDropdown(dropdown);
        } else {
            openBreadcrumbDropdown(dropdown);
        }
    }
}

function openBreadcrumbDropdown(dropdown) {
    dropdown.style.opacity = '1';
    dropdown.style.visibility = 'visible';
    dropdown.setAttribute('aria-expanded', 'true');
}

function closeBreadcrumbDropdown(dropdown) {
    if (dropdown) {
        dropdown.style.opacity = '0';
        dropdown.style.visibility = 'hidden';
        dropdown.setAttribute('aria-expanded', 'false');
    }
}

function focusNextBreadcrumbItem(items, currentIndex) {
    const nextIndex = currentIndex + 1 < items.length ? currentIndex + 1 : 0;
    items[nextIndex]?.focus();
}

function focusPrevBreadcrumbItem(items, currentIndex) {
    const prevIndex = currentIndex - 1 >= 0 ? currentIndex - 1 : items.length - 1;
    items[prevIndex]?.focus();
}

// Global breadcrumb manager for drill-down functionality
window.BreadcrumbManager = {
    history: [],
    currentState: {},

    drillDown: function(data) {
        // Store current state
        this.history.push(this.currentState);

        // Update current state
        this.currentState = {
            ...this.currentState,
            ...data,
            timestamp: Date.now()
        };

        // Update URL if needed
        if (data.url) {
            // Use Laravel's URL manipulation or simple history API
            if (typeof window.history !== 'undefined') {
                window.history.pushState(this.currentState, '', data.url);
            }
        }

        // Trigger custom event for other components
        document.dispatchEvent(new CustomEvent('breadcrumb:drilldown', {
            detail: this.currentState
        }));
    },

    goBack: function() {
        if (this.history.length > 0) {
            this.currentState = this.history.pop();

            // Update URL
            if (typeof window.history !== 'undefined') {
                window.history.back();
            }

            // Trigger custom event
            document.dispatchEvent(new CustomEvent('breadcrumb:navigate', {
                detail: this.currentState
            }));
        }
    },

    updateBreadcrumbs: function(newBreadcrumbs) {
        const breadcrumbContainer = document.querySelector('.breadcrumb-container');
        if (!breadcrumbContainer) return;

        // Update the breadcrumb HTML dynamically
        const breadcrumbList = breadcrumbContainer.querySelector('.breadcrumb');
        if (breadcrumbList) {
            breadcrumbList.innerHTML = this.generateBreadcrumbHTML(newBreadcrumbs);
        }

        // Re-initialize event listeners
        this.initializeBreadcrumbEvents();
    },

    generateBreadcrumbHTML: function(breadcrumbs) {
        const totalBreadcrumbs = breadcrumbs.length;
        const shouldCollapse = totalBreadcrumbs > 3;
        const visibleBreadcrumbs = shouldCollapse ? breadcrumbs.slice(-3) : breadcrumbs;
        const hiddenCount = shouldCollapse ? totalBreadcrumbs - 3 : 0;

        let html = '';

        // Hidden breadcrumbs for screen readers
        if (shouldCollapse && hiddenCount > 0) {
            html += `<li class="sr-only" aria-live="polite">Showing last 3 of ${totalBreadcrumbs} breadcrumbs</li>`;
        }

        // Collapsed indicator
        if (shouldCollapse) {
            html += `
                <li class="breadcrumb-item breadcrumb-collapsed" aria-hidden="true">
                    <button type="button" class="breadcrumb-expand-btn" aria-label="Expand breadcrumb (${hiddenCount} more)">
                        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                        <span class="visually-hidden">Expand breadcrumb (${hiddenCount} more)</span>
                    </button>
                    <ol class="breadcrumb-dropdown" role="list">
                        ${breadcrumbs.slice(0, hiddenCount).map(crumb => `
                            <li class="breadcrumb-dropdown-item" role="listitem">
                                <a href="${crumb.url || '#'}" class="breadcrumb-dropdown-link ${crumb.active ? 'active' : ''}" ${crumb.active ? 'aria-current="page"' : ''}>
                                    <i class="${crumb.icon || 'fas fa-folder'}" aria-hidden="true"></i>
                                    ${crumb.title}
                                </a>
                            </li>
                        `).join('')}
                    </ol>
                </li>
            `;
        }

        // Visible breadcrumbs
        visibleBreadcrumbs.forEach((breadcrumb, index) => {
            const isLast = index === visibleBreadcrumbs.length - 1;
            const position = shouldCollapse ? totalBreadcrumbs - 3 + index + 1 : index + 1;

            html += `
                <li class="breadcrumb-item ${breadcrumb.active ? 'active' : ''}"
                    itemprop="itemListElement"
                    itemscope
                    itemtype="https://schema.org/ListItem">
                    ${breadcrumb.active ?
                        `<span class="breadcrumb-current" itemprop="name" aria-current="page">
                            ${breadcrumb.icon ? `<i class="${breadcrumb.icon}" aria-hidden="true"></i>` : ''}
                            ${breadcrumb.title}
                        </span>` :
                        `<a href="${breadcrumb.url || '#'}" class="breadcrumb-link" itemprop="item" data-drilldown='${JSON.stringify({
                            url: breadcrumb.url,
                            title: breadcrumb.title,
                            kpi: breadcrumb.kpi || '',
                            filters: breadcrumb.data ? breadcrumb.data['drilldown-filters'] : {}
                        })}' data-breadcrumb-update="true">
                            <span itemprop="name">
                                ${breadcrumb.icon ? `<i class="${breadcrumb.icon}" aria-hidden="true"></i>` : ''}
                                ${breadcrumb.title}
                            </span>
                        </a>`
                    }
                    <meta itemprop="position" content="${position}" />
                </li>
            `;
        });

        return html;
    },

    initializeBreadcrumbEvents: function() {
        // Re-bind expand/collapse functionality
        document.querySelectorAll('.breadcrumb-expand-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                toggleBreadcrumbDropdown(this);
            });
        });

        // Re-bind drill-down functionality
        document.querySelectorAll('.breadcrumb-link[data-breadcrumb-update]').forEach(link => {
            link.addEventListener('click', function(e) {
                const drilldownData = JSON.parse(this.dataset.drilldown || '{}');
                if (drilldownData.url && drilldownData.title) {
                    e.preventDefault();
                    window.location.href = drilldownData.url;
                }
            });
        });
    },

    updateContextualActions: function(actions) {
        const container = document.querySelector('.breadcrumb-actions .dropdown-menu');
        if (container) {
            container.innerHTML = '';

            actions.forEach(action => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.className = 'dropdown-item breadcrumb-action-item';
                link.href = action.url || '#';

                if (action.icon) {
                    const icon = document.createElement('i');
                    icon.className = action.icon;
                    link.appendChild(icon);
                }

                link.appendChild(document.createTextNode(action.title));

                if (action.onclick) {
                    link.addEventListener('click', action.onclick);
                }

                item.appendChild(link);
                container.appendChild(item);
            });
        }
    }
};
</script>
@endpush