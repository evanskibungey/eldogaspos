<?php

namespace Tests\Unit;

use App\Services\Sms\SmsService;
use PHPUnit\Framework\TestCase;

/**
 * Segment counting drives the cost estimate shown before a campaign is sent,
 * so an undercount here understates the bill on every bulk send.
 */
class SmsSegmentTest extends TestCase
{
    public function test_a_short_gsm_message_is_one_segment(): void
    {
        $this->assertSame(1, SmsService::segments('Thanks for your purchase.'));
    }

    public function test_an_empty_message_still_counts_as_one(): void
    {
        $this->assertSame(1, SmsService::segments(''));
    }

    public function test_gsm_messages_split_every_160_characters(): void
    {
        $this->assertSame(1, SmsService::segments(str_repeat('a', 160)));
        $this->assertSame(2, SmsService::segments(str_repeat('a', 161)));
        $this->assertSame(3, SmsService::segments(str_repeat('a', 321)));
    }

    /**
     * A single non-GSM character drops the whole message to the 70-character
     * unicode limit, which is where surprise bills come from.
     */
    public function test_a_unicode_character_drops_the_limit_to_70(): void
    {
        $this->assertSame(1, SmsService::segments(str_repeat('a', 70)));

        $unicode = str_repeat('a', 70) . '£';
        $this->assertSame(2, SmsService::segments($unicode));
    }
}
