<?php

declare(strict_types=1);

namespace Bancard\Tests\Util;

use Bancard\Tests\TestCase;
use Bancard\Util\Token;

class TokenTest extends TestCase
{
    /**
     * Make returns md 5 of concatenated args.
     */
    public function testMakeReturnsMd5OfConcatenatedArgs(): void
    {
        $this->assertSame(md5('abc'), Token::make('a', 'b', 'c'));
    }

    /**
     * Make with single arg.
     */
    public function testMakeWithSingleArg(): void
    {
        $this->assertSame(md5('hello'), Token::make('hello'));
    }

    /**
     * Make with no args.
     */
    public function testMakeWithNoArgs(): void
    {
        $this->assertSame(md5(''), Token::make());
    }

    /**
     * Token: make is deterministic.
     */
    public function testMakeIsDeterministic(): void
    {
        $this->assertSame(Token::make('a', 'b'), Token::make('a', 'b'));
    }

    /**
     * Make returns 32 char hex.
     */
    public function testMakeReturns32CharHex(): void
    {
        $token = Token::make('test');
        $this->assertSame(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
    }
}
