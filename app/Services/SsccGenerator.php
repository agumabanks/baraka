<?php

namespace App\Services;

class SsccGenerator
{
    /**
     * Generate GS1 SSCC (Serial Shipping Container Code)
     * Format: (00) + 18 digits + check digit
     * Total: 20 digits
     *
     * @param  int|null  $companyPrefix  GS1 company prefix (usually 7-10 digits)
     */
    public static function generate(?int $companyPrefix = null): string
    {
        // Use default company prefix if not provided
        $companyPrefix = $companyPrefix ?? config('gs1.company_prefix', '123456789');

        // Generate serial number (remaining digits after company prefix)
        $serialLength = 17 - strlen($companyPrefix); // 17 because 18 total - 1 check digit
        $serial = self::generateRandomDigits($serialLength);

        // Combine company prefix and serial
        $ssccWithoutCheck = $companyPrefix.$serial;

        // Calculate check digit
        $checkDigit = self::calculateCheckDigit($ssccWithoutCheck);

        // Return full SSCC with GS1 Application Identifier (00)
        return '(00)'.$ssccWithoutCheck.$checkDigit;
    }

    /**
     * Generate random digits
     */
    private static function generateRandomDigits(int $length): string
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= mt_rand(0, 9);
        }

        return $digits;
    }

    /**
     * Calculate GS1 check digit using modulo 10 algorithm
     */
    private static function calculateCheckDigit(string $number): int
    {
        $sum = 0;
        $length = strlen($number);

        // Process digits from right to left
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            // Alternate multiplication by 3
            if (($length - 1 - $i) % 2 === 0) {
                $digit *= 3;
            }

            $sum += $digit;
        }

        // Find the smallest number that makes sum divisible by 10
        $remainder = $sum % 10;

        return $remainder === 0 ? 0 : 10 - $remainder;
    }

    /**
     * Validate SSCC format and check digit
     */
    public static function validate(string $sscc): bool
    {
        // Remove GS1 AI if present
        $sscc = str_replace(['(00)', '(00)'], '', $sscc);

        if (strlen($sscc) !== 18) {
            return false;
        }

        $number = substr($sscc, 0, 17);
        $checkDigit = (int) substr($sscc, 17, 1);

        return self::calculateCheckDigit($number) === $checkDigit;
    }

    /**
     * Extract clean SSCC without GS1 AI
     */
    public static function clean(string $sscc): string
    {
        return str_replace(['(00)', '(00)'], '', $sscc);
    }

    /**
     * Format SSCC for barcode (without GS1 AI)
     */
    public static function forBarcode(string $sscc): string
    {
        return self::clean($sscc);
    }

    /**
     * Format SSCC for human display (with GS1 AI)
     */
    public static function forDisplay(string $sscc): string
    {
        $clean = self::clean($sscc);
        if (strlen($clean) === 18) {
            return '(00)'.$clean;
        }

        return $sscc;
    }
}
