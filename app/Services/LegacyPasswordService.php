<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class LegacyPasswordService
{
    public function matches(string $plainText, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        try {
            if (Hash::check($plainText, $storedPassword)) {
                return true;
            }
        } catch (\Throwable) {
            // Legacy installations may contain non-hash values during migration.
        }

        // Temporary migration compatibility: any successful plaintext login is
        // immediately marked for password replacement by requiresChange().
        return hash_equals($storedPassword, $plainText);
    }

    public function requiresChange(string $plainText, string $storedPassword): bool
    {
        // The agreed legacy policy treats an all-numeric password as the
        // default/weak password that must be replaced after login.
        return ctype_digit($plainText);
    }

    /**
     * Compatibility storage requested for the legacy VB.NET application.
     * This deliberately returns plaintext and must not be reused elsewhere.
     */
    public function storePlaintext(string $plainText): string
    {
        return $plainText;
    }
}
