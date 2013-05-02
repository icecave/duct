<?php
namespace Icecave\Duct;

use PHPUnit_Framework_TestCase;

/**
 * @covers Icecave\Duct\Token
 * @covers Icecave\Duct\Lexer
 */
class LexerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->lexer = new Lexer;
    }

    public function testFeedEmitsIntegerAfterNonDigit()
    {
        $this->lexer->feed(' 1 ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(TokenType::NUMBER_LITERAL(), $tokens->back()->type());
        $this->assertSame(1, $tokens->back()->value());
    }

    public function testFeedEmitsMultipleIntegers()
    {
        $this->lexer->feed(' 1 2 ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(1, $tokens[0]->value());
        $this->assertSame(2, $tokens[1]->value());
    }

    public function testFeedIntegerThenNonInteger()
    {
        $this->lexer->feed(' 1{ ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(1, $tokens[0]->value());
        $this->assertSame('{', $tokens[1]->value());
    }

    public function testFeedZeroIntegerThenNonInteger()
    {
        $this->lexer->feed(' 0{ ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(0, $tokens[0]->value());
        $this->assertSame('{', $tokens[1]->value());
    }

    public function testFeedFloatThenNonInteger()
    {
        $this->lexer->feed(' 1.1{ ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(1.1, $tokens[0]->value());
        $this->assertSame('{', $tokens[1]->value());
    }

    public function testFeedExponentThenNonInteger()
    {
        $this->lexer->feed(' 1e5{ ');

        $tokens = $this->lexer->tokens();
        $this->assertSame(1e5, $tokens[0]->value());
        $this->assertSame('{', $tokens[1]->value());
    }

    public function testFeedFailsOnInvalidBeginningCharacter()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Unexpected character: "x".');
        $this->lexer->feed('x');
    }

    public function testFeedFailsOnInvalidEscapeSequence()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Invalid escape sequence.');
        $this->lexer->feed('"\\q"');
    }

    public function testFeedFailsOnInvalidUnicodeEscapeSequence()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Invalid escape sequence.');
        $this->lexer->feed('"\\uq"');
    }

    public function testFeedFailsOnNonDigitAfterNegativeSign()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected digit after negative sign.');
        $this->lexer->feed('-q');
    }

    public function testFeedFailsOnNonDigitAfterDecimalPoint()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected digit after decimal point.');
        $this->lexer->feed('1.q');
    }

    public function testFeedFailsOnNonDigitAfterExponentE()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected digit or +/- as exponent.');
        $this->lexer->feed('1eq');
    }

    public function testFeedFailsOnMispelledTrue()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected "true", got "trX".');
        $this->lexer->feed('trX');
    }

    public function testFeedFailsOnMispelledFalse()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected "false", got "faX".');
        $this->lexer->feed('faX');
    }

    public function testFeedFailsOnMispelledNull()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Expected "null", got "nuX".');
        $this->lexer->feed('nuX');
    }

    public function testFeedFailsOnMultipleHighSurrogates()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Multiple high surrogates for unicode surrogate pair.');
        $this->lexer->feed('"\\ud834\\ud834"');
    }

    public function testFeedFailsOnMissingHighSurrogate()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Missing high surrogate for unicode surrogate pair.');
        $this->lexer->feed('"\\udD1E"');
    }

    public function testFeedFailsOnMissingLowSurrogateEndString()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Missing low surrogate for unicode surrogate pair.');
        $this->lexer->feed('"\\ud834"');
    }

    public function testFeedFailsOnMissingLowSurrogateRegularCharacter()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Missing low surrogate for unicode surrogate pair.');
        $this->lexer->feed('"\\ud834_"');
    }

    public function testFeedFailsOnMissingLowSurrogateRegularNonUnicodeEscape()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Missing low surrogate for unicode surrogate pair.');
        $this->lexer->feed('"\\ud834\t"');
    }

    public function testFeedPartialMultibyteCharacter()
    {
        $this->lexer->feed("\"\xc3");

        $this->assertTrue($this->lexer->tokens()->isEmpty());

        $this->lexer->feed("\xb6\"");

        $this->assertSame("\xc3\xb6", $this->lexer->tokens()->back()->value());
    }

    /**
     * @dataProvider partialLiterals
     */
    public function testFinalizeFailsWithPartialLiteral($literal)
    {
        $this->lexer->feed($literal);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\LexerException', 'Character stream ended while scanning literal value.');
        $this->lexer->finalize();
    }

    public function partialLiterals()
    {
        return array(
            array('-'),
            array('1e'),
            array('"foo'),
            array('"foo\\'),
            array('"foo\\uA'),
            array('tru'),
            array('fals'),
            array('nul'),
        );
    }

    /**
     * @dataProvider singleTokens
     */
    public function testLexWithSingleToken($json, $expectedToken)
    {
        $tokens = $this->lexer->lex($json);
        $this->assertInstanceOf('Icecave\Collections\Vector', $tokens);
        $this->assertSame(1, $tokens->size());
        $this->assertEquals($expectedToken, $tokens->back());
        $this->assertSame($expectedToken->value(), $tokens->back()->value());
    }

    public function singleTokens()
    {
        return array(
            array('{',                new Token(TokenType::BRACE_OPEN(), '{')),
            array('}',                new Token(TokenType::BRACE_CLOSE(), '}')),
            array('[',                new Token(TokenType::BRACKET_OPEN(), '[')),
            array(']',                new Token(TokenType::BRACKET_CLOSE(), ']')),
            array(':',                new Token(TokenType::COLON(), ':')),
            array(',',                new Token(TokenType::COMMA(), ',')),

            array('true',             new Token(TokenType::BOOLEAN_LITERAL(), true)),
            array('false',            new Token(TokenType::BOOLEAN_LITERAL(), false)),
            array('null',             new Token(TokenType::NULL_LITERAL(), null)),

            array('0',                new Token(TokenType::NUMBER_LITERAL(), 0)),
            array('-0',               new Token(TokenType::NUMBER_LITERAL(), 0)),
            array('1',                new Token(TokenType::NUMBER_LITERAL(), 1)),
            array('-1',               new Token(TokenType::NUMBER_LITERAL(), -1)),
            array('12345',            new Token(TokenType::NUMBER_LITERAL(), 12345)),
            array('-12345',           new Token(TokenType::NUMBER_LITERAL(), -12345)),

            array('0.0',              new Token(TokenType::NUMBER_LITERAL(), 0.0)),
            array('-0.0',             new Token(TokenType::NUMBER_LITERAL(), 0.0)),
            array('1.1',              new Token(TokenType::NUMBER_LITERAL(), 1.1)),
            array('-1.1',             new Token(TokenType::NUMBER_LITERAL(), -1.1)),
            array('123.123',          new Token(TokenType::NUMBER_LITERAL(), 123.123)),
            array('-123.123',         new Token(TokenType::NUMBER_LITERAL(), -123.123)),

            array('0e5',              new Token(TokenType::NUMBER_LITERAL(), 0e5)),
            array('0E5',              new Token(TokenType::NUMBER_LITERAL(), 0e5)),

            array('1e5',              new Token(TokenType::NUMBER_LITERAL(), 1e5)),
            array('1E5',              new Token(TokenType::NUMBER_LITERAL(), 1e5)),
            array('1e10',             new Token(TokenType::NUMBER_LITERAL(), 1e10)),
            array('1E10',             new Token(TokenType::NUMBER_LITERAL(), 1e10)),

            array('1e+5',             new Token(TokenType::NUMBER_LITERAL(), 1e5)),
            array('1E+5',             new Token(TokenType::NUMBER_LITERAL(), 1e5)),
            array('1e+10',            new Token(TokenType::NUMBER_LITERAL(), 1e10)),
            array('1E+10',            new Token(TokenType::NUMBER_LITERAL(), 1e10)),

            array('1e-5',             new Token(TokenType::NUMBER_LITERAL(), 1e-5)),
            array('1E-5',             new Token(TokenType::NUMBER_LITERAL(), 1e-5)),
            array('1e-10',            new Token(TokenType::NUMBER_LITERAL(), 1e-10)),
            array('1E-10',            new Token(TokenType::NUMBER_LITERAL(), 1e-10)),

            array('0.1e10',           new Token(TokenType::NUMBER_LITERAL(), 0.1e10)),
            array('0.1E10',           new Token(TokenType::NUMBER_LITERAL(), 0.1e10)),

            array('""',               new Token(TokenType::STRING_LITERAL(), '')),
            array('"foo"',            new Token(TokenType::STRING_LITERAL(), 'foo')),
            array('"foo bar"',        new Token(TokenType::STRING_LITERAL(), 'foo bar')),

            array('"\\""',            new Token(TokenType::STRING_LITERAL(), '"')),
            array('"\\\\"',           new Token(TokenType::STRING_LITERAL(), '\\')),
            array('"\\/"',            new Token(TokenType::STRING_LITERAL(), '/')),
            array('"\\b"',            new Token(TokenType::STRING_LITERAL(), "\x08")),
            array('"\\f"',            new Token(TokenType::STRING_LITERAL(), "\f")),
            array('"\\n"',            new Token(TokenType::STRING_LITERAL(), "\n")),
            array('"\\r"',            new Token(TokenType::STRING_LITERAL(), "\r")),
            array('"\\t"',            new Token(TokenType::STRING_LITERAL(), "\t")),

            array('"\\u00a9"',        new Token(TokenType::STRING_LITERAL(), json_decode('"\\u00a9"'))),
            array('"\\ud834\\udD1E"', new Token(TokenType::STRING_LITERAL(), json_decode('"\\ud834\\udD1E"'))),
        );
    }
}
