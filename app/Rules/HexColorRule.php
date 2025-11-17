<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Custom validation rule for hex color formats
 * 
 * Validates that a string is a proper hex color format:
 * - #RGB (3 hex digits)
 * - #RRGGBB (6 hex digits)
 * - Supports both uppercase and lowercase
 */
class HexColorRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        // Remove any whitespace
        $value = trim($value);

        // Must start with #
        if (!str_starts_with($value, '#')) {
            return false;
        }

        // Extract the hex part (without #)
        $hex = substr($value, 1);

        // Check if it's valid hex format (3 or 6 characters, alphanumeric only)
        if (!preg_match('/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/', $hex)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('settings.validation.hex_color_format');
    }
}