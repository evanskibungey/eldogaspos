<?php

namespace Tests\Unit;

use App\Services\Sms\PhoneNumber;
use PHPUnit\Framework\TestCase;

/**
 * Phone numbers are stored as free text, so normalisation is the only thing
 * standing between the customer table and messages billed to numbers that
 * cannot receive them.
 */
class PhoneNumberTest extends TestCase
{
    /**
     * @dataProvider sendableNumbers
     */
    public function test_it_normalises_kenyan_numbers_to_msisdn(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalise($input));
    }

    public static function sendableNumbers(): array
    {
        return [
            'local safaricom' => ['0712345678', '254712345678'],
            'local airtel 01x' => ['0112345678', '254112345678'],
            'international with plus' => ['+254712345678', '254712345678'],
            'international without plus' => ['254712345678', '254712345678'],
            'nine digits only' => ['712345678', '254712345678'],
            'spaces' => ['0712 345 678', '254712345678'],
            'dashes' => ['0712-345-678', '254712345678'],
            'brackets and spaces' => ['(+254) 712 345 678', '254712345678'],
        ];
    }

    /**
     * @dataProvider unsendableNumbers
     */
    public function test_it_rejects_numbers_that_cannot_receive_sms(?string $input): void
    {
        $this->assertNull(PhoneNumber::normalise($input));
        $this->assertFalse(PhoneNumber::isSendable($input));
    }

    public static function unsendableNumbers(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'letters only' => ['not a phone'],
            'too short' => ['0712345'],
            'too long' => ['0712345678901234'],
            'landline prefix' => ['0202345678'],
            'wrong country' => ['+447712345678'],
            'all zeroes' => ['0000000000'],
        ];
    }

    /**
     * The POS creates a real customer row for cash sales using this placeholder.
     * Treating it as sendable would bill a message on nearly every sale.
     */
    public function test_it_rejects_the_pos_walk_in_placeholder(): void
    {
        $this->assertNull(PhoneNumber::normalise(PhoneNumber::WALK_IN));
    }
}
