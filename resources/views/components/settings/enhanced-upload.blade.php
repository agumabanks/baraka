@props([
    'name',
    'label',
    'existing' => null,
    'icon' => 'fas fa-upload',
    'accept' => '*/*',
    'description' => '',
    'size' => 'normal'
])

<div class="enhanced-upload-container" data-size="{{ $size }}">
    <label for="{{ $name }}" class="enhanced-upload-label">
        <div class="enhanced-upload-card">
            @if($existing)
                <div class="upload-preview-existing">
                    <img src="{{ $existing }}" alt="Current {{ $label }}" class="upload-image">
                    <div class="upload-overlay">
                        <i class="fas fa-camera"></i>
                        <span>Change {{ $label }}</span>
                    </div>
                </div>
            @else
                <div class="upload-placeholder">
                    <div class="upload-icon">
                        <i class="{{ $icon }}"></i>
                    </div>
                    <div class="upload-text">
                        <strong>Drop files here or click to upload</strong>
                        @if($description)
                            <p class="upload-description">{{ $description }}</p>
                        @endif
                    </div>
                </div>
            @endif
            
            <input type="file" 
                   name="{{ $name }}" 
                   id="{{ $name }}" 
                   class="enhanced-upload-input" 
                   accept="{{ $accept }}"
                   {{ $attributes }}>
        </div>
    </label>
    
    <!-- Upload Progress -->
    <div class="upload-progress-container d-none">
        <div class="upload-progress-bar">
            <div class="upload-progress-fill"></div>
        </div>
        <div class="upload-progress-text">
            <span class="upload-status">Uploading...</span>
            <span class="upload-percentage">0%</span>
        </div>
    </div>
    
    <!-- Upload Success -->
    <div class="upload-success d-none">
        <i class="fas fa-check-circle text-success"></i>
        <span>Upload successful</span>
    </div>
    
    <!-- Upload Error -->
    <div class="upload-error d-none">
        <i class="fas fa-exclamation-circle text-danger"></i>
        <span class="error-message">Upload failed</span>
    </div>
</div>

<style>
/* Enhanced Upload Styles */
.enhanced-upload-container {
    margin-bottom: var(--spacing-6);
}

.enhanced-upload-card {
    position: relative;
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--background-secondary);
    transition: all var(--transition-base);
    cursor: pointer;
    overflow: hidden;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.enhanced-upload-card:hover {
    border-color: var(--primary-color);
    background: var(--background-tertiary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.enhanced-upload-card.dragover {
    border-color: var(--success-color);
    background: rgba(52, 199, 89, 0.1);
    transform: scale(1.02);
}

.upload-placeholder {
    text-align: center;
    padding: var(--spacing-8);
}

.upload-icon {
    font-size: 3rem;
    color: var(--text-tertiary);
    margin-bottom: var(--spacing-4);
    transition: all var(--transition-base);
}

.enhanced-upload-card:hover .upload-icon {
    color: var(--primary-color);
    transform: scale(1.1);
}

.upload-text {
    color: var(--text-secondary);
}

.upload-text strong {
    display: block;
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-2);
    color: var(--text-primary);
}

.upload-description {
    font-size: var(--font-size-sm);
    color: var(--text-tertiary);
    margin: 0;
}

.upload-preview-existing {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 200px;
}

.upload-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: var(--radius-lg);
}

.upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all var(--transition-base);
    border-radius: var(--radius-lg);
}

.upload-overlay i {
    font-size: 2rem;
    margin-bottom: var(--spacing-2);
}

.upload-preview-existing:hover .upload-overlay {
    opacity: 1;
}

.enhanced-upload-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

/* Upload Progress */
.upload-progress-container {
    margin-top: var(--spacing-4);
    padding: var(--spacing-4);
    background: var(--background-secondary);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.upload-progress-bar {
    height: 8px;
    background: var(--color-gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin-bottom: var(--spacing-2);
}

.upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--info-color));
    border-radius: var(--radius-full);
    transition: width var(--transition-base);
    width: 0%;
}

.upload-progress-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

/* Upload Status Messages */
.upload-success,
.upload-error {
    margin-top: var(--spacing-4);
    padding: var(--spacing-3);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
}

.upload-success {
    background: rgba(52, 199, 89, 0.1);
    color: var(--success-color);
    border: 1px solid rgba(52, 199, 89, 0.2);
}

.upload-error {
    background: rgba(255, 59, 48, 0.1);
    color: var(--danger-color);
    border: 1px solid rgba(255, 59, 48, 0.2);
}

/* Size variations */
.enhanced-upload-container[data-size="small"] .enhanced-upload-card {
    min-height: 120px;
}

.enhanced-upload-container[data-size="small"] .upload-placeholder {
    padding: var(--spacing-4);
}

.enhanced-upload-container[data-size="large"] .enhanced-upload-card {
    min-height: 300px;
}

.enhanced-upload-container[data-size="large"] .upload-placeholder {
    padding: var(--spacing-12);
}

/* Animation keyframes */
@keyframes uploadShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.upload-error {
    animation: uploadShake 0.5s ease-in-out;
}

@keyframes uploadPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.upload-success {
    animation: uploadPulse 0.6s ease-in-out;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .upload-overlay {
        background: rgba(0, 0, 0, 0.8);
    }
    
    .upload-progress-bar {
        background: var(--color-gray-700);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadContainers = document.querySelectorAll('.enhanced-upload-container');
    
    uploadContainers.forEach(container => {
        const card = container.querySelector('.enhanced-upload-card');
        const input = container.querySelector('.enhanced-upload-input');
        const progressContainer = container.querySelector('.upload-progress-container');
        const progressFill = container.querySelector('.upload-progress-fill');
        const progressText = container.querySelector('.upload-progress-text');
        const successMessage = container.querySelector('.upload-success');
        const errorMessage = container.querySelector('.upload-error');
        
        // Drag and drop functionality
        card.addEventListener('dragover', function(e) {
            e.preventDefault();
            card.classList.add('dragover');
        });
        
        card.addEventListener('dragleave', function() {
            card.classList.remove('dragover');
        });
        
        card.addEventListener('drop', function(e) {
            e.preventDefault();
            card.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileUpload(container, files[0]);
            }
        });
        
        // File input change
        input.addEventListener('change', function() {
            if (input.files.length > 0) {
                handleFileUpload(container, input.files[0]);
            }
        });
        
        function handleFileUpload(container, file) {
            // Validate file
            if (!validateFile(file, container)) {
                return;
            }
            
            // Show progress
            showProgress(container);
            
            // Simulate upload progress (replace with actual upload logic)
            simulateUpload(container, file);
        }
        
        function validateFile(file, container) {
            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (file.size > maxSize) {
                showError(container, 'File size must be less than 2MB');
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                showError(container, 'Please select a valid image file');
                return false;
            }
            
            return true;
        }
        
        function showProgress(container) {
            hideMessages(container);
            container.querySelector('.upload-progress-container').classList.remove('d-none');
        }
        
        function simulateUpload(container, file) {
            let progress = 0;
            const progressFill = container.querySelector('.upload-progress-fill');
            const statusText = container.querySelector('.upload-status');
            
            const interval = setInterval(() => {
                progress += Math.random() * 20;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    
                    // Show success
                    container.querySelector('.upload-progress-container').classList.add('d-none');
                    container.querySelector('.upload-success').classList.remove('d-none');
                    
                    // Update preview if exists
                    updatePreview(container, file);
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        container.querySelector('.upload-success').classList.add('d-none');
                    }, 3000);
                }
                
                progressFill.style.width = progress + '%';
                statusText.textContent = progress < 100 ? 'Uploading...' : 'Upload complete';
            }, 200);
        }
        
        function showError(container, message) {
            hideMessages(container);
            const errorEl = container.querySelector('.upload-error');
            errorEl.querySelector('.error-message').textContent = message;
            errorEl.classList.remove('d-none');
            
            // Hide error after 5 seconds
            setTimeout(() => {
                errorEl.classList.add('d-none');
            }, 5000);
        }
        
        function hideMessages(container) {
            container.querySelector('.upload-progress-container').classList.add('d-none');
            container.querySelector('.upload-success').classList.add('d-none');
            container.querySelector('.upload-error').classList.add('d-none');
        }
        
        function updatePreview(container, file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = container.querySelector('.upload-preview-existing') || 
                              createPreview(container);
                
                if (preview.querySelector('img')) {
                    preview.querySelector('img').src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
        
        function createPreview(container) {
            const preview = document.createElement('div');
            preview.className = 'upload-preview-existing';
            preview.innerHTML = `
                <img class="upload-image">
                <div class="upload-overlay">
                    <i class="fas fa-camera"></i>
                    <span>Change image</span>
                </div>
            `;
            
            container.querySelector('.enhanced-upload-card').innerHTML = '';
            container.querySelector('.enhanced-upload-card').appendChild(preview);
            
            return preview;
        }
    });
});
</script>