@props([
    'name',
    'label',
    'checked' => false,
    'help' => '',
    'icon' => '',
    'disabled' => false,
    'size' => 'normal'
])

<div class="enhanced-toggle-container" data-size="{{ $size }}">
    <div class="enhanced-toggle-wrapper {{ $disabled ? 'disabled' : '' }}">
        <!-- Toggle Input -->
        <input type="checkbox" 
               id="{{ $name }}" 
               name="{{ $name }}" 
               value="1"
               {{ $checked ? 'checked' : '' }}
               {{ $disabled ? 'disabled' : '' }}
               class="enhanced-toggle-input">
        
        <!-- Toggle Label -->
        <label class="enhanced-toggle-label" for="{{ $name }}">
            <div class="toggle-track">
                <!-- Track Background -->
                <div class="toggle-track-bg"></div>
                
                <!-- Thumb -->
                <div class="toggle-thumb">
                    <div class="thumb-icon thumb-on-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="thumb-icon thumb-off-icon">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                
                <!-- Text Labels -->
                <div class="toggle-text">
                    <span class="toggle-on-text">On</span>
                    <span class="toggle-off-text">Off</span>
                </div>
                
                <!-- Status Icons -->
                <div class="toggle-status-icons">
                    <div class="status-icon status-active">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="status-icon status-inactive">
                        <i class="fas fa-circle"></i>
                    </div>
                </div>
            </div>
        </label>
        
        <!-- Label Text -->
        <div class="toggle-content">
            <div class="toggle-main-content">
                @if($icon)
                    <i class="{{ $icon }} toggle-icon"></i>
                @endif
                <span class="toggle-label-text">{{ $label }}</span>
            </div>
            
            @if($help)
                <div class="toggle-help-text">{{ $help }}</div>
            @endif
        </div>
    </div>
    
    <!-- Toggle State Indicator -->
    <div class="toggle-state-indicator">
        <div class="state-indicator-dot"></div>
        <span class="state-indicator-text">{{ $checked ? 'Enabled' : 'Disabled' }}</span>
    </div>
</div>

<style>
/* Enhanced Toggle Styles */
.enhanced-toggle-container {
    margin-bottom: var(--spacing-6);
}

.enhanced-toggle-wrapper {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    background: var(--background-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    transition: all var(--transition-base);
    position: relative;
}

.enhanced-toggle-wrapper:hover {
    border-color: var(--border-color-hover);
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.enhanced-toggle-wrapper.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.enhanced-toggle-wrapper.disabled .enhanced-toggle-label {
    cursor: not-allowed;
}

/* Toggle Input (Hidden) */
.enhanced-toggle-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Toggle Label and Track */
.enhanced-toggle-label {
    flex-shrink: 0;
    cursor: pointer;
    position: relative;
}

.toggle-track {
    position: relative;
    width: 64px;
    height: 32px;
    background: var(--color-gray-300);
    border-radius: var(--radius-full);
    transition: all var(--transition-base);
    overflow: hidden;
}

.toggle-track-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, var(--color-gray-300), var(--color-gray-400));
    transition: all var(--transition-base);
}

/* Toggle Thumb */
.toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 28px;
    height: 28px;
    background: white;
    border-radius: var(--radius-full);
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: center;
}

.thumb-icon {
    position: absolute;
    font-size: 10px;
    transition: all var(--transition-base);
}

.thumb-on-icon {
    opacity: 0;
    color: var(--success-color);
}

.thumb-off-icon {
    opacity: 1;
    color: var(--color-gray-400);
}

/* Toggle Text */
.toggle-text {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 8px;
    pointer-events: none;
}

.toggle-on-text,
.toggle-off-text {
    font-size: 10px;
    font-weight: 600;
    color: white;
    opacity: 0;
    transition: opacity var(--transition-base);
}

/* Status Icons */
.toggle-status-icons {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 8px;
    pointer-events: none;
}

.status-icon {
    font-size: 4px;
    transition: all var(--transition-base);
}

.status-active {
    color: var(--success-color);
    opacity: 0;
}

.status-inactive {
    color: var(--color-gray-400);
    opacity: 1;
}

/* Toggle Content */
.toggle-content {
    flex: 1;
    min-width: 0;
}

.toggle-main-content {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-1);
}

.toggle-icon {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    transition: color var(--transition-base);
}

.toggle-label-text {
    font-weight: 600;
    font-size: var(--font-size-sm);
    color: var(--text-primary);
    line-height: 1.4;
}

.toggle-help-text {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    line-height: 1.4;
    margin-top: var(--spacing-1);
}

/* Toggle State Indicator */
.toggle-state-indicator {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-top: var(--spacing-3);
    margin-left: var(--spacing-4);
    opacity: 0;
    transition: all var(--transition-base);
}

.state-indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--color-gray-400);
    transition: all var(--transition-base);
}

.state-indicator-text {
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
    font-weight: 500;
}

/* Toggle States */
.enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-track {
    background: linear-gradient(90deg, var(--success-color), #28a745);
    box-shadow: inset 0 0 0 1px rgba(52, 199, 89, 0.1);
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-thumb {
    transform: translateX(32px);
    box-shadow: var(--shadow-md);
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .thumb-on-icon {
    opacity: 1;
    color: white;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .thumb-off-icon {
    opacity: 0;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-on-text {
    opacity: 1;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-off-text {
    opacity: 0;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .status-active {
    opacity: 1;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .status-inactive {
    opacity: 0;
}

.enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-track-bg {
    background: linear-gradient(90deg, var(--success-color), #28a745);
}

.enhanced-toggle-input:checked ~ .toggle-state-indicator {
    opacity: 1;
}

.enhanced-toggle-input:checked ~ .toggle-state-indicator .state-indicator-dot {
    background: var(--success-color);
    box-shadow: 0 0 8px rgba(52, 199, 89, 0.4);
}

.enhanced-toggle-input:checked ~ .toggle-state-indicator .state-indicator-text {
    color: var(--success-color);
}

/* Focus States */
.enhanced-toggle-input:focus + .enhanced-toggle-label .toggle-track {
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1), var(--shadow-sm);
}

.enhanced-toggle-input:focus:checked + .enhanced-toggle-label .toggle-track {
    box-shadow: 0 0 0 3px rgba(52, 199, 89, 0.2), var(--shadow-sm);
}

/* Hover Effects */
.enhanced-toggle-label:hover .toggle-track {
    box-shadow: var(--shadow-sm);
}

.enhanced-toggle-label:hover .toggle-thumb {
    box-shadow: var(--shadow-md);
}

/* Active States */
.enhanced-toggle-label:active .toggle-thumb {
    transform: scale(0.95);
}

.enhanced-toggle-input:checked + .enhanced-toggle-label:active .toggle-thumb {
    transform: translateX(32px) scale(0.95);
}

/* Size Variations */
.enhanced-toggle-container[data-size="small"] .toggle-track {
    width: 48px;
    height: 24px;
}

.enhanced-toggle-container[data-size="small"] .toggle-thumb {
    width: 20px;
    height: 20px;
    top: 2px;
    left: 2px;
}

.enhanced-toggle-container[data-size="small"] .enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-thumb {
    transform: translateX(24px);
}

.enhanced-toggle-container[data-size="small"] .toggle-on-text,
.enhanced-toggle-container[data-size="small"] .toggle-off-text {
    display: none;
}

.enhanced-toggle-container[data-size="large"] .toggle-track {
    width: 80px;
    height: 40px;
}

.enhanced-toggle-container[data-size="large"] .toggle-thumb {
    width: 36px;
    height: 36px;
    top: 2px;
    left: 2px;
}

.enhanced-toggle-container[data-size="large"] .enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-thumb {
    transform: translateX(40px);
}

.enhanced-toggle-container[data-size="large"] .toggle-label-text {
    font-size: var(--font-size-base);
}

.enhanced-toggle-container[data-size="large"] .toggle-help-text {
    font-size: var(--font-size-sm);
}

/* Animation Keyframes */
@keyframes togglePulse {
    0% {
        transform: scale(1);
        box-shadow: var(--shadow-sm);
    }
    50% {
        transform: scale(1.02);
        box-shadow: var(--shadow-md);
    }
    100% {
        transform: scale(1);
        box-shadow: var(--shadow-sm);
    }
}

.toggle-pulse-animation {
    animation: togglePulse 0.3s ease-in-out;
}

@keyframes toggleSuccess {
    0% {
        transform: translateY(0);
    }
    25% {
        transform: translateY(-2px);
    }
    50% {
        transform: translateY(0);
    }
    75% {
        transform: translateY(-1px);
    }
    100% {
        transform: translateY(0);
    }
}

.toggle-success-animation {
    animation: toggleSuccess 0.5s ease-in-out;
}

/* Responsive Design */
@media (max-width: 768px) {
    .enhanced-toggle-wrapper {
        flex-direction: column;
        align-items: stretch;
        gap: var(--spacing-3);
    }
    
    .toggle-state-indicator {
        margin-left: 0;
        align-self: flex-start;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .toggle-track {
        border: 2px solid var(--color-black);
    }
    
    .toggle-thumb {
        border: 2px solid var(--color-black);
    }
    
    .enhanced-toggle-input:checked + .enhanced-toggle-label .toggle-track {
        border: 2px solid var(--success-color);
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .toggle-track,
    .toggle-thumb,
    .thumb-icon,
    .toggle-text,
    .status-icon,
    .state-indicator-dot,
    .enhanced-toggle-wrapper,
    .toggle-state-indicator {
        transition: none !important;
    }
    
    .toggle-pulse-animation,
    .toggle-success-animation {
        animation: none !important;
    }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
    .toggle-thumb {
        background: var(--color-gray-800);
        border: 1px solid var(--color-gray-700);
    }
    
    .thumb-off-icon {
        color: var(--color-gray-500);
    }
}

/* Print Styles */
@media print {
    .enhanced-toggle-container {
        break-inside: avoid;
    }
    
    .toggle-state-indicator {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.enhanced-toggle-container');
    
    toggles.forEach(toggle => {
        const input = toggle.querySelector('.enhanced-toggle-input');
        const wrapper = toggle.querySelector('.enhanced-toggle-wrapper');
        const label = toggle.querySelector('.enhanced-toggle-label');
        const track = toggle.querySelector('.toggle-track');
        const thumb = toggle.querySelector('.toggle-thumb');
        
        // Add event listeners
        input.addEventListener('change', function() {
            handleToggleChange(toggle, this.checked);
        });
        
        // Add click animation
        label.addEventListener('click', function() {
            if (!wrapper.classList.contains('disabled')) {
                addClickAnimation(toggle);
            }
        });
        
        // Initialize state
        updateToggleState(toggle, input.checked);
    });
    
    function handleToggleChange(toggle, isChecked) {
        updateToggleState(toggle, isChecked);
        addStateChangeAnimation(toggle, isChecked);
        
        // Dispatch custom event
        const event = new CustomEvent('toggleChange', {
            detail: {
                name: toggle.querySelector('.enhanced-toggle-input').name,
                checked: isChecked
            }
        });
        toggle.dispatchEvent(event);
    }
    
    function updateToggleState(toggle, isChecked) {
        const indicatorDot = toggle.querySelector('.state-indicator-dot');
        const indicatorText = toggle.querySelector('.state-indicator-text');
        
        if (isChecked) {
            indicatorText.textContent = 'Enabled';
            indicatorDot.style.backgroundColor = 'var(--success-color)';
        } else {
            indicatorText.textContent = 'Disabled';
            indicatorDot.style.backgroundColor = 'var(--color-gray-400)';
        }
    }
    
    function addClickAnimation(toggle) {
        const track = toggle.querySelector('.toggle-track');
        track.classList.add('toggle-pulse-animation');
        
        setTimeout(() => {
            track.classList.remove('toggle-pulse-animation');
        }, 300);
    }
    
    function addStateChangeAnimation(toggle, isChecked) {
        const wrapper = toggle.querySelector('.enhanced-toggle-wrapper');
        
        if (isChecked) {
            wrapper.classList.add('toggle-success-animation');
            setTimeout(() => {
                wrapper.classList.remove('toggle-success-animation');
            }, 500);
        }
    }
    
    // Keyboard navigation support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            // Ensure proper tab order for toggle inputs
            const focusedElement = document.activeElement;
            if (focusedElement && focusedElement.classList.contains('enhanced-toggle-input')) {
                const toggle = focusedElement.closest('.enhanced-toggle-container');
                if (toggle) {
                    toggle.classList.add('keyboard-focused');
                }
            }
        }
    });
    
    document.addEventListener('keyup', function(e) {
        if (e.key === 'Tab') {
            // Remove keyboard focus class
            document.querySelectorAll('.enhanced-toggle-container.keyboard-focused').forEach(toggle => {
                toggle.classList.remove('keyboard-focused');
            });
        }
    });
});
</script>