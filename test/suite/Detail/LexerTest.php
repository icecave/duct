<?php
namespace Icecave\Duct\Detail;

use PHPUnit_Framework_TestCase;

/**
 * @covers Icecave\Duct\Detail\Token
 * @covers Icecave\Duct\Detail\Lexer
 */
class LexerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $tokens = [];

        $this->lexer  = new Lexer();
        $this->tokens = &$tokens;

        $this->lexer->setCallback(
            function ($token) use (&$tokens) {
                $tokens[] = $token;
            }
        );
    }

    public function testFeedEmitsIntegerAfterNonDigit()
    {
        $this->lexer->feed(' 1 ');

        $this->assertSame(TokenType::NUMBER_LITERAL, end($this->tokens)->type);
        $this->assertSame(1, end($this->tokens)->value);
    }

    public function testFeedEmitsMultipleIntegers()
    {
        $this->lexer->feed(' 1 2 ');

        $this->assertSame(1, $this->tokens[0]->value);
        $this->assertSame(2, $this->tokens[1]->value);
    }

    public function testFeedIntegerThenNonInteger()
    {
        $this->lexer->feed(' 1{ ');

        $this->assertSame(1, $this->tokens[0]->value);
        $this->assertSame('{', $this->tokens[1]->value);
    }

    public function testFeedZeroIntegerThenNonInteger()
    {
        $this->lexer->feed(' 0{ ');

        $this->assertSame(0, $this->tokens[0]->value);
        $this->assertSame('{', $this->tokens[1]->value);
    }

    public function testFeedFloatThenNonInteger()
    {
        $this->lexer->feed(' 1.1{ ');

        $this->assertSame(1.1, $this->tokens[0]->value);
        $this->assertSame('{', $this->tokens[1]->value);
    }

    public function testFeedExponentThenNonInteger()
    {
        $this->lexer->feed(' 1e5{ ');

        $this->assertSame(1e5, $this->tokens[0]->value);
        $this->assertSame('{', $this->tokens[1]->value);
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

        $this->assertEmpty($this->tokens);

        $this->lexer->feed("\xb6\"");

        $this->assertSame("\xc3\xb6", end($this->tokens)->value);
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
        return [
            ['-'],
            ['1e'],
            ['"foo'],
            ['"foo\\'],
            ['"foo\\uA'],
            ['tru'],
            ['fals'],
            ['nul'],
        ];
    }

    /**
     * @dataProvider singleTokens
     */
    public function testLexWithSingleToken($json, $expectedToken)
    {
        $this->lexer->feed($json);
        $this->lexer->finalize();

        $this->assertSame(1, count($this->tokens));
        $this->assertEquals($expectedToken, end($this->tokens));
        $this->assertSame($expectedToken->value, end($this->tokens)->value);
    }

    public function singleTokens()
    {
        return [
            ['{',                new Token(TokenType::BRACE_OPEN, '{')],
            ['}',                new Token(TokenType::BRACE_CLOSE, '}')],
            ['[',                new Token(TokenType::BRACKET_OPEN, '[')],
            [']',                new Token(TokenType::BRACKET_CLOSE, ']')],
            [':',                new Token(TokenType::COLON, ':')],
            [',',                new Token(TokenType::COMMA, ',')],

            ['true',             new Token(TokenType::BOOLEAN_LITERAL, true)],
            ['false',            new Token(TokenType::BOOLEAN_LITERAL, false)],
            ['null',             new Token(TokenType::NULL_LITERAL, null)],

            ['0',                new Token(TokenType::NUMBER_LITERAL, 0)],
            ['-0',               new Token(TokenType::NUMBER_LITERAL, 0)],
            ['1',                new Token(TokenType::NUMBER_LITERAL, 1)],
            ['-1',               new Token(TokenType::NUMBER_LITERAL, -1)],
            ['12345',            new Token(TokenType::NUMBER_LITERAL, 12345)],
            ['-12345',           new Token(TokenType::NUMBER_LITERAL, -12345)],

            ['0.0',              new Token(TokenType::NUMBER_LITERAL, 0.0)],
            ['-0.0',             new Token(TokenType::NUMBER_LITERAL, 0.0)],
            ['1.1',              new Token(TokenType::NUMBER_LITERAL, 1.1)],
            ['-1.1',             new Token(TokenType::NUMBER_LITERAL, -1.1)],
            ['123.123',          new Token(TokenType::NUMBER_LITERAL, 123.123)],
            ['-123.123',         new Token(TokenType::NUMBER_LITERAL, -123.123)],

            ['0e5',              new Token(TokenType::NUMBER_LITERAL, 0e5)],
            ['0E5',              new Token(TokenType::NUMBER_LITERAL, 0e5)],

            ['1e5',              new Token(TokenType::NUMBER_LITERAL, 1e5)],
            ['1E5',              new Token(TokenType::NUMBER_LITERAL, 1e5)],
            ['1e10',             new Token(TokenType::NUMBER_LITERAL, 1e10)],
            ['1E10',             new Token(TokenType::NUMBER_LITERAL, 1e10)],

            ['1e+5',             new Token(TokenType::NUMBER_LITERAL, 1e5)],
            ['1E+5',             new Token(TokenType::NUMBER_LITERAL, 1e5)],
            ['1e+10',            new Token(TokenType::NUMBER_LITERAL, 1e10)],
            ['1E+10',            new Token(TokenType::NUMBER_LITERAL, 1e10)],

            ['1e-5',             new Token(TokenType::NUMBER_LITERAL, 1e-5)],
            ['1E-5',             new Token(TokenType::NUMBER_LITERAL, 1e-5)],
            ['1e-10',            new Token(TokenType::NUMBER_LITERAL, 1e-10)],
            ['1E-10',            new Token(TokenType::NUMBER_LITERAL, 1e-10)],

            ['0.1e10',           new Token(TokenType::NUMBER_LITERAL, 0.1e10)],
            ['0.1E10',           new Token(TokenType::NUMBER_LITERAL, 0.1e10)],

            ['""',               new Token(TokenType::STRING_LITERAL, '')],
            ['"foo"',            new Token(TokenType::STRING_LITERAL, 'foo')],
            ['"foo bar"',        new Token(TokenType::STRING_LITERAL, 'foo bar')],

            ['"\\""',            new Token(TokenType::STRING_LITERAL, '"')],
            ['"\\\\"',           new Token(TokenType::STRING_LITERAL, '\\')],
            ['"\\/"',            new Token(TokenType::STRING_LITERAL, '/')],
            ['"\\b"',            new Token(TokenType::STRING_LITERAL, "\x08")],
            ['"\\f"',            new Token(TokenType::STRING_LITERAL, "\f")],
            ['"\\n"',            new Token(TokenType::STRING_LITERAL, "\n")],
            ['"\\r"',            new Token(TokenType::STRING_LITERAL, "\r")],
            ['"\\t"',            new Token(TokenType::STRING_LITERAL, "\t")],

            ['"\\u00a9"',        new Token(TokenType::STRING_LITERAL, json_decode('"\\u00a9"'))],
            ['"\\ud834\\udd1e"', new Token(TokenType::STRING_LITERAL, json_decode('"\\ud834\\udd1e"'))],
            ['"\\udbff\\udfff"', new Token(TokenType::STRING_LITERAL, json_decode('"\\udbff\\udfff"'))],
        ];
    }
}
