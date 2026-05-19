<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ApiEncryptionService
{
    protected ?string $keyHex;
    protected ?string $keyBin = null;

    public function __construct()
    {
        $this->keyHex = config('app.api_encryption_key') ?: env('API_ENCRYPTION_KEY');
        if ($this->keyHex) {
            $this->keyBin = hex2bin($this->keyHex);
        }
    }

    /**
     * Encrypt data to send to the frontend
     * 
     * @param mixed $data
     * @return string Base64 encoded IV + Ciphertext + Tag
     * @throws Exception
     */
    public function encrypt($data): string
    {
        if (!$this->keyBin) {
            throw new Exception('API Encryption Key is not set or invalid.');
        }

        $plaintext = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($plaintext === false) {
            throw new Exception('JSON encoding failed: ' . json_last_error_msg());
        }

        // 12 bytes IV is standard for AES-GCM
        $iv = random_bytes(12);

        // OpenSSL GCM encryption. $tag is populated automatically by reference.
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->keyBin,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length is 16 bytes (128 bits)
        );

        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        // Concatenate IV + Ciphertext + Tag
        $combined = $iv . $ciphertext . $tag;

        return base64_encode($combined);
    }

    /**
     * Decrypt data received from the frontend
     * 
     * @param string $base64Payload
     * @return mixed Decoded JSON data
     * @throws Exception
     */
    public function decrypt(string $base64Payload)
    {
        if (!$this->keyBin) {
            throw new Exception('API Encryption Key is not set or invalid.');
        }

        $combined = base64_decode($base64Payload);
        if ($combined === false) {
            throw new Exception('Base64 decode failed.');
        }

        if (strlen($combined) < 28) { // 12 bytes IV + 16 bytes Tag
            throw new Exception('Payload is too short.');
        }

        // Extract components:
        // IV is the first 12 bytes
        $iv = substr($combined, 0, 12);
        // Tag is the last 16 bytes
        $tag = substr($combined, -16);
        // Ciphertext is everything in between
        $ciphertext = substr($combined, 12, -16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->keyBin,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed. Authentication tag validation may have failed.');
        }

        $decoded = json_decode($decrypted, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode failed: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
