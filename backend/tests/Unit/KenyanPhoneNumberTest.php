<?php

namespace Tests\Unit;

use App\Support\KenyanPhoneNumber;
use InvalidArgumentException;
use Tests\TestCase;

class KenyanPhoneNumberTest extends TestCase
{
    public function test_it_normalizes_supported_kenyan_mobile_formats(): void
    {
        foreach ([
            '0716933897' => '254716933897',
            '0113920136' => '254113920136',
            '254716933897' => '254716933897',
            '+254113920136' => '254113920136',
        ] as $input => $expected) {
            $this->assertSame($expected, KenyanPhoneNumber::normalize($input));
        }
    }

    public function test_it_rejects_invalid_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        KenyanPhoneNumber::normalize('12345abc');
    }

    public function test_it_masks_and_hashes_without_exposing_the_phone(): void
    {
        $phone = '254716933897';
        $this->assertSame('2547****897', KenyanPhoneNumber::mask($phone));
        $this->assertSame(64, strlen(KenyanPhoneNumber::hash($phone)));
        $this->assertStringNotContainsString($phone, KenyanPhoneNumber::hash($phone));
    }
}
