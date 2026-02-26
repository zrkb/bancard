<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Amount;

class AmountTest extends TestCase
{
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
