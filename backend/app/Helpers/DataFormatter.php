<?php

namespace App\Helpers;

class DataFormatter
{
    /**
     * Mask email keeping 3-4 chars, replacing the rest with *, preserving domain.
     */
    public static function maskEmail(?string $email): string
    {
        if (!$email) {
            return 'Anonymous';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email; // fallback for invalid email formats
        }

        $username = $parts[0];
        $domain = $parts[1];

        $keep = 4;
        if (strlen($username) <= 4) {
            $keep = 1; 
        }

        $maskedUsername = substr($username, 0, $keep) . str_repeat('*', max(1, strlen($username) - $keep));

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Convert full program names into abbreviations.
     */
    public static function abbreviateProgram(?string $programName): string
    {
        if (!$programName) {
            return '—';
        }

        $clean = trim($programName);
        $lower = strtolower($clean);
        
        if (str_starts_with($lower, 'bachelor of science in ')) {
            $major = substr($clean, 23); 
            return 'BS' . self::getInitials($major);
        }
        
        if (str_starts_with($lower, 'bachelor of arts in ')) {
            $major = substr($clean, 20); 
            return 'BA' . self::getInitials($major);
        }

        return $clean;
    }

    private static function getInitials(string $string): string
    {
        $words = explode(' ', $string);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        return $initials;
    }
}
