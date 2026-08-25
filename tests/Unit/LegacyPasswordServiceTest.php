<?php

namespace Tests\Unit;

use App\Services\LegacyPasswordService;
use Tests\TestCase;

class LegacyPasswordServiceTest extends TestCase
{
    public function test_numeric_password_requires_change(): void
    {
        $service = app(LegacyPasswordService::class);
        $stored = $service->storePlaintext('12345678');

        $this->assertSame('12345678', $stored);
        $this->assertTrue($service->matches('12345678', $stored));
        $this->assertTrue($service->requiresChange('12345678', $stored));
    }

    public function test_legacy_plaintext_numeric_password_requires_change(): void
    {
        $service = app(LegacyPasswordService::class);

        $this->assertTrue($service->matches('123', '123'));
        $this->assertTrue($service->requiresChange('123', '123'));
    }

    public function test_non_numeric_plaintext_password_does_not_require_change(): void
    {
        $service = app(LegacyPasswordService::class);
        $stored = $service->storePlaintext('Strong!23Pwd');

        $this->assertSame('Strong!23Pwd', $stored);
        $this->assertTrue($service->matches('Strong!23Pwd', $stored));
        $this->assertFalse($service->requiresChange('Strong!23Pwd', $stored));
    }
}
