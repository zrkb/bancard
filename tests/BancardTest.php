<?php

namespace Bancard\Tests;

use Bancard\Bancard;

class BancardTest extends TestCase
{
    public function testSetAndGetPrivateKey(): void
    {
        Bancard::setPrivateKey('my_private_key');
        $this->assertSame('my_private_key', Bancard::privateKey());
    }

    public function testSetAndGetPublicKey(): void
    {
        Bancard::setPublicKey('my_public_key');
        $this->assertSame('my_public_key', Bancard::publicKey());
    }

    public function testSetAndGetStaging(): void
    {
        Bancard::setStaging(true);
        $this->assertTrue(Bancard::staging());

        Bancard::setStaging(false);
        $this->assertFalse(Bancard::staging());
    }

    public function testStagingDefaultsToFalse(): void
    {
        // setUp sets staging to false, matching the class default
        $this->assertFalse(Bancard::staging());
    }

    public function testBaseUriReturnsProductionUrl(): void
    {
        Bancard::setStaging(false);
        $bancard = new Bancard();
        $this->assertSame('https://vpos.infonet.com.py/', $bancard->baseUri());
    }

    public function testBaseUriReturnsStagingUrl(): void
    {
        Bancard::setStaging(true);
        $bancard = new Bancard();
        $this->assertSame('https://vpos.infonet.com.py:8888/', $bancard->baseUri());
    }
}
