<?php

namespace App\Services\Sms;

/**
 * Normalises Kenyan phone numbers into the msisdn form the SMS gateway expects.
 *
 * Numbers reach us as free text (`customers.phone` is an unvalidated string), so
 * the same person can be stored as "0712 345 678", "+254712345678" or
 * "254712345678". The gateway only accepts one of those, and a malformed number
 * is billed as a failed send, so everything is funnelled through here before it
 * leaves the application.
 */
class PhoneNumber
{
    /** Placeholder used by the POS for cash sales with no real customer. */
    public const WALK_IN = '0000000000';

    /**
     * @return string|null msisdn (254XXXXXXXXX), or null when the input cannot
     *                     be a real Kenyan mobile number.
     */
    public static function normalise(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Strip everything the gateway would reject: spaces, dashes, brackets,
        // and a leading + (we re-add it only if the gateway is configured for it).
        $digits = preg_replace('/[^0-9]/', '', $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        // The walk-in placeholder is a real row in `customers`, so it would
        // otherwise be treated as a sendable number.
        if ($digits === self::WALK_IN || (int) $digits === 0) {
            return null;
        }

        // 0712345678 -> 712345678
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // 254712345678 -> 712345678, so every shape converges before validation.
        if (strlen($digits) === 12 && str_starts_with($digits, '254')) {
            $digits = substr($digits, 3);
        }

        // Safaricom/Airtel/Telkom mobile prefixes are 7XX and 1XX, nine digits.
        if (!preg_match('/^[71][0-9]{8}$/', $digits)) {
            return null;
        }

        return '254' . $digits;
    }

    /**
     * The normalised number in the wire format configured for the gateway.
     * Some installs of the platform want a leading +, others reject it.
     */
    public static function forGateway(?string $raw): ?string
    {
        $msisdn = self::normalise($raw);

        if ($msisdn === null) {
            return null;
        }

        return config('services.talksasa.plus_prefix') ? '+' . $msisdn : $msisdn;
    }

    public static function isSendable(?string $raw): bool
    {
        return self::normalise($raw) !== null;
    }

    /**
     * Every spelling of a number that could already be stored against a
     * customer.
     *
     * `customers.phone` is free text with a unique index, so the same person
     * may be on file as "0712345678", "254712345678" or "+254712345678".
     * Looking a customer up by one spelling misses the others, which is how a
     * single person ends up as several records - and how creating a customer
     * that already exists hits the unique index instead of reusing the row.
     *
     * The raw input is always included, so numbers this class cannot parse
     * (landlines, foreign numbers) still match themselves exactly.
     *
     * @return string[]
     */
    public static function variants(?string $raw): array
    {
        $trimmed = trim((string) $raw);
        $found = $trimmed === '' ? [] : [$trimmed];

        $msisdn = self::normalise($raw);

        if ($msisdn !== null) {
            $local = substr($msisdn, 3);

            $found[] = $msisdn;              // 254712345678
            $found[] = '+' . $msisdn;        // +254712345678
            $found[] = '0' . $local;         // 0712345678
            $found[] = $local;               // 712345678
        }

        return array_values(array_unique($found));
    }
}
