<?php
namespace Icecave\Duct;

use PHPUnit_Framework_TestCase;

class LexerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->lexer = new Lexer();
    }

    /**
     * @dataProvider simpleTokens
     */
    public function testLexWithSimpleTokens($json, $expectedToken)
    {
        $tokens = $this->lexer->lex($json);
        $this->assertInstanceOf('Icecave\Collections\Vector', $tokens);
        $this->assertSame(1, $tokens->size());
        $this->assertEquals($expectedToken, $tokens->back());
        $this->assertSame($expectedToken->value(), $tokens->back()->value());
    }

    /**
     * @dataProvider keywordTokens
     */
    public function testLexWithKeywordTokens($json, $expectedToken)
    {
        $tokens = $this->lexer->lex($json);
        $this->assertInstanceOf('Icecave\Collections\Vector', $tokens);
        $this->assertSame(1, $tokens->size());
        $this->assertEquals($expectedToken, $tokens->back());
        $this->assertSame($expectedToken->value(), $tokens->back()->value());
    }

    /**
     * @dataProvider numericTokens
     */
    public function testLexWithNumericTokens($json, $expectedToken)
    {
        $tokens = $this->lexer->lex($json);
        $this->assertInstanceOf('Icecave\Collections\Vector', $tokens);
        $this->assertSame(1, $tokens->size());
        $this->assertEquals($expectedToken, $tokens->back());
        $this->assertSame($expectedToken->value(), $tokens->back()->value());
    }

    /**
     * @dataProvider stringTokens
     */
    public function testLexWithStringTokens($json, $expectedToken)
    {
        $tokens = $this->lexer->lex($json);
        $this->assertInstanceOf('Icecave\Collections\Vector', $tokens);
        $this->assertSame(1, $tokens->size());
        $this->assertEquals($expectedToken, $tokens->back());
        $this->assertSame($expectedToken->value(), $tokens->back()->value());
    }

    public function simpleTokens()
    {
        return array(
            array('{', new Token(TokenType::BRACE_OPEN(), '{')),
            array('}', new Token(TokenType::BRACE_CLOSE(), '}')),
            array('[', new Token(TokenType::BRACKET_OPEN(), '[')),
            array(']', new Token(TokenType::BRACKET_CLOSE(), ']')),
            array(':', new Token(TokenType::COLON(), ':')),
            array(',', new Token(TokenType::COMMA(), ',')),
        );
    }

    public function keywordTokens()
    {
        return array(
            array('true',  new Token(TokenType::SCALAR(), true)),
            array('false', new Token(TokenType::SCALAR(), false)),
            array('null',  new Token(TokenType::SCALAR(), null)),
        );
    }

    public function numericTokens()
    {
        return array(
            array('0',        new Token(TokenType::SCALAR(), 0)),
            array('-0',       new Token(TokenType::SCALAR(), 0)),
            array('1',        new Token(TokenType::SCALAR(), 1)),
            array('-1',       new Token(TokenType::SCALAR(), -1)),
            array('12345',    new Token(TokenType::SCALAR(), 12345)),
            array('-12345',   new Token(TokenType::SCALAR(), -12345)),

            array('0.0',      new Token(TokenType::SCALAR(), 0.0)),
            array('-0.0',     new Token(TokenType::SCALAR(), 0.0)),
            array('1.1',      new Token(TokenType::SCALAR(), 1.1)),
            array('-1.1',     new Token(TokenType::SCALAR(), -1.1)),
            array('123.123',  new Token(TokenType::SCALAR(), 123.123)),
            array('-123.123', new Token(TokenType::SCALAR(), -123.123)),

            array('0e5',      new Token(TokenType::SCALAR(), 0e5)),
            array('0E5',      new Token(TokenType::SCALAR(), 0e5)),

            array('1e5',      new Token(TokenType::SCALAR(), 1e5)),
            array('1E5',      new Token(TokenType::SCALAR(), 1e5)),
            array('1e10',     new Token(TokenType::SCALAR(), 1e10)),
            array('1E10',     new Token(TokenType::SCALAR(), 1e10)),

            array('1e+5',     new Token(TokenType::SCALAR(), 1e5)),
            array('1E+5',     new Token(TokenType::SCALAR(), 1e5)),
            array('1e+10',    new Token(TokenType::SCALAR(), 1e10)),
            array('1E+10',    new Token(TokenType::SCALAR(), 1e10)),

            array('1e-5',     new Token(TokenType::SCALAR(), 1e-5)),
            array('1E-5',     new Token(TokenType::SCALAR(), 1e-5)),
            array('1e-10',    new Token(TokenType::SCALAR(), 1e-10)),
            array('1E-10',    new Token(TokenType::SCALAR(), 1e-10)),

            array('0.1e10',   new Token(TokenType::SCALAR(), 0.1e10)),
            array('0.1E10',   new Token(TokenType::SCALAR(), 0.1e10)),
        );
    }

    public function stringTokens()
    {
        return array(
            array('""',                 new Token(TokenType::SCALAR(), '')),
            array('"foo"',              new Token(TokenType::SCALAR(), 'foo')),
            array('"foo bar"',          new Token(TokenType::SCALAR(), 'foo bar')),

            array('"\\""',              new Token(TokenType::SCALAR(), '"')),
            array('"\\\\"',             new Token(TokenType::SCALAR(), '\\')),
            array('"\\/"',              new Token(TokenType::SCALAR(), '/')),
            array('"\\b"',              new Token(TokenType::SCALAR(), "\x08")),
            array('"\\f"',              new Token(TokenType::SCALAR(), "\f")),
            array('"\\n"',              new Token(TokenType::SCALAR(), "\n")),
            array('"\\r"',              new Token(TokenType::SCALAR(), "\r")),
            array('"\\t"',              new Token(TokenType::SCALAR(), "\t")),

            array('"\\u00a9"',          new Token(TokenType::SCALAR(), json_decode('"\\u00a9"'))),
            array('"\\ud834\\udD1E"',   new Token(TokenType::SCALAR(), json_decode('"\\ud834\\udD1E"'))),
        );
    }
}
