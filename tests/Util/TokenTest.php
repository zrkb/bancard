<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Token;

class TokenTest extends TestCase
{
    public function testMakeReturnsMd5OfConcatenatedArgs(): void
    {
        $this->assertSame(md5('abc'), Token::make('a', 'b', 'c'));
    }

    public function testMakeWithSingleArg(): void
    {
        $this->assertSame(md5('hello'), Token::make('hello'));
    }

    public function testMakeWithNoArgs(): void
    {
        $this->assertSame(md5(''), Token::make());
    }

    public function testMakeIsDeterministic(): void
    {
        $this->assertSame(Token::make('a', 'b'), Token::make('a', 'b'));
    }

    public function testMakeReturns32CharHex(): void
    {
        $token = Token::make('test');
        $this->assertSame(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
    }
}
