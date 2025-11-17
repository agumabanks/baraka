<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Custom validation rule for currency codes
 * 
 * Validates that a string is a valid ISO 4217 currency code
 */
class CurrencyCodeRule implements Rule
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

        // Remove any whitespace and convert to uppercase
        $value = trim(strtoupper($value));

        // Must be 3 characters
        if (strlen($value) !== 3) {
            return false;
        }

        // Must be alphabetic only
        if (!ctype_alpha($value)) {
            return false;
        }

        // Common currency codes validation
        $validCurrencies = [
            'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD',
            'MXN', 'SGD', 'HKD', 'NOK', 'INR', 'KRW', 'TRY', 'RUB', 'BRL', 'ZAR',
            'UGX', 'KES', 'TZS', 'RWF', 'GHS', 'NGN', 'MAD', 'EGP', 'DZD', 'TND',
            'AOA', 'ZMW', 'BWP', 'MUR', 'MZN', 'SCR', 'NA', 'SZ', 'LS', 'MW', 'ZM'
        ];

        return in_array($value, $validCurrencies);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('settings.validation.currency_code_invalid');
    }
}