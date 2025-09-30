{{-- Keyboard Shortcuts Help Overlay --}}
<div class="keyboard-shortcuts-overlay" id="shortcuts-help" style="display: none;" role="dialog" aria-labelledby="shortcuts-title" aria-modal="true">
    <div class="shortcuts-modal">
        <div class="shortcuts-header">
            <h3 id="shortcuts-title">
                <i class="fas fa-keyboard me-2"></i>
                Keyboard Shortcuts
            </h3>
            <button class="close-btn" aria-label="Close shortcuts help" onclick="window.keyboardShortcuts?.hideShortcutsHelp()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <input 
            type="search" 
            placeholder="Search shortcuts..." 
            class="shortcuts-search"
            aria-label="Search keyboard shortcuts"
            id="shortcuts-search-input"
        />
        
        <div class="shortcuts-body" id="shortcuts-list">
            <div class="shortcuts-section">
                <h4>Navigation</h4>
                <ul class="shortcuts-list">
                    <li>
                        <span class="shortcut-keys">
                            <kbd>G</kbd> <span class="then-text">then</span> <kbd>D</kbd>
                        </span>
                        <span class="shortcut-desc">Go to Dashboard</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>G</kbd> <span class="then-text">then</span> <kbd>P</kbd>
                        </span>
                        <span class="shortcut-desc">Go to Parcels</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>G</kbd> <span class="then-text">then</span> <kbd>M</kbd>
                        </span>
                        <span class="shortcut-desc">Go to Merchants</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>G</kbd> <span class="then-text">then</span> <kbd>H</kbd>
                        </span>
                        <span class="shortcut-desc">Go to Hubs</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>G</kbd> <span class="then-text">then</span> <kbd>T</kbd>
                        </span>
                        <span class="shortcut-desc">Go to Todo List</span>
                    </li>
                </ul>
            </div>
            
            <div class="shortcuts-section">
                <h4>Actions</h4>
                <ul class="shortcuts-list">
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>K</kbd>
                        </span>
                        <span class="shortcut-desc">Open Quick Actions</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>B</kbd>
                        </span>
                        <span class="shortcut-desc">Book New Shipment</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>S</kbd>
                        </span>
                        <span class="shortcut-desc">Save Current Form</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>F</kbd>
                        </span>
                        <span class="shortcut-desc">Focus Search</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Esc</kbd>
                        </span>
                        <span class="shortcut-desc">Close Modals/Dropdowns</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>?</kbd>
                        </span>
                        <span class="shortcut-desc">Show This Help</span>
                    </li>
                </ul>
            </div>
            
            <div class="shortcuts-section">
                <h4>Table & List Navigation</h4>
                <ul class="shortcuts-list">
                    <li>
                        <span class="shortcut-keys">
                            <kbd>J</kbd>
                        </span>
                        <span class="shortcut-desc">Navigate Down in Lists</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>K</kbd>
                        </span>
                        <span class="shortcut-desc">Navigate Up in Lists</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Enter</kbd>
                        </span>
                        <span class="shortcut-desc">Open Selected Item</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>A</kbd>
                        </span>
                        <span class="shortcut-desc">Select All (in bulk operations)</span>
                    </li>
                    <li>
                        <span class="shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>D</kbd>
                        </span>
                        <span class="shortcut-desc">Deselect All</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="shortcuts-footer">
            <p class="text-muted mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                Press <kbd>?</kbd> anytime to see this help
            </p>
        </div>
    </div>
</div>

{{-- Search Functionality Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('shortcuts-search-input');
    const shortcutsList = document.getElementById('shortcuts-list');
    
    if (searchInput && shortcutsList) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const sections = shortcutsList.querySelectorAll('.shortcuts-section');
            
            sections.forEach(section => {
                const items = section.querySelectorAll('li');
                let visibleCount = 0;
                
                items.forEach(item => {
                    const desc = item.querySelector('.shortcut-desc').textContent.toLowerCase();
                    const keys = item.querySelector('.shortcut-keys').textContent.toLowerCase();
                    
                    if (query === '' || desc.includes(query) || keys.includes(query)) {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Hide section if no visible items
                section.style.display = visibleCount > 0 ? '' : 'none';
            });
        });
    }
});
</script>