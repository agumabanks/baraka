<?php

namespace App\Services\Security;

use App\Models\Security\SecurityEncryptionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Exception;

class EncryptionService
{
    private SecurityEncryptionKey $masterKey;
    private SecurityEncryptionKey $dataKey;

    public function __construct()
    {
        $this->loadActiveKeys();
    }

    /**
     * Load active encryption keys
     */
    private function loadActiveKeys(): void
    {
        $this->masterKey = SecurityEncryptionKey::byType('master')
            ->active()
            ->firstOrFail();

        $this->dataKey = SecurityEncryptionKey::byType('data_encryption')
            ->active()
            ->firstOrFail();
    }

    /**
     * Encrypt sensitive data using AES-256-GCM
     */
    public function encryptData(string $data, string $context = 'general'): string
    {
        try {
            // Use the data encryption key
            $key = $this->getDecryptedKey($this->dataKey);
            
            // Create a unique IV for each encryption
            $iv = random_bytes(16);
            
            // Encrypt using OpenSSL with AES-256-GCM
            $encrypted = openssl_encrypt(
                $data, 
                'AES-256-GCM', 
                base64_decode($key), 
                0, 
                $iv, 
                $tag
            );

            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }

            // Combine IV, encrypted data, and auth tag
            $result = base64_encode($iv . $encrypted . $tag);

            // Log the encryption event
            $this->logEncryptionEvent('data_encrypted', $context, $data);

            return $result;

        } catch (Exception $e) {
            Log::error('Data encryption failed', [
                'error' => $e->getMessage(),
                'context' => $context,
                'user_id' => auth()->id(),
            ]);
            throw new Exception('Failed to encrypt data');
        }
    }

    /**
     * Decrypt sensitive data
     */
    public function decryptData(string $encryptedData, string $context = 'general'): string
    {
        try {
            $key = $this->getDecryptedKey($this->dataKey);
            
            // Decode the combined data
            $data = base64_decode($encryptedData);
            
            // Extract IV (first 16 bytes), encrypted data, and auth tag (last 16 bytes)
            $iv = substr($data, 0, 16);
            $tag = substr($data, -16);
            $encrypted = substr($data, 16, -16);
            
            // Decrypt
            $decrypted = openssl_decrypt(
                $encrypted, 
                'AES-256-GCM', 
                base64_decode($key), 
                0, 
                $iv, 
                $tag
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }

            // Log the decryption event
            $this->logEncryptionEvent('data_decrypted', $context, $decrypted);

            return $decrypted;

        } catch (Exception $e) {
            Log::error('Data decryption failed', [
                'error' => $e->getMessage(),
                'context' => $context,
                'user_id' => auth()->id(),
            ]);
            throw new Exception('Failed to decrypt data');
        }
    }

    /**
     * Hash sensitive data using HMAC
     */
    public function hashData(string $data, string $context = 'general'): string
    {
        $key = $this->getDecryptedKey($this->masterKey);
        $hash = hash_hmac('sha256', $data, base64_decode($key), true);
        
        // Log the hashing event
        $this->logEncryptionEvent('data_hashed', $context, $data);
        
        return base64_encode($hash);
    }

    /**
     * Generate secure random token
     */
    public function generateSecureToken(int $length = 32): string
    {
        $token = random_bytes($length);
        
        // Log token generation
        Log::info('Secure token generated', [
            'length' => $length,
            'context' => 'token_generation',
            'user_id' => auth()->id(),
        ]);
        
        return base64_encode($token);
    }

    /**
     * Encrypt financial data with additional security
     */
    public function encryptFinancialData(array $data): string
    {
        // Add additional context and security measures for financial data
        $secureData = [
            'data' => $data,
            'timestamp' => now()->timestamp,
            'context' => 'financial',
            'user_id' => auth()->id(),
        ];

        return $this->encryptData(json_encode($secureData), 'financial');
    }

    /**
     * Decrypt financial data
     */
    public function decryptFinancialData(string $encryptedData): array
    {
        $decrypted = $this->decryptData($encryptedData, 'financial');
        $data = json_decode($decrypted, true);
        
        // Verify the data structure
        if (!isset($data['data']) || !isset($data['timestamp'])) {
            throw new Exception('Invalid financial data structure');
        }
        
        return $data['data'];
    }

    /**
     * Get decrypted key from stored key record
     */
    private function getDecryptedKey(SecurityEncryptionKey $key): string
    {
        // In production, this would use a proper key management service
        // For now, we'll assume keys are stored encrypted
        $keyValue = Crypt::decryptString($key->key_value);
        
        return $keyValue;
    }

    /**
     * Log encryption events for audit
     */
    private function logEncryptionEvent(string $event, string $context, string $data): void
    {
        Log::channel('security')->info($event, [
            'context' => $context,
            'data_length' => strlen($data),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Rotate encryption keys
     */
    public function rotateKeys(): array
    {
        try {
            $oldMasterKey = $this->masterKey;
            $oldDataKey = $this->dataKey;

            // Mark old keys as inactive
            $oldMasterKey->update(['status' => 'inactive']);
            $oldDataKey->update(['status' => 'inactive']);

            // Create new keys
            $newMasterKey = $this->createNewKey('master');
            $newDataKey = $this->createNewKey('data_encryption');

            // Reload keys
            $this->loadActiveKeys();

            Log::channel('security')->info('Encryption keys rotated', [
                'old_master_key_id' => $oldMasterKey->id,
                'new_master_key_id' => $newMasterKey->id,
                'old_data_key_id' => $oldDataKey->id,
                'new_data_key_id' => $newDataKey->id,
                'rotated_by' => auth()->id(),
            ]);

            return [
                'master_key' => $newMasterKey,
                'data_key' => $newDataKey,
            ];

        } catch (Exception $e) {
            Log::error('Key rotation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            throw new Exception('Failed to rotate encryption keys');
        }
    }

    /**
     * Create a new encryption key
     */
    private function createNewKey(string $type): SecurityEncryptionKey
    {
        $keyValue = $this->generateSecureKey();
        
        return SecurityEncryptionKey::create([
            'key_name' => $type . '_' . uniqid(),
            'key_type' => $type,
            'key_value' => Crypt::encryptString($keyValue),
            'algorithm' => 'AES-256-GCM',
            'key_length' => 256,
            'expires_at' => now()->addYear(), // Keys expire in 1 year
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Generate a secure encryption key
     */
    private function generateSecureKey(): string
    {
        return base64_encode(random_bytes(32)); // 256-bit key
    }
}