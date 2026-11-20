<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Currency;

class CurrencyTest extends TestCase
{
    /**
     * Currency: pYG case.
     */
    public function testPygCase(): void
    {
        $this->assertSame('PYG', Currency::PYG->value);
    }

    /**
     * Currency: uSD case.
     */
    public function testUsdCase(): void
    {
        $this->assertSame('USD', Currency::USD->value);
    }

    /**
     * Currency: from string.
     */
    public function testFromString(): void
    {
        $this->assertSame(Currency::PYG, Currency::from('PYG'));
        $this->assertSame(Currency::USD, Currency::from('USD'));
    }

    /**
     * Try from invalid returns null.
     */
    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(Currency::tryFrom('EUR'));
    }
}
