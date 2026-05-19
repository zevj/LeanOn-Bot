<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ApiEncryptionService;

class ApiEncryptionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set up the environment key for testing
        putenv('API_ENCRYPTION_KEY=7b72809e2e698bb2c24f686d0b60584b4237d1cfd1d5df3f38eb45d614a90f14');
    }

    public function test_encryption_and_decryption(): void
    {
        $service = new ApiEncryptionService();
        $testData = ['message' => 'Hello World!', 'status' => true, 'count' => 42];

        // Encrypt
        $encrypted = $service->encrypt($testData);
        $this->assertNotEmpty($encrypted);
        $this->assertIsString($encrypted);

        // Decrypt
        $decrypted = $service->decrypt($encrypted);
        $this->assertEquals($testData, $decrypted);
    }

    public function test_decryption_failure_on_tampered_payload(): void
    {
        $service = new ApiEncryptionService();
        $testData = ['sensitive' => 'data'];

        $encrypted = $service->encrypt($testData);
        
        // Decode and tamper
        $bytes = base64_decode($encrypted);
        // Tamper with ciphertext (change a byte in the middle)
        $bytes[15] = chr(ord($bytes[15]) ^ 0xff);
        $tampered = base64_encode($bytes);

        $this->expectException(\Exception::class);
        $service->decrypt($tampered);
    }
}
