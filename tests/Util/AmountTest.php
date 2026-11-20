<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Amount;
use PHPUnit\Framework\Attributes\DataProvider;

class AmountTest extends TestCase
{
    /**
     * Valid amounts and their normalized form.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function validAmounts(): array
    {
        return [
            'int' => [10000, '10000.00'],
            'zero int' => [0, '0.00'],
            'float' => [10000.5, '10000.50'],
            'integer string' => ['10000', '10000.00'],
            'one decimal string' => ['10000.5', '10000.50'],
            'two decimal string' => ['10000.55', '10000.55'],
            'already normalized' => ['0.00', '0.00'],
            'large string, no float rounding' => ['12345678901234567.89', '12345678901234567.89'],
        ];
    }

    /**
     * Invalid amounts that must be rejected.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'thousands separator' => ['10,000'],
            'comma decimal' => ['10000,50'],
            'three decimals' => ['1.005'],
            'negative string' => ['-1.00'],
            'negative int' => [-1],
            'negative float' => [-0.5],
            'non numeric' => ['abc'],
            'empty string' => [''],
            'trailing dot' => ['10.'],
            'whitespace' => [' 10.00'],
            'infinite float' => [INF],
            'nan' => [NAN],
            'bool' => [true],
            'null' => [null],
        ];
    }

    /**
     * normalize() formats valid amounts to two decimals.
     */
    #[DataProvider('validAmounts')]
    public function testNormalizeValidAmounts(mixed $input, string $expected): void
    {
        $this->assertSame($expected, Amount::normalize($input));
    }

    /**
     * normalize() returns null for invalid amounts instead of rounding or guessing.
     */
    #[DataProvider('invalidAmounts')]
    public function testNormalizeRejectsInvalidAmounts(mixed $input): void
    {
        $this->assertNull(Amount::normalize($input));
    }

    public function testFormatFloat(): void
    {
        $this->assertSame('10000.00', Amount::format(10000));
        $this->assertSame('10000.50', Amount::format(10000.5));
        $this->assertSame('10000.55', Amount::format(10000.55));
    }

    public function testFormatString(): void
    {
        $this->assertSame('10000.00', Amount::format('10000'));
        $this->assertSame('10000.50', Amount::format('10000.5'));
        $this->assertSame('10000.55', Amount::format('10000.55'));
    }

    public function testFormatZero(): void
    {
        $this->assertSame('0.00', Amount::format(0));
        $this->assertSame('0.00', Amount::format(0.0));
        $this->assertSame('0.00', Amount::format('0'));
    }

    public function testFormatNoThousandsSeparator(): void
    {
        $this->assertSame('1000000.00', Amount::format(1000000));
    }

    public function testFormatTruncatesExtraDecimals(): void
    {
        $this->assertSame('10.12', Amount::format(10.123));
    }
}
