{{-- Settings Color Picker Component --}}
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
    
    <div class="color-picker-container mt-2">
        <div class="input-group @error($name) is-invalid @enderror">
            <input 
                type="color" 
                class="form-control form-control-color" 
                id="{{ $id ?? $name }}_color"
                name="{{ $name }}"
                value="{{ $value ?? '#000000' }}"
                {{ $disabled ?? false ? 'disabled' : '' }}
            >
            <input 
                type="text" 
                class="form-control @error($name) is-invalid @enderror" 
                id="{{ $id ?? $name }}_hex"
                placeholder="#000000"
                value="{{ $value ?? '#000000' }}"
                {{ $disabled ?? false ? 'disabled' : '' }}
            >
            <button 
                class="btn btn-outline-secondary dropdown-toggle" 
                type="button" 
                data-bs-toggle="dropdown"
                {{ $disabled ?? false ? 'disabled' : '' }}
            >
                <i class="fas fa-palette"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Quick Colors</h6></li>
                <li><hr class="dropdown-divider"></li>
                @php
                    $quickColors = [
                        '#000000' => 'Black',
                        '#FFFFFF' => 'White',
                        '#FF0000' => 'Red',
                        '#00FF00' => 'Green',
                        '#0000FF' => 'Blue',
                        '#FFFF00' => 'Yellow',
                        '#FF00FF' => 'Magenta',
                        '#00FFFF' => 'Cyan',
                        '#FFA500' => 'Orange',
                        '#800080' => 'Purple',
                        '#FFC0CB' => 'Pink',
                        '#A52A2A' => 'Brown',
                        '#808080' => 'Gray',
                        '#1F2937' => 'Dark Gray',
                        '#0d6efd' => 'Primary Blue'
                    ];
                @endphp
                @foreach($quickColors as $color => $name)
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#" 
                           onclick="setColor{{ $id ?? $name }}('{{ $color }}')">
                            <div class="color-swatch me-2" style="background-color: {{ $color }}; width: 20px; height: 20px; border-radius: 3px; border: 1px solid #ccc;"></div>
                            <span>{{ $name }}</span>
                            <code class="ms-auto">{{ $color }}</code>
                        </a>
                    </li>
                @endforeach
            </ul>
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
    const colorInput = document.getElementById('{{ $id ?? $name }}_color');
    const hexInput = document.getElementById('{{ $id ?? $name }}_hex');
    
    if (colorInput && hexInput) {
        // Sync color picker with hex input
        colorInput.addEventListener('input', function() {
            hexInput.value = this.value;
        });
        
        // Sync hex input with color picker
        hexInput.addEventListener('input', function() {
            const value = this.value;
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorInput.value = value;
            }
        });
        
        // Validate hex input on blur
        hexInput.addEventListener('blur', function() {
            const value = this.value;
            if (!/^#[0-9A-F]{6}$/i.test(value)) {
                this.value = colorInput.value;
                showToast('Please enter a valid hex color code (e.g., #FF0000)', 'warning');
            }
        });
    }
    
    // Global function for quick colors
    window.setColor{{ $id ?? $name }} = function(color) {
        colorInput.value = color;
        hexInput.value = color;
    };
});
</script>

<style>
.color-picker-container .form-control-color {
    border-radius: 0.375rem 0 0 0.375rem;
}

.color-picker-container .form-control:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
}

.color-swatch {
    display: inline-block;
    transition: transform 0.2s ease;
}

.dropdown-item:hover .color-swatch {
    transform: scale(1.2);
}

.dropdown-item code {
    font-size: 0.75rem;
}
</style>
@endpush