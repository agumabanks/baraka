{{-- Settings File Upload Component --}}
<div class="mb-4">
    @if(isset($label))
        <label class="form-label fw-semibold" for="{{ $id ?? $name }}">
            {{ $label }}
            @if(isset($required) && $required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    @if(isset($help))
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
    
    <div class="upload-container mt-2">
        <div class="upload-area @error($name) is-invalid @enderror" id="uploadArea{{ $id ?? $name }}">
            <input 
                type="file" 
                class="form-control @error($name) is-invalid @enderror" 
                id="{{ $id ?? $name }}"
                name="{{ $name }}"
                accept="{{ $accept ?? 'image/*' }}"
                {{ $required ?? false ? 'required' : '' }}
                {{ $disabled ?? false ? 'disabled' : '' }}
                style="display: none;"
            >
            
            @if(isset($existing) && $existing)
                <div class="current-file mb-3">
                    <img src="{{ asset($existing) }}" 
                         alt="Current {{ $label ?? 'Image' }}" 
                         class="img-thumbnail" 
                         style="max-width: 200px; max-height: 150px;">
                    <p class="text-muted small mt-1">
                        <i class="fas fa-image me-1"></i>
                        Current {{ $label ?? 'image' }}
                    </p>
                </div>
            @endif
            
            <div class="upload-placeholder text-center p-4 border border-dashed border-secondary rounded">
                <i class="{{ $icon ?? 'fas fa-cloud-upload-alt' }} text-primary mb-3" style="font-size: 2rem;"></i>
                <p class="mb-2">
                    <strong>Click to upload</strong> or drag and drop
                </p>
                <p class="text-muted small mb-0">
                    {{ $acceptText ?? 'PNG, JPG, GIF up to 2MB' }}
                </p>
            </div>
        </div>
        
        @if(isset($description))
            <small class="form-text text-muted mt-2">{{ $description }}</small>
        @endif
    </div>
    
    @error($name)
        <div class="invalid-feedback d-block">
            <i class="fas fa-exclamation-circle me-1"></i>
            {{ $message }}
        </div>
    @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea{{ $id ?? $name }}');
    const fileInput = document.getElementById('{{ $id ?? $name }}');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    
    if (uploadArea && fileInput && placeholder) {
        // Click to upload
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                updateFileDisplay(fileInput.files[0]);
            }
        });
        
        function updateFileDisplay(file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            placeholder.innerHTML = `
                <i class="fas fa-file text-primary mb-3" style="font-size: 2rem;"></i>
                <p class="mb-1"><strong>${fileName}</strong></p>
                <p class="text-muted small mb-0">${fileSize} MB</p>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="clearFile{{ $id ?? $name }}()">
                    <i class="fas fa-times me-1"></i>Remove
                </button>
            `;
        }
        
        // Clear file function
        window.clearFile{{ $id ?? $name }} = function() {
            fileInput.value = '';
            placeholder.innerHTML = `
                <i class="{{ $icon ?? 'fas fa-cloud-upload-alt' }} text-primary mb-3" style="font-size: 2rem;"></i>
                <p class="mb-2"><strong>Click to upload</strong> or drag and drop</p>
                <p class="text-muted small mb-0">{{ $acceptText ?? 'PNG, JPG, GIF up to 2MB' }}</p>
            `;
        };
    }
});
</script>

<style>
.upload-area {
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area:hover .upload-placeholder {
    border-color: var(--primary-color);
    background-color: rgba(13, 110, 253, 0.05);
}

.upload-area.dragover .upload-placeholder {
    border-color: var(--primary-color);
    background-color: rgba(13, 110, 253, 0.1);
}

.current-file {
    text-align: center;
}

.current-file img {
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
}

.current-file img:hover {
    border-color: var(--primary-color);
    transform: scale(1.05);
}
</style>
@endpush