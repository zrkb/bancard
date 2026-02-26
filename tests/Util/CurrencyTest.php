<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Currency;

class CurrencyTest extends TestCase
{
    public function testPygCase(): void
    {
        $this->assertSame('PYG', Currency::PYG->value);
    }

    public function testUsdCase(): void
    {
        $this->assertSame('USD', Currency::USD->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(Currency::PYG, Currency::from('PYG'));
        $this->assertSame(Currency::USD, Currency::from('USD'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(Currency::tryFrom('EUR'));
    }
}
