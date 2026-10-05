<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\PhoneExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneExtractorTest extends TestCase
{
    public static function formats(): array
    {
        return [
            'local'               => ['01711223344'],
            'local with dash'     => ['01711-223344'],
            'spaces'              => ['017 1122 3344'],
            'plus country code'   => ['+8801711223344'],
            'plus with spaces'    => ['+880 1711 223344'],
            'bare country code'   => ['8801711223344'],
            'double zero'         => ['008801711223344'],
            'brackets'            => ['(+88) 01711-223344'],
            'dots'                => ['01711.223.344'],
            'bangla digits'       => ['০১৭১১২২৩৩৪৪'],
            'inside a sentence'   => ['amar number 01711223344 apu'],
        ];
    }

    #[DataProvider('formats')]
    public function test_bangladesh_formats_normalize_to_the_stored_national_number(string $input): void
    {
        $found = PhoneExtractor::find($input);

        $this->assertCount(1, $found);
        $this->assertSame('1711223344', $found[0]['national']);
        $this->assertSame('01711223344', PhoneExtractor::local($found[0]['national']));
    }

    public function test_not_a_mobile_number_is_ignored(): void
    {
        $this->assertSame([], PhoneExtractor::find('order 123456789012'));
        $this->assertSame([], PhoneExtractor::find('01211223344'));   // 012 isn't a BD mobile prefix
        $this->assertSame([], PhoneExtractor::find('total 2450 tk'));
    }

    public function test_numbers_on_separate_lines_or_next_to_amounts_stay_apart(): void
    {
        $found = PhoneExtractor::find("01711223344\n01811223344 1200tk");

        $this->assertSame(['1711223344', '1811223344'], array_column($found, 'national'));
    }

    public function test_strip_removes_numbers_from_text(): void
    {
        $this->assertSame('Rahim   Dhanmondi', PhoneExtractor::strip('Rahim 01711-223344 Dhanmondi'));
    }
}
