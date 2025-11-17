@props([
    'name',
    'label',
    'value' => '#007AFF',
    'help' => '',
    'size' => 'normal'
])

<div class="enhanced-color-picker-container" data-size="{{ $size }}">
    <label class="enhanced-color-picker-label" for="{{ $name }}">
        <span class="enhanced-color-label-text">{{ $label }}</span>
    </label>
    
    <div class="enhanced-color-picker-wrapper">
        <!-- Color Preview -->
        <div class="color-preview-section">
            <div class="color-preview-circle" style="background-color: {{ $value }}">
                <div class="color-preview-inner">
                    <i class="fas fa-palette"></i>
                </div>
            </div>
            <div class="color-info">
                <span class="color-value-text">{{ $value }}</span>
                <span class="color-rgb-text">{{ rgbFromHex($value) }}</span>
            </div>
        </div>
        
        <!-- Color Picker Controls -->
        <div class="color-controls">
            <div class="color-input-group">
                <input type="color" 
                       id="{{ $name }}_color" 
                       class="color-input-native" 
                       value="{{ $value }}"
                       aria-label="Color picker for {{ $label }}">
                
                <input type="text" 
                       id="{{ $name }}_hex" 
                       name="{{ $name }}" 
                       class="color-input-hex" 
                       value="{{ $value }}"
                       pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                       placeholder="#000000">
                
                <button type="button" 
                        class="color-random-btn" 
                        title="Generate random color"
                        aria-label="Generate random color">
                    <i class="fas fa-dice"></i>
                </button>
            </div>
            
            <!-- Color Sliders -->
            <div class="color-sliders" style="display: none;">
                <div class="slider-group">
                    <label class="slider-label">Hue</label>
                    <input type="range" 
                           id="{{ $name }}_hue" 
                           class="slider-input hue-slider" 
                           min="0" 
                           max="360" 
                           value="{{ hueFromHex($value) }}">
                    <span class="slider-value">{{ hueFromHex($value) }}</span>
                </div>
                
                <div class="slider-group">
                    <label class="slider-label">Saturation</label>
                    <input type="range" 
                           id="{{ $name }}_saturation" 
                           class="slider-input saturation-slider" 
                           min="0" 
                           max="100" 
                           value="{{ saturationFromHex($value) }}">
                    <span class="slider-value">{{ saturationFromHex($value) }}%</span>
                </div>
                
                <div class="slider-group">
                    <label class="slider-label">Lightness</label>
                    <input type="range" 
                           id="{{ $name }}_lightness" 
                           class="slider-input lightness-slider" 
                           min="0" 
                           max="100" 
                           value="{{ lightnessFromHex($value) }}">
                    <span class="slider-value">{{ lightnessFromHex($value) }}%</span>
                </div>
            </div>
            
            <!-- Color Presets -->
            <div class="color-presets">
                <span class="presets-label">Quick Colors:</span>
                <div class="presets-grid">
                    <button type="button" class="preset-color" data-color="#007AFF" style="background-color: #007AFF" title="Apple Blue"></button>
                    <button type="button" class="preset-color" data-color="#34C759" style="background-color: #34C759" title="Apple Green"></button>
                    <button type="button" class="preset-color" data-color="#FF3B30" style="background-color: #FF3B30" title="Apple Red"></button>
                    <button type="button" class="preset-color" data-color="#FF9500" style="background-color: #FF9500" title="Apple Orange"></button>
                    <button type="button" class="preset-color" data-color="#AF52DE" style="background-color: #AF52DE" title="Apple Purple"></button>
                    <button type="button" class="preset-color" data-color="#FF2D92" style="background-color: #FF2D92" title="Apple Pink"></button>
                </div>
            </div>
        </div>
        
        <!-- Advanced Toggle -->
        <button type="button" 
                class="advanced-toggle" 
                aria-expanded="false" 
                aria-controls="{{ $name }}_advanced">
            <i class="fas fa-cog"></i>
            <span>Advanced</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        
        <!-- Help Text -->
        @if($help)
            <div class="color-help-text">{{ $help }}</div>
        @endif
    </div>
</div>

@php
function rgbFromHex($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }
    return sprintf('rgb(%d, %d, %d)', 
                   hexdec(substr($hex, 0, 2)), 
                   hexdec(substr($hex, 2, 2)), 
                   hexdec(substr($hex, 4, 2)));
}

function hueFromHex($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }
    
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $diff = $max - $min;
    
    if ($max == $min) {
        return 0;
    }
    
    switch($max) {
        case $r: return (($g - $b) / $diff) % 6;
        case $g: return ($b - $r) / $diff + 2;
        case $b: return ($r - $g) / $diff + 4;
    }
    
    return 0;
}

function saturationFromHex($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }
    
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $diff = $max - $min;
    
    if ($max == 0) {
        return 0;
    }
    
    return round(($diff / $max) * 100);
}

function lightnessFromHex($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }
    
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    
    return round((($max + $min) / 2) * 100);
}
@endphp

<style>
/* Enhanced Color Picker Styles */
.enhanced-color-picker-container {
    margin-bottom: var(--spacing-6);
}

.enhanced-color-picker-label {
    display: block;
    margin-bottom: var(--spacing-3);
}

.enhanced-color-label-text {
    font-weight: 600;
    font-size: var(--font-size-sm);
    color: var(--text-primary);
    line-height: 1.4;
}

.enhanced-color-picker-wrapper {
    background: var(--background-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    transition: all var(--transition-base);
}

.enhanced-color-picker-wrapper:hover {
    border-color: var(--border-color-hover);
    box-shadow: var(--shadow-sm);
}

/* Color Preview */
.color-preview-section {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.color-preview-circle {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-full);
    position: relative;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    border: 3px solid white;
}

.color-preview-circle:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-lg);
}

.color-preview-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    opacity: 0;
    transition: opacity var(--transition-base);
}

.color-preview-circle:hover .color-preview-inner {
    opacity: 1;
}

.color-info {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.color-value-text {
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--text-primary);
}

.color-rgb-text {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
}

/* Color Controls */
.color-input-group {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-4);
}

.color-input-native {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    box-shadow: var(--shadow-sm);
}

.color-input-hex {
    flex: 1;
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: var(--font-size-sm);
    background: var(--background-secondary);
    color: var(--text-primary);
    transition: all var(--transition-fast);
}

.color-input-hex:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.color-random-btn {
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--background-secondary);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: center;
}

.color-random-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    transform: rotate(180deg);
}

/* Color Sliders */
.color-sliders {
    margin-bottom: var(--spacing-4);
    padding: var(--spacing-4);
    background: var(--background-secondary);
    border-radius: var(--radius-md);
}

.slider-group {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-3);
}

.slider-group:last-child {
    margin-bottom: 0;
}

.slider-label {
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--text-primary);
    min-width: 80px;
}

.slider-input {
    flex: 1;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--background-primary);
    outline: none;
    -webkit-appearance: none;
}

.slider-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: var(--radius-full);
    background: var(--primary-color);
    cursor: pointer;
    box-shadow: var(--shadow-sm);
}

.slider-input::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: var(--radius-full);
    background: var(--primary-color);
    cursor: pointer;
    border: none;
    box-shadow: var(--shadow-sm);
}

.slider-value {
    font-size: var(--font-size-xs);
    font-weight: 600;
    color: var(--text-secondary);
    min-width: 40px;
    text-align: right;
}

/* Color Presets */
.color-presets {
    margin-bottom: var(--spacing-4);
}

.presets-label {
    display: block;
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-2);
    font-weight: 500;
}

.presets-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: var(--spacing-2);
}

.preset-color {
    width: 32px;
    height: 32px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-base);
    position: relative;
}

.preset-color:hover {
    transform: scale(1.1);
    border-color: var(--text-primary);
    box-shadow: var(--shadow-sm);
}

.preset-color.active {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(0, 122, 255, 0.2);
}

/* Advanced Toggle */
.advanced-toggle {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--background-secondary);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-4);
}

.advanced-toggle:hover {
    background: var(--background-tertiary);
    color: var(--text-primary);
    border-color: var(--border-color-hover);
}

.advanced-toggle[aria-expanded="true"] i:last-child {
    transform: rotate(180deg);
}

/* Help Text */
.color-help-text {
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
    margin-top: var(--spacing-2);
    padding: var(--spacing-2);
    background: var(--background-secondary);
    border-radius: var(--radius-sm);
}

/* Size variations */
.enhanced-color-picker-container[data-size="small"] .color-preview-circle {
    width: 40px;
    height: 40px;
}

.enhanced-color-picker-container[data-size="small"] .color-preview-section {
    gap: var(--spacing-2);
}

/* Animations */
@keyframes colorChange {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.color-change-animation {
    animation: colorChange 0.3s ease-in-out;
}

/* Responsive */
@media (max-width: 768px) {
    .color-preview-section {
        flex-direction: column;
        text-align: center;
    }
    
    .slider-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .slider-label {
        min-width: auto;
        text-align: left;
    }
    
    .slider-value {
        text-align: left;
        min-width: auto;
    }
    
    .presets-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorPickers = document.querySelectorAll('.enhanced-color-picker-container');
    
    colorPickers.forEach(picker => {
        const name = picker.querySelector('input[name]').name;
        const nativePicker = picker.querySelector(`#${name.replace('[', '_').replace(']', '')}_color`);
        const hexInput = picker.querySelector(`#${name.replace('[', '_').replace(']', '')}_hex`);
        const randomBtn = picker.querySelector('.color-random-btn');
        const previewCircle = picker.querySelector('.color-preview-circle');
        const presetColors = picker.querySelectorAll('.preset-color');
        const sliders = picker.querySelector('.color-sliders');
        const advancedToggle = picker.querySelector('.advanced-toggle');
        
        // Native color picker change
        nativePicker.addEventListener('input', function() {
            updateColor(this.value);
        });
        
        // Hex input change
        hexInput.addEventListener('input', function() {
            if (this.value.match(/^#[A-Fa-f0-9]{6}$/) || this.value.match(/^#[A-Fa-f0-9]{3}$/)) {
                updateColor(this.value);
            }
        });
        
        // Random color button
        randomBtn.addEventListener('click', function() {
            const randomColor = generateRandomColor();
            updateColor(randomColor);
        });
        
        // Preset colors
        presetColors.forEach(preset => {
            preset.addEventListener('click', function() {
                updateColor(this.dataset.color);
            });
        });
        
        // Advanced toggle
        advancedToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            
            if (sliders.style.display === 'none' || !sliders.style.display) {
                sliders.style.display = 'block';
                sliders.classList.add('premium-fade-in');
            } else {
                sliders.style.display = 'none';
            }
        });
        
        // Slider controls
        const hueSlider = picker.querySelector('.hue-slider');
        const saturationSlider = picker.querySelector('.saturation-slider');
        const lightnessSlider = picker.querySelector('.lightness-slider');
        
        if (hueSlider && saturationSlider && lightnessSlider) {
            hueSlider.addEventListener('input', updateFromHsl);
            saturationSlider.addEventListener('input', updateFromHsl);
            lightnessSlider.addEventListener('input', updateFromHsl);
        }
        
        function updateColor(color) {
            // Update inputs
            nativePicker.value = color;
            hexInput.value = color;
            
            // Update preview
            previewCircle.style.backgroundColor = color;
            picker.querySelector('.color-value-text').textContent = color;
            picker.querySelector('.color-rgb-text').textContent = rgbFromHex(color);
            
            // Update preset active state
            presetColors.forEach(preset => {
                if (preset.dataset.color.toLowerCase() === color.toLowerCase()) {
                    preset.classList.add('active');
                } else {
                    preset.classList.remove('active');
                }
            });
            
            // Update sliders
            if (hueSlider && saturationSlider && lightnessSlider) {
                const hsl = hexToHsl(color);
                hueSlider.value = hsl.h;
                saturationSlider.value = hsl.s;
                lightnessSlider.value = hsl.l;
                
                hueSlider.nextElementSibling.textContent = hsl.h;
                saturationSlider.nextElementSibling.textContent = hsl.s + '%';
                lightnessSlider.nextElementSibling.textContent = hsl.l + '%';
            }
            
            // Add animation
            previewCircle.classList.add('color-change-animation');
            setTimeout(() => {
                previewCircle.classList.remove('color-change-animation');
            }, 300);
            
            // Trigger change event
            hexInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        function updateFromHsl() {
            const h = hueSlider.value;
            const s = saturationSlider.value;
            const l = lightnessSlider.value;
            
            const color = hslToHex(h, s, l);
            updateColor(color);
        }
        
        function generateRandomColor() {
            return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
        }
        
        function rgbFromHex(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? `rgb(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)})` : null;
        }
        
        function hexToHsl(hex) {
            let r = 0, g = 0, b = 0;
            
            if (hex.length === 4) {
                r = "0x" + hex[1] + hex[1];
                g = "0x" + hex[2] + hex[2];
                b = "0x" + hex[3] + hex[3];
            } else if (hex.length === 7) {
                r = "0x" + hex[1] + hex[2];
                g = "0x" + hex[3] + hex[4];
                b = "0x" + hex[5] + hex[6];
            }
            
            r /= 255;
            g /= 255;
            b /= 255;
            
            const cmin = Math.min(r,g,b),
                  cmax = Math.max(r,g,b),
                  delta = cmax - cmin;
            
            let h = 0, s = 0, l = 0;
            
            if (delta == 0) {
                h = 0;
            } else if (cmax == r) {
                h = ((g - b) / delta) % 6;
            } else if (cmax == g) {
                h = (b - r) / delta + 2;
            } else {
                h = (r - g) / delta + 4;
            }
            
            h = Math.round(h * 60);
            if (h < 0) h += 360;
            
            l = (cmax + cmin) / 2;
            s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
            s = +(s * 100).toFixed(1);
            l = +(l * 100).toFixed(1);
            
            return { h, s, l };
        }
        
        function hslToHex(h, s, l) {
            h /= 360;
            s /= 100;
            l /= 100;
            
            let r, g, b;
            
            if (s === 0) {
                r = g = b = l;
            } else {
                const hue2rgb = (p, q, t) => {
                    if (t < 0) t += 1;
                    if (t > 1) t -= 1;
                    if (t < 1/6) return p + (q - p) * 6 * t;
                    if (t < 1/2) return q;
                    if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                    return p;
                };
                
                const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                const p = 2 * l - q;
                r = hue2rgb(p, q, h + 1/3);
                g = hue2rgb(p, q, h);
                b = hue2rgb(p, q, h - 1/3);
            }
            
            const toHex = x => {
                const hex = Math.round(x * 255).toString(16);
                return hex.length === 1 ? "0" + hex : hex;
            };
            
            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }
    });
});
</script>