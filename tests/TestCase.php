<?php

namespace Bancard\Tests;

use Bancard\Bancard;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionProperty;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Bancard::setPrivateKey('test_private_key');
        Bancard::setPublicKey('test_public_key');
        Bancard::setStaging(false);
    }

    protected function tearDown(): void
    {
        Bancard::setPrivateKey('');
        Bancard::setPublicKey('');
        Bancard::setStaging(false);
        parent::tearDown();
    }

    /**
     * @return mixed
     */
    protected function getProtectedProperty(object $object, string $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }
}
