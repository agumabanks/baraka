/**
 * Settings Form UX Enhancements
 * Comprehensive JavaScript for advanced UX behavior and validation
 * Compatible with existing GeneralSettingsController and SPA
 */

class SettingsUXEnhancements {
    constructor() {
        this.originalData = {};
        this.hasUnsavedChanges = false;
        this.isSaving = false;
        this.validationRules = {};
        this.autoSaveDrafts = new Map();
        this.previewElements = {};
        
        this.init();
    }

    init() {
        this.captureOriginalData();
        this.setupEventListeners();
        this.initializePreviews();
        this.setupValidation();
        this.setupUnsavedChangesTracking();
        this.setupAutoSave();
        this.setupConfirmationDialogs();
        this.setupProgressIndicators();
    }

    /**
     * Capture original form data for change tracking
     */
    captureOriginalData() {
        const form = document.getElementById('settingsForm');
        if (!form) return;

        const formData = new FormData(form);
        for (let [key, value] of formData.entries()) {
            this.originalData[key] = value;
        }

        // Also capture file inputs
        const fileInputs = form.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                this.originalData[input.name] = input.files[0].name;
            } else {
                this.originalData[input.name] = null;
            }
        });
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Form input change tracking
        document.addEventListener('input', (e) => this.handleInputChange(e));
        document.addEventListener('change', (e) => this.handleInputChange(e));

        // Form submission
        const form = document.getElementById('settingsForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // File upload handling
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.handleFileUpload(e.target);
            }
        });

        // Color picker live updates
        document.addEventListener('input', (e) => {
            if (e.target.type === 'color' || e.target.classList.contains('color-picker-container')) {
                this.handleColorChange(e.target);
            }
        });

        // Navigation away detection
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Tab switching - clear unsaved indicator
        document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('show.bs.tab', () => {
                this.clearUnsavedChangesIndicator();
            });
        });
    }

    /**
     * Handle input changes for tracking and live previews
     */
    handleInputChange(e) {
        const field = e.target;
        const value = this.getFieldValue(field);

        // Check if value changed from original
        const hasChanged = this.hasFieldChanged(field.name, value);
        
        if (hasChanged) {
            this.markFieldAsChanged(field);
            this.hasUnsavedChanges = true;
            this.updateSaveButtonState();
            this.showUnsavedChangesIndicator();
        } else {
            this.markFieldAsUnchanged(field);
            this.updateUnsavedChangesState();
        }

        // Trigger live previews
        this.updateLivePreviews(field.name, value);
        
        // Real-time validation
        this.validateField(field);
    }

    /**
     * Get field value considering different input types
     */
    getFieldValue(field) {
        if (field.type === 'checkbox') {
            return field.checked;
        }
        if (field.type === 'file') {
            return field.files.length > 0 ? field.files[0] : null;
        }
        return field.value;
    }

    /**
     * Check if field has changed from original value
     */
    hasFieldChanged(fieldName, currentValue) {
        const originalValue = this.originalData[fieldName];
        
        if (currentValue === null || currentValue === undefined) {
            return originalValue !== null && originalValue !== undefined;
        }
        
        if (typeof currentValue === 'object') {
            return currentValue.name !== originalValue;
        }
        
        return currentValue !== originalValue;
    }

    /**
     * Mark field as changed visually
     */
    markFieldAsChanged(field) {
        field.classList.add('changed-field');
        
        // Add validation success state
        if (this.isFieldValid(field)) {
            field.classList.add('is-valid');
        }
        
        // Update field container
        const container = field.closest('.mb-4, .form-group');
        if (container) {
            container.classList.add('field-changed');
        }
    }

    /**
     * Mark field as unchanged visually
     */
    markFieldAsUnchanged(field) {
        field.classList.remove('changed-field', 'is-valid');
        
        // Update field container
        const container = field.closest('.mb-4, .form-group');
        if (container) {
            container.classList.remove('field-changed');
        }
    }

    /**
     * Update save button state based on changes
     */
    updateSaveButtonState() {
        const saveButton = document.querySelector('button[type="submit"], .save-button');
        if (saveButton) {
            if (this.hasUnsavedChanges && !this.isSaving) {
                saveButton.disabled = false;
                saveButton.classList.remove('btn-secondary');
                saveButton.classList.add('btn-primary');
                
                // Add indicator
                this.addSaveButtonIndicator(saveButton);
            } else if (this.isSaving) {
                saveButton.disabled = true;
                saveButton.classList.remove('btn-primary');
                saveButton.classList.add('btn-warning');
                
                // Add loading indicator
                this.addLoadingIndicator(saveButton);
            } else {
                saveButton.disabled = true;
                saveButton.classList.remove('btn-primary', 'btn-warning');
                saveButton.classList.add('btn-secondary');
                
                // Remove indicators
                this.removeSaveButtonIndicators(saveButton);
            }
        }
    }

    /**
     * Add save button indicator for unsaved changes
     */
    addSaveButtonIndicator(button) {
        this.removeSaveButtonIndicators(button);
        
        const indicator = document.createElement('span');
        indicator.className = 'save-indicator ms-2';
        indicator.innerHTML = '<i class="fas fa-circle text-warning" style="font-size: 0.5rem;"></i>';
        button.appendChild(indicator);
    }

    /**
     * Add loading indicator during save
     */
    addLoadingIndicator(button) {
        this.removeSaveButtonIndicators(button);
        
        const spinner = document.createElement('span');
        spinner.className = 'loading-spinner ms-2';
        spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.appendChild(spinner);
    }

    /**
     * Remove save button indicators
     */
    removeSaveButtonIndicators(button) {
        button.querySelectorAll('.save-indicator, .loading-spinner').forEach(el => el.remove());
    }

    /**
     * Show unsaved changes indicator
     */
    showUnsavedChangesIndicator() {
        let indicator = document.getElementById('unsavedChangesIndicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'unsavedChangesIndicator';
            indicator.className = 'alert alert-warning alert-dismissible fade show position-fixed';
            indicator.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            indicator.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                You have unsaved changes
                <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="window.settingsUX.saveChanges()">
                    Save now
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(indicator);
        }
    }

    /**
     * Clear unsaved changes indicator
     */
    clearUnsavedChangesIndicator() {
        const indicator = document.getElementById('unsavedChangesIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Update overall unsaved changes state
     */
    updateUnsavedChangesState() {
        // Check if any fields still have changes
        const changedFields = document.querySelectorAll('.changed-field');
        this.hasUnsavedChanges = changedFields.length > 0;
        
        if (!this.hasUnsavedChanges) {
            this.clearUnsavedChangesIndicator();
            this.removeSaveButtonIndicators(document.querySelector('button[type="submit"], .save-button'));
        }
        
        this.updateSaveButtonState();
    }

    /**
     * Handle form submission
     */
    handleFormSubmit(e) {
        if (this.isSaving) {
            e.preventDefault();
            return;
        }

        this.isSaving = true;
        this.updateSaveButtonState();

        // Validate all fields before submission
        const isValid = this.validateAllFields();
        if (!isValid) {
            e.preventDefault();
            this.isSaving = false;
            this.updateSaveButtonState();
            this.showValidationSummary();
            return;
        }

        // Show progress for form submission
        this.showFormSubmissionProgress();
    }

    /**
     * Initialize live preview functionality
     */
    initializePreviews() {
        // Initialize image previews
        this.initializeImagePreviews();
        
        // Initialize color previews  
        this.initializeColorPreviews();
        
        // Initialize hero section preview
        this.initializeHeroPreview();
    }

    /**
     * Initialize image upload previews
     */
    initializeImagePreviews() {
        const uploadContainers = document.querySelectorAll('.upload-container');
        
        uploadContainers.forEach(container => {
            const fileInput = container.querySelector('input[type="file"]');
            if (fileInput) {
                this.createImagePreview(fileInput);
            }
        });
    }

    /**
     * Create live image preview for file upload
     */
    createImagePreview(fileInput) {
        const container = fileInput.closest('.upload-container');
        const previewId = `preview_${fileInput.name}`;
        
        // Create preview element
        const preview = document.createElement('div');
        preview.id = previewId;
        preview.className = 'image-preview mt-2 text-center';
        preview.innerHTML = `
            <div class="preview-placeholder text-muted small">
                <i class="fas fa-image me-1"></i>
                Image preview will appear here
            </div>
        `;
        
        container.appendChild(preview);
        
        // Listen for file changes
        fileInput.addEventListener('change', () => {
            this.updateImagePreview(fileInput, preview);
        });
        
        this.previewElements[fileInput.name] = preview;
    }

    /**
     * Update image preview with selected file
     */
    updateImagePreview(fileInput, previewElement) {
        if (!fileInput.files.length) {
            previewElement.innerHTML = `
                <div class="preview-placeholder text-muted small">
                    <i class="fas fa-image me-1"></i>
                    Image preview will appear here
                </div>
            `;
            return;
        }

        const file = fileInput.files[0];
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const preview = document.createElement('div');
            preview.className = 'preview-content';
            preview.innerHTML = `
                <img src="${e.target.result}" 
                     alt="Preview" 
                     class="img-thumbnail" 
                     style="max-width: 300px; max-height: 200px;">
                <div class="mt-2">
                    <small class="text-muted">
                        ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </small>
                </div>
            `;
            
            previewElement.innerHTML = '';
            previewElement.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    }

    /**
     * Initialize color preview functionality
     */
    initializeColorPreviews() {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        
        colorInputs.forEach(colorInput => {
            this.createColorPreview(colorInput);
        });
    }

    /**
     * Create live color preview
     */
    createColorPreview(colorInput) {
        const container = colorInput.closest('.mb-4');
        const previewId = `color_preview_${colorInput.name}`;
        
        const preview = document.createElement('div');
        preview.id = previewId;
        preview.className = 'color-preview mt-2';
        preview.innerHTML = `
            <div class="color-preview-content">
                <div class="color-swatch-preview mb-2"></div>
                <div class="color-info small text-muted">
                    <span class="color-value">${colorInput.value}</span>
                </div>
            </div>
        `;
        
        container.appendChild(preview);
        
        // Update preview when color changes
        colorInput.addEventListener('input', () => {
            this.updateColorPreview(colorInput);
        });
        
        this.previewElements[colorInput.name] = preview;
    }

    /**
     * Update color preview
     */
    updateColorPreview(colorInput) {
        const previewElement = this.previewElements[colorInput.name];
        if (!previewElement) return;
        
        const swatch = previewElement.querySelector('.color-swatch-preview');
        const valueDisplay = previewElement.querySelector('.color-value');
        
        swatch.style.backgroundColor = colorInput.value;
        valueDisplay.textContent = colorInput.value;
        
        // Apply color to related elements if applicable
        this.applyColorToElements(colorInput.name, colorInput.value);
    }

    /**
     * Apply color to related UI elements
     */
    applyColorToElements(colorName, colorValue) {
        const root = document.documentElement;
        
        if (colorName.includes('primary_color')) {
            root.style.setProperty('--primary-color', colorValue);
            
            // Update primary buttons and accents
            document.querySelectorAll('.btn-primary').forEach(btn => {
                btn.style.backgroundColor = colorValue;
                btn.style.borderColor = colorValue;
            });
        }
        
        if (colorName.includes('text_color')) {
            root.style.setProperty('--text-on-primary', colorValue);
            
            // Update text on primary backgrounds
            document.querySelectorAll('.btn-primary').forEach(btn => {
                btn.style.color = colorValue;
            });
        }
    }

    /**
     * Initialize hero section preview
     */
    initializeHeroPreview() {
        const heroFields = [
            'preferences[website][hero_title]',
            'preferences[website][hero_subtitle]', 
            'preferences[website][hero_cta_label]'
        ];
        
        // Create hero preview container
        const previewContainer = document.getElementById('heroPreview');
        if (!previewContainer) {
            const websiteTab = document.getElementById('website');
            if (websiteTab) {
                const preview = document.createElement('div');
                preview.id = 'heroPreview';
                preview.className = 'hero-preview-container mt-4 p-4 border rounded';
                preview.innerHTML = `
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-desktop me-2"></i>
                        Live Website Preview
                    </h6>
                    <div class="hero-preview-content" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                                          padding: 60px 40px; border-radius: 8px; color: white; text-align: center;">
                        <h1 class="hero-preview-title mb-3" style="font-size: 2.5rem; font-weight: 700;">
                            {{ hero_title }}
                        </h1>
                        <p class="hero-preview-subtitle mb-4" style="font-size: 1.2rem; opacity: 0.9;">
                            {{ hero_subtitle }}
                        </p>
                        <button class="hero-preview-cta btn btn-light btn-lg px-5 py-3" 
                                style="font-weight: 600; border-radius: 50px;">
                            {{ hero_cta_label }}
                        </button>
                    </div>
                `;
                websiteTab.appendChild(preview);
            }
        }
    }

    /**
     * Update hero section preview
     */
    updateHeroPreview(fieldName, value) {
        const previewContainer = document.getElementById('heroPreview');
        if (!previewContainer) return;
        
        const titleEl = previewContainer.querySelector('.hero-preview-title');
        const subtitleEl = previewContainer.querySelector('.hero-preview-subtitle');
        const ctaEl = previewContainer.querySelector('.hero-preview-cta');
        
        switch (fieldName) {
            case 'preferences[website][hero_title]':
                titleEl.textContent = value || 'Your Hero Title';
                break;
            case 'preferences[website][hero_subtitle]':
                subtitleEl.textContent = value || 'Your compelling subtitle text';
                break;
            case 'preferences[website][hero_cta_label]':
                ctaEl.textContent = value || 'Get Started';
                break;
        }
    }

    /**
     * Update all live previews
     */
    updateLivePreviews(fieldName, value) {
        if (fieldName.includes('logo') || fieldName.includes('light_logo') || fieldName.includes('favicon')) {
            // Image preview handled separately
            return;
        }
        
        if (fieldName.includes('color')) {
            this.updateColorPreview(document.querySelector(`[name="${fieldName}"]`));
        }
        
        if (fieldName.includes('hero_')) {
            this.updateHeroPreview(fieldName, value);
        }
    }

    /**
     * Setup validation system
     */
    setupValidation() {
        this.defineValidationRules();
    }

    /**
     * Define field validation rules
     */
    defineValidationRules() {
        this.validationRules = {
            'name': {
                required: true,
                minLength: 2,
                pattern: /^[a-zA-Z0-9\s\-&.,']+$/,
                messages: {
                    required: 'Company name is required',
                    minLength: 'Company name must be at least 2 characters',
                    pattern: 'Company name contains invalid characters'
                }
            },
            'email': {
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                messages: {
                    pattern: 'Please enter a valid email address'
                }
            },
            'phone': {
                pattern: /^[\+]?[1-9][\d]{0,15}$/,
                messages: {
                    pattern: 'Please enter a valid phone number'
                }
            }
        };
    }

    /**
     * Validate individual field
     */
    validateField(field) {
        const fieldName = field.name;
        const value = this.getFieldValue(field);
        const rules = this.validationRules[fieldName];
        
        if (!rules) return true;
        
        let isValid = true;
        let errorMessage = '';
        
        // Required validation
        if (rules.required && (!value || value.toString().trim() === '')) {
            isValid = false;
            errorMessage = rules.messages.required;
        }
        
        // Pattern validation
        if (isValid && rules.pattern && value && !rules.pattern.test(value)) {
            isValid = false;
            errorMessage = rules.messages.pattern || 'Invalid format';
        }
        
        // Min length validation
        if (isValid && rules.minLength && value && value.length < rules.minLength) {
            isValid = false;
            errorMessage = rules.messages.minLength;
        }
        
        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Show field validation result
     */
    showFieldValidation(field, isValid, errorMessage) {
        // Remove existing validation states
        field.classList.remove('is-valid', 'is-invalid');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.field-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        if (isValid && this.hasFieldChanged(field.name, this.getFieldValue(field))) {
            field.classList.add('is-valid');
        } else if (!isValid && errorMessage) {
            field.classList.add('is-invalid');
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error-message invalid-feedback d-block';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${errorMessage}`;
            
            field.parentNode.appendChild(errorDiv);
        }
    }

    /**
     * Validate all fields
     */
    validateAllFields() {
        let isValid = true;
        const fields = document.querySelectorAll('#settingsForm input, #settingsForm select, #settingsForm textarea');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Check if field is currently valid
     */
    isFieldValid(field) {
        const rules = this.validationRules[field.name];
        if (!rules) return true;
        
        const value = this.getFieldValue(field);
        
        if (rules.required && (!value || value.toString().trim() === '')) {
            return false;
        }
        
        if (rules.pattern && value && !rules.pattern.test(value)) {
            return false;
        }
        
        if (rules.minLength && value && value.length < rules.minLength) {
            return false;
        }
        
        return true;
    }

    /**
     * Show validation summary
     */
    showValidationSummary() {
        const invalidFields = document.querySelectorAll('.is-invalid');
        
        if (invalidFields.length === 0) return;
        
        const summary = document.createElement('div');
        summary.className = 'validation-summary alert alert-danger mt-3';
        summary.innerHTML = `
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
            <ul class="mb-0">
                ${Array.from(invalidFields).map(field => {
                    const label = field.closest('.mb-4, .form-group')?.querySelector('label')?.textContent || field.name;
                    const errorMsg = field.parentNode.querySelector('.field-error-message')?.textContent || 'Invalid value';
                    return `<li>${label}: ${errorMsg}</li>`;
                }).join('')}
            </ul>
        `;
        
        const form = document.getElementById('settingsForm');
        const existingSummary = form.querySelector('.validation-summary');
        if (existingSummary) {
            existingSummary.remove();
        }
        
        form.insertBefore(summary, form.firstChild);
        
        // Scroll to first error
        invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        // Auto-save every 30 seconds for draft preferences
        setInterval(() => {
            if (this.hasUnsavedChanges) {
                this.autoSaveDraft();
            }
        }, 30000);
        
        // Auto-save on tab blur (when user switches tabs)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && this.hasUnsavedChanges) {
                this.autoSaveDraft();
            }
        });
    }

    /**
     * Auto-save draft changes
     */
    autoSaveDraft() {
        const formData = new FormData(document.getElementById('settingsForm'));
        const draftData = {};
        
        for (let [key, value] of formData.entries()) {
            // Only save preference changes, not sensitive data
            if (key.includes('preferences.')) {
                draftData[key] = value;
            }
        }
        
        // Store in localStorage
        localStorage.setItem('settings_draft', JSON.stringify(draftData));
        this.showDraftSavedNotification();
    }

    /**
     * Show draft saved notification
     */
    showDraftSavedNotification() {
        const notification = document.createElement('div');
        notification.className = 'toast-notification position-fixed bg-info text-white p-2 rounded';
        notification.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; opacity: 0.9;';
        notification.innerHTML = `
            <i class="fas fa-save me-2"></i>
            Draft saved automatically
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }

    /**
     * Load draft data
     */
    loadDraft() {
        const draftData = localStorage.getItem('settings_draft');
        if (!draftData) return;
        
        try {
            const draft = JSON.parse(draftData);
            let hasDraftData = false;
            
            Object.keys(draft).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field && draft[fieldName] !== this.getFieldValue(field)) {
                    field.value = draft[fieldName];
                    hasDraftData = true;
                    this.handleInputChange({ target: field });
                }
            });
            
            if (hasDraftData) {
                this.showDraftLoadedNotification();
            }
        } catch (e) {
            console.warn('Failed to load draft data:', e);
        }
    }

    /**
     * Show draft loaded notification
     */
    showDraftLoadedNotification() {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
        notification.innerHTML = `
            <i class="fas fa-refresh me-2"></i>
            Previous draft data has been loaded
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Setup confirmation dialogs
     */
    setupConfirmationDialogs() {
        // Confirmation for destructive actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.destructive-action')) {
                e.preventDefault();
                this.showConfirmationDialog(e.target);
            }
        });
    }

    /**
     * Show confirmation dialog
     */
    showConfirmationDialog(element) {
        const message = element.dataset.confirm || 'Are you sure you want to perform this action?';
        
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Confirm Action
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-warning confirm-action-btn">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const confirmBtn = modal.querySelector('.confirm-action-btn');
        confirmBtn.addEventListener('click', () => {
            // Execute the original action
            if (element.href) {
                window.location.href = element.href;
            } else if (element.onclick) {
                element.onclick();
            }
            
            // Close modal
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
        });
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Clean up after hide
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    /**
     * Setup progress indicators
     */
    setupProgressIndicators() {
        this.setupFileUploadProgress();
        this.setupFormSubmissionProgress();
    }

    /**
     * Setup file upload progress
     */
    setupFileUploadProgress() {
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.files.length > 0) {
                this.showFileUploadProgress(e.target);
            }
        });
    }

    /**
     * Show file upload progress
     */
    showFileUploadProgress(fileInput) {
        const container = fileInput.closest('.upload-container');
        const progressBar = document.createElement('div');
        progressBar.className = 'upload-progress mt-2';
        progressBar.innerHTML = `
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted mt-1">
                <i class="fas fa-upload me-1"></i>
                Uploading file...
            </small>
        `;
        
        container.appendChild(progressBar);
        
        const progressElement = progressBar.querySelector('.progress-bar');
        
        // Simulate upload progress (in real implementation, this would be actual upload progress)
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 30;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                
                setTimeout(() => {
                    progressBar.remove();
                    this.showUploadCompleteNotification();
                }, 500);
            }
            progressElement.style.width = progress + '%';
        }, 200);
    }

    /**
     * Show upload complete notification
     */
    showUploadCompleteNotification() {
        const notification = document.createElement('div');
        notification.className = 'toast-notification position-fixed bg-success text-white p-2 rounded';
        notification.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; opacity: 0.9;';
        notification.innerHTML = `
            <i class="fas fa-check me-2"></i>
            File uploaded successfully
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Setup form submission progress
     */
    setupFormSubmissionProgress() {
        const form = document.getElementById('settingsForm');
        if (form) {
            form.addEventListener('submit', () => {
                this.showFormSubmissionProgress();
            });
        }
    }

    /**
     * Show form submission progress
     */
    showFormSubmissionProgress() {
        const progressModal = document.createElement('div');
        progressModal.className = 'modal fade';
        progressModal.innerHTML = `
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-save fa-3x text-primary"></i>
                        </div>
                        <h5>Saving Settings...</h5>
                        <div class="progress mt-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Please wait while we save your settings
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(progressModal);
        
        // Show modal (prevent backdrop click)
        const bsModal = new bootstrap.Modal(progressModal, {
            backdrop: 'static',
            keyboard: false
        });
        bsModal.show();
    }

    /**
     * Handle file upload with progress and preview
     */
    handleFileUpload(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showError('Please select a valid image file (JPEG, PNG, GIF, WebP)');
            fileInput.value = '';
            return;
        }
        
        // Validate file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            this.showError('File size must be less than 2MB');
            fileInput.value = '';
            return;
        }
        
        // Update preview if exists
        const previewElement = this.previewElements[fileInput.name];
        if (previewElement) {
            this.updateImagePreview(fileInput, previewElement);
        }
    }

    /**
     * Handle color changes for live preview
     */
    handleColorChange(colorInput) {
        const hexInput = colorInput.parentNode.querySelector('input[type="text"]');
        if (hexInput && colorInput.type === 'color') {
            hexInput.value = colorInput.value;
        }
        
        this.updateColorPreview(colorInput);
    }

    /**
     * Save changes manually
     */
    saveChanges() {
        const form = document.getElementById('settingsForm');
        if (form && this.hasUnsavedChanges) {
            form.submit();
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        // Use existing Toastr if available
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Use existing Toastr if available
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            alert(message);
        }
    }

    /**
     * Public API method to refresh previews
     */
    refreshPreviews() {
        Object.keys(this.previewElements).forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.updateLivePreviews(fieldName, this.getFieldValue(field));
            }
        });
    }

    /**
     * Cleanup method
     */
    destroy() {
        // Remove all event listeners and clean up
        this.clearUnsavedChangesIndicator();
        
        // Remove draft data
        localStorage.removeItem('settings_draft');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a settings page
    if (document.getElementById('settingsForm')) {
        window.settingsUX = new SettingsUXEnhancements();
        
        // Load draft data if available
        window.settingsUX.loadDraft();
        
        // Make refresh method available globally
        window.refreshSettingsPreviews = () => {
            window.settingsUX.refreshPreviews();
        };
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SettingsUXEnhancements;
}