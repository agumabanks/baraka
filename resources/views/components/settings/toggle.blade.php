{{-- Settings Toggle Component --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
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
            
            <div class="mt-2">
                <div class="form-check form-switch">
                    <input 
                        class="form-check-input @error($name) is-invalid @enderror" 
                        type="checkbox" 
                        id="{{ $id ?? $name }}"
                        name="{{ $name }}"
                        value="1"
                        {{ $checked ?? false ? 'checked' : '' }}
                        {{ $disabled ?? false ? 'disabled' : '' }}
                    >
                    <label class="form-check-label" for="{{ $id ?? $name }}">
                        {{ $slot ?? 'Enable' }}
                    </label>
                </div>
            </div>
        </div>
        
        @if(isset($icon))
            <div class="ms-3">
                <i class="{{ $icon }} text-primary" style="font-size: 1.2rem;"></i>
            </div>
        @endif
    </div>
    
    @error($name)
        <div class="invalid-feedback d-block">
            <i class="fas fa-exclamation-circle me-1"></i>
            {{ $message }}
        </div>
    @enderror
</div>