<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * DataEncryptionService
 * 
 * Handles encryption of sensitive data:
 * - PII encryption/decryption
 * - Field-level encryption
 * - Secure data handling
 */
class DataEncryptionService
{
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data): string
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        return Crypt::encryptString($data);
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $encrypted, bool $asArray = false)
    {
        try {
            $decrypted = Crypt::decryptString($encrypted);
            
            if ($asArray) {
                return json_decode($decrypted, true);
            }
            
            return $decrypted;
        } catch (DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt specific fields in an array
     */
    public function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypt specific fields in an array
     */
    public function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Hash sensitive data (one-way)
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data . config('app.key'));
    }

    /**
     * Verify hashed data
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($this->hash($data), $hash);
    }

    /**
     * Mask sensitive data for display
     */
    public function mask(string $data, int $visibleStart = 4, int $visibleEnd = 4, string $maskChar = '*'): string
    {
        $length = strlen($data);
        
        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat($maskChar, $length);
        }

        $maskLength = $length - $visibleStart - $visibleEnd;
        
        return substr($data, 0, $visibleStart) 
            . str_repeat($maskChar, $maskLength) 
            . substr($data, -$visibleEnd);
    }

    /**
     * Mask email address
     */
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        
        if (count($parts) !== 2) {
            return $this->mask($email);
        }

        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = strlen($name) > 2 
            ? substr($name, 0, 1) . str_repeat('*', strlen($name) - 2) . substr($name, -1)
            : str_repeat('*', strlen($name));

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask phone number
     */
    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($digits) < 4) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', strlen($digits) - 4) . substr($digits, -4);
    }

    /**
     * Generate secure random token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
