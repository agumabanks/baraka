/**
 * Premium Settings Module - Enhanced JavaScript
 * Apple-level refinements with sophisticated interactions and performance optimization
 */

class SettingsPremiumEnhancements {
    constructor() {
        this.originalData = new Map();
        this.hasUnsavedChanges = false;
        this.isSaving = false;
        this.autoSaveInterval = null;
        this.validationRules = new Map();
        this.livePreviews = new Map();
        this.performanceObserver = null;
        this.accessibilityObserver = null;
        this.observerCallbacks = new Map();
        
        this.config = {
            autoSaveDelay: 30000, // 30 seconds
            debounceDelay: 300,
            animationDuration: 300,
            focusDelay: 100,
            scrollOffset: 20
        };
        
        this.init();
    }

    init() {
        this.setupPerformanceOptimizations();
        this.setupAccessibilityEnhancements();
        this.setupMicroInteractions();
        this.setupEventListeners();
        this.initializeComponents();
        this.setupObservers();
        this.loadDraftData();
        
        // Enhanced initialization with proper timing
        setTimeout(() => {
            this.captureOriginalData();
            this.setupChangeTracking();
            this.initializePreviews();
            this.setupValidation();
            this.startAutoSave();
        }, this.config.focusDelay);
    }

    /**
     * Performance Optimizations
     */
    setupPerformanceOptimizations() {
        // Debounced event handlers for better performance
        this.debouncedHandlers = new Map();
        this.throttledHandlers = new Map();
        
        // Lazy load non-critical components
        this.lazyLoadComponents();
        
        // Optimize animations for better performance
        this.optimizeAnimations();
        
        // Setup intersection observer for scroll-based animations
        this.setupScrollAnimations();
    }

    /**
     * Enhanced Accessibility Features
     */
    setupAccessibilityEnhancements() {
        // Enhanced keyboard navigation
        this.setupKeyboardNavigation();
        
        // Screen reader announcements
        this.announcements = new Map();
        this.setupAriaLiveRegion();
        
        // Focus management
        this.setupFocusManagement();
        
        // High contrast mode detection
        this.detectHighContrastMode();
        
        // Reduced motion preferences
        this.respectReducedMotion();
    }

    /**
     * Micro-interactions and Animations
     */
    setupMicroInteractions() {
        // Button press animations
        this.setupButtonAnimations();
        
        // Form field interactions
        this.setupFieldInteractions();
        
        // Navigation transitions
        this.setupNavigationTransitions();
        
        // Loading states
        this.setupLoadingAnimations();
        
        // Success/Error feedback
        this.setupFeedbackAnimations();
    }

    /**
     * Enhanced Event Listeners with Performance
     */
    setupEventListeners() {
        // Use event delegation for better performance
        this.setupEventDelegation();
        
        // Window events with debouncing
        this.setupWindowEvents();
        
        // Form events
        this.setupFormEvents();
        
        // Component events
        this.setupComponentEvents();
    }

    /**
     * Initialize all components
     */
    initializeComponents() {
        this.initializeToggles();
        this.initializeColorPickers();
        this.initializeFileUploads();
        this.initializeTabs();
        this.initializeCards();
        this.initializeTooltips();
    }

    /**
     * Setup Observers for performance monitoring
     */
    setupObservers() {
        // Performance monitoring
        if ('PerformanceObserver' in window) {
            this.performanceObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'measure') {
                        this.logPerformance(entry);
                    }
                }
            });
            this.performanceObserver.observe({ entryTypes: ['measure', 'navigation'] });
        }

        // Accessibility monitoring
        if ('MutationObserver' in window) {
            this.accessibilityObserver = new MutationObserver((mutations) => {
                this.checkAccessibilityChanges(mutations);
            });
            this.accessibilityObserver.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['aria-label', 'aria-describedby', 'role']
            });
        }
    }

    /**
     * Event Delegation for Performance
     */
    setupEventDelegation() {
        const debouncedInputHandler = this.debounce(this.handleInputChange.bind(this), this.config.debounceDelay);
        const throttledScrollHandler = this.throttle(this.handleScroll.bind(this), 16); // ~60fps
        
        document.addEventListener('input', debouncedInputHandler, { passive: true });
        document.addEventListener('change', debouncedInputHandler, { passive: true });
        document.addEventListener('scroll', throttledScrollHandler, { passive: true });
        
        // Form submission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('premium-settings-form')) {
                this.handleFormSubmit(e);
            }
        });
        
        // File uploads
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.handleFileUpload(e.target);
            }
        });
    }

    /**
     * Enhanced Input Change Handling
     */
    handleInputChange(e) {
        const field = e.target;
        if (!this.shouldProcessField(field)) return;
        
        const value = this.getFieldValue(field);
        const fieldName = field.name;
        
        // Performance tracking
        performance.mark('input-change-start');
        
        // Check for changes
        const hasChanged = this.hasFieldChanged(fieldName, value);
        
        if (hasChanged) {
            this.markFieldAsChanged(field);
            this.hasUnsavedChanges = true;
            this.updateSaveButtonState();
            this.showUnsavedChangesIndicator();
            
            // Enhanced visual feedback
            this.addFieldChangeAnimation(field);
        } else {
            this.markFieldAsUnchanged(field);
            this.updateUnsavedChangesState();
        }
        
        // Live previews with throttling
        this.updateLivePreviews(fieldName, value);
        
        // Real-time validation
        this.validateField(field);
        
        // Accessibility announcement
        this.announceFieldChange(field, hasChanged);
        
        performance.mark('input-change-end');
        performance.measure('input-change', 'input-change-start', 'input-change-end');
    }

    /**
     * Enhanced Form Submission
     */
    async handleFormSubmit(e) {
        e.preventDefault();
        
        if (this.isSaving) return;
        
        this.isSaving = true;
        this.updateSaveStatus('saving');
        this.updateSaveButtonState();
        
        performance.mark('form-submit-start');
        
        // Validate all fields
        const isValid = await this.validateAllFieldsAsync();
        if (!isValid) {
            this.handleValidationErrors();
            this.isSaving = false;
            this.updateSaveStatus('error');
            return;
        }
        
        // Show enhanced loading state
        this.showEnhancedLoadingState();
        
        try {
            const formData = new FormData(e.target);
            const response = await this.submitForm(e.target.action, formData);
            
            if (response.success) {
                await this.handleSuccessfulSubmission(response);
            } else {
                this.handleSubmissionError(response);
            }
        } catch (error) {
            this.handleSubmissionException(error);
        } finally {
            this.isSaving = false;
            this.hideEnhancedLoadingState();
            this.updateSaveButtonState();
            
            performance.mark('form-submit-end');
            performance.measure('form-submit', 'form-submit-start', 'form-submit-end');
        }
    }

    /**
     * Initialize Enhanced Toggles
     */
    initializeToggles() {
        const toggles = document.querySelectorAll('.enhanced-toggle-container');
        
        toggles.forEach(toggle => {
            const input = toggle.querySelector('.enhanced-toggle-input');
            const label = toggle.querySelector('.enhanced-toggle-label');
            
            // Enhanced toggle interactions
            label.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    input.click();
                }
            });
            
            // Smooth animation
            input.addEventListener('change', () => {
                this.animateToggle(toggle, input.checked);
            });
            
            // Accessibility enhancement
            input.setAttribute('aria-describedby', toggle.id + '-description');
        });
    }

    /**
     * Initialize Enhanced Color Pickers
     */
    initializeColorPickers() {
        const colorPickers = document.querySelectorAll('.enhanced-color-picker-container');
        
        colorPickers.forEach(picker => {
            this.setupColorPickerInteractions(picker);
        });
    }

    setupColorPickerInteractions(picker) {
        const nativePicker = picker.querySelector('input[type="color"]');
        const hexInput = picker.querySelector('.color-input-hex');
        const preview = picker.querySelector('.color-preview-circle');
        const randomBtn = picker.querySelector('.color-random-btn');
        const presets = picker.querySelectorAll('.preset-color');
        
        // Debounced color update
        const debouncedColorUpdate = this.debounce((color) => {
            this.updateColorPreview(preview, color);
            this.updateColorInputs(nativePicker, hexInput, color);
            this.applyColorToTheme(picker, color);
        }, 100);
        
        // Native picker
        nativePicker.addEventListener('input', (e) => {
            debouncedColorUpdate(e.target.value);
        });
        
        // Hex input
        hexInput.addEventListener('input', (e) => {
            if (this.isValidHex(e.target.value)) {
                debouncedColorUpdate(e.target.value);
            }
        });
        
        // Random color
        randomBtn.addEventListener('click', () => {
            const randomColor = this.generateRandomColor();
            debouncedColorUpdate(randomColor);
        });
        
        // Presets
        presets.forEach(preset => {
            preset.addEventListener('click', () => {
                const color = preset.dataset.color;
                debouncedColorUpdate(color);
                this.setActivePreset(presets, preset);
            });
        });
        
        // Initial setup
        this.updateColorPreview(preview, nativePicker.value);
    }

    /**
     * Enhanced File Upload Handling
     */
    handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;
        
        const container = input.closest('.enhanced-upload-container');
        const preview = container.querySelector('.upload-preview');
        
        // Validate file
        if (!this.validateFile(file)) {
            this.showUploadError(container, 'Invalid file type or size');
            return;
        }
        
        // Show progress
        this.showUploadProgress(container);
        
        // Simulate upload with realistic progress
        this.simulateUpload(container, file);
    }

    /**
     * Enhanced Loading States
     */
    showEnhancedLoadingState() {
        const modal = this.createLoadingModal();
        document.body.appendChild(modal);
        
        // Animate modal in
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
        
        this.loadingModal = modal;
    }

    hideEnhancedLoadingState() {
        if (this.loadingModal) {
            this.loadingModal.classList.remove('show');
            setTimeout(() => {
                this.loadingModal.remove();
                this.loadingModal = null;
            }, 300);
        }
    }

    createLoadingModal() {
        const modal = document.createElement('div');
        modal.className = 'premium-loading-modal';
        modal.innerHTML = `
            <div class="loading-modal-backdrop"></div>
            <div class="loading-modal-content">
                <div class="loading-spinner-premium">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                <h3 class="loading-title">Saving Settings</h3>
                <p class="loading-description">Please wait while we save your changes</p>
                <div class="loading-progress">
                    <div class="progress-bar"></div>
                </div>
            </div>
        `;
        
        return modal;
    }

    /**
     * Accessibility Enhancements
     */
    setupAriaLiveRegion() {
        this.ariaLiveRegion = document.createElement('div');
        this.ariaLiveRegion.setAttribute('aria-live', 'polite');
        this.ariaLiveRegion.setAttribute('aria-atomic', 'true');
        this.ariaLiveRegion.className = 'sr-only';
        this.ariaLiveRegion.id = 'premium-aria-live';
        document.body.appendChild(this.ariaLiveRegion);
    }

    announce(message, priority = 'polite') {
        this.ariaLiveRegion.setAttribute('aria-live', priority);
        this.ariaLiveRegion.textContent = message;
        
        // Clear after announcement
        setTimeout(() => {
            this.ariaLiveRegion.textContent = '';
        }, 1000);
    }

    announceFieldChange(field, hasChanged) {
        const label = field.closest('.form-group, .premium-form-group')?.querySelector('label')?.textContent || field.name;
        const message = hasChanged ? `${label} has been changed` : `${label} is now ${field.value}`;
        this.announce(message);
    }

    /**
     * Enhanced Keyboard Navigation
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
    }

    handleKeyboardNavigation(e) {
        const activeElement = document.activeElement;
        
        // Enhanced tab navigation
        if (e.key === 'Tab') {
            this.enhanceTabNavigation(activeElement);
        }
        
        // Escape key handling
        if (e.key === 'Escape') {
            this.handleEscapeKey();
        }
        
        // Arrow key navigation for certain components
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
            this.handleArrowKeyNavigation(e, activeElement);
        }
    }

    /**
     * Enhanced Animations
     */
    addFieldChangeAnimation(field) {
        const container = field.closest('.form-group, .premium-form-group');
        if (!container) return;
        
        container.classList.add('field-changed-animation');
        
        // Add ripple effect
        this.addRippleEffect(container, field);
        
        setTimeout(() => {
            container.classList.remove('field-changed-animation');
        }, 600);
    }

    addRippleEffect(element, trigger) {
        const ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = trigger.clientX - rect.left - size / 2;
        const y = trigger.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(0, 122, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    /**
     * Performance Monitoring
     */
    logPerformance(entry) {
        if (entry.duration > 100) {
            console.warn(`Performance issue detected: ${entry.name} took ${entry.duration}ms`);
        }
    }

    /**
     * Responsive Enhancements
     */
    setupResponsiveFeatures() {
        // Touch interactions for mobile
        this.setupTouchInteractions();
        
        // Responsive navigation
        this.setupResponsiveNavigation();
        
        // Responsive typography
        this.setupResponsiveTypography();
    }

    /**
     * Auto-save functionality
     */
    startAutoSave() {
        this.autoSaveInterval = setInterval(() => {
            if (this.hasUnsavedChanges && !this.isSaving) {
                this.autoSaveDraft();
            }
        }, this.config.autoSaveDelay);
    }

    async autoSaveDraft() {
        if (!this.hasUnsavedChanges) return;
        
        try {
            const draftData = this.collectDraftData();
            await this.saveDraft(draftData);
            this.showAutoSaveNotification();
        } catch (error) {
            console.warn('Auto-save failed:', error);
        }
    }

    /**
     * Enhanced Validation
     */
    async validateAllFieldsAsync() {
        const fields = document.querySelectorAll('.premium-form-input, .form-control');
        const validationPromises = Array.from(fields).map(field => this.validateFieldAsync(field));
        
        const results = await Promise.all(validationPromises);
        return results.every(result => result.isValid);
    }

    async validateFieldAsync(field) {
        const rules = this.validationRules.get(field.name);
        if (!rules) return { isValid: true, errors: [] };
        
        const errors = [];
        const value = this.getFieldValue(field);
        
        // Async validation if needed
        if (rules.async) {
            try {
                const result = await rules.async(value);
                if (!result.valid) {
                    errors.push(result.message);
                }
            } catch (error) {
                errors.push('Validation failed');
            }
        }
        
        // Client-side validation
        if (rules.required && (!value || value.toString().trim() === '')) {
            errors.push(rules.messages.required);
        }
        
        if (rules.pattern && value && !rules.pattern.test(value)) {
            errors.push(rules.messages.pattern);
        }
        
        this.showFieldValidation(field, errors.length === 0, errors);
        
        return { isValid: errors.length === 0, errors };
    }

    /**
     * Utility Functions
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    isValidHex(color) {
        return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
    }

    generateRandomColor() {
        return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
    }

    shouldProcessField(field) {
        return field && 
               field.name && 
               !field.disabled && 
               !field.readOnly &&
               !field.closest('.enhanced-toggle-container')?.classList.contains('disabled');
    }

    getFieldValue(field) {
        if (field.type === 'checkbox') {
            return field.checked;
        }
        if (field.type === 'file') {
            return field.files.length > 0 ? field.files[0] : null;
        }
        return field.value;
    }

    hasFieldChanged(fieldName, currentValue) {
        const originalValue = this.originalData.get(fieldName);
        
        if (currentValue === null || currentValue === undefined) {
            return originalValue !== null && originalValue !== undefined;
        }
        
        if (typeof currentValue === 'object') {
            return currentValue.name !== originalValue;
        }
        
        return currentValue !== originalValue;
    }

    /**
     * Cleanup
     */
    destroy() {
        // Clear intervals
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
        
        // Disconnect observers
        if (this.performanceObserver) {
            this.performanceObserver.disconnect();
        }
        
        if (this.accessibilityObserver) {
            this.accessibilityObserver.disconnect();
        }
        
        // Remove event listeners
        // (In a real implementation, you'd keep references to the bound functions)
    }
}

// CSS for premium loading modal and animations
const premiumStyles = `
<style>
.premium-loading-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.premium-loading-modal.show {
    opacity: 1;
    visibility: visible;
}

.loading-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
}

.loading-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--background-primary);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    text-align: center;
    box-shadow: var(--shadow-2xl);
    border: 1px solid var(--border-color);
    min-width: 300px;
}

.loading-spinner-premium {
    position: relative;
    width: 60px;
    height: 60px;
    margin: 0 auto var(--spacing-6);
}

.spinner-ring {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border: 3px solid transparent;
    border-radius: 50%;
    animation: premiumSpin 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

.spinner-ring:nth-child(1) {
    border-top-color: var(--primary-color);
    animation-delay: 0s;
}

.spinner-ring:nth-child(2) {
    border-top-color: var(--info-color);
    animation-delay: -0.5s;
}

.spinner-ring:nth-child(3) {
    border-top-color: var(--success-color);
    animation-delay: -1s;
}

@keyframes premiumSpin {
    0% {
        transform: rotate(0deg) scale(1);
    }
    50% {
        transform: rotate(180deg) scale(1.1);
    }
    100% {
        transform: rotate(360deg) scale(1);
    }
}

.loading-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 var(--spacing-2) 0;
}

.loading-description {
    color: var(--text-secondary);
    margin: 0 0 var(--spacing-6) 0;
}

.loading-progress {
    height: 4px;
    background: var(--background-secondary);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.loading-progress .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--success-color));
    border-radius: var(--radius-full);
    animation: progressShimmer 2s ease-in-out infinite;
}

@keyframes progressShimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.field-changed-animation {
    animation: fieldChange 0.6s ease-out;
}

@keyframes fieldChange {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes ripple {
    to {
        transform: scale(2);
        opacity: 0;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .loading-modal-content {
        margin: var(--spacing-4);
        padding: var(--spacing-6);
        min-width: auto;
        width: calc(100% - 2rem);
    }
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', premiumStyles);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.premium-settings-form')) {
        window.premiumSettings = new SettingsPremiumEnhancements();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SettingsPremiumEnhancements;
}