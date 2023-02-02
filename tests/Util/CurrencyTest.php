<?php

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Currency;

class CurrencyTest extends TestCase
{
    public function testPygConstant(): void
    {
        $this->assertSame('PYG', Currency::PYG);
    }

    public function testUsdConstant(): void
    {
        $this->assertSame('USD', Currency::USD);
    }
}
