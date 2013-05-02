<?php
namespace Icecave\Duct;

use PHPUnit_Framework_TestCase;
use stdClass;

class TokenStreamParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = new TokenStreamParser;
    }

    protected function createTokens(array $tokens)
    {
        $result = array();

        foreach ($tokens as $token) {
            if (is_string($token) && 1 === strlen($token)) {
                $result[] = Token::createSpecial($token);
            } else {
                $result[] = Token::createLiteral($token);
            }
        }

        return $result;
    }

    public function testFinalizeFailsWithPartialObject()
    {
        $tokens = $this->createTokens(array('{'));
        $this->parser->feed($tokens);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Token stream ended while parsing object.');
        $this->parser->finalize();
    }

    public function testFinalizeFailsWithPartialArray()
    {
        $tokens = $this->createTokens(array('['));
        $this->parser->feed($tokens);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Token stream ended while parsing array.');
        $this->parser->finalize();
    }

    public function testFeedFailsOnNonStringKey()
    {
        $tokens = $this->createTokens(array('{', 1));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "NUMBER_LITERAL" in state "OBJECT_KEY".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterObjectKey()
    {
        $tokens = $this->createTokens(array('{', "foo", ','));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COMMA" in state "OBJECT_KEY_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterObjectValue()
    {
        $tokens = $this->createTokens(array('{', "foo", ':', "bar", ':'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COLON" in state "OBJECT_VALUE_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterArrayValue()
    {
        $tokens = $this->createTokens(array('[', "foo", ':'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COLON" in state "ARRAY_VALUE_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    /**
     * @dataProvider invalidStartToken
     */
    public function testFeedFailsOnInvalidStartingToken($token)
    {
        $tokens = $this->createTokens(array($token));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "' . $tokens[0]->type() . '".');
        $this->parser->feed($tokens);
    }

    public function invalidStartToken()
    {
        return array(
            array('}'),
            array(']'),
            array(':'),
            array(','),
        );
    }

    /**
     * @dataProvider parseData
     */
    public function testParse(array $tokens, $expectedValue)
    {
        $tokens = $this->createTokens($tokens);
        $values = $this->parser->parse($tokens);
        $this->assertInstanceOf('Icecave\Collections\Vector', $values);
        $this->assertSame(1, $values->size());
        $this->assertSame(gettype($expectedValue), gettype($values->back()));
        $this->assertEquals($expectedValue, $values->back());
    }

    public function parseData()
    {
        return array(
            array(array(1),                                                  1),
            array(array(1.1),                                                1.1),
            array(array(true),                                               true),
            array(array(false),                                              false),
            array(array(null),                                               null),
            array(array('foo'),                                              'foo'),

            array(array('[', ']'),                                           array()),
            array(array('[', 1, ']'),                                        array(1)),
            array(array('[', 1, ',', 2, ',', 3, ']'),                        array(1, 2, 3)),

            array(array('[', '[', ']', ']'),                                 array(array())),
            array(array('[', '{', '}', ']'),                                 array(new stdClass)),

            array(array('{', '}'),                                           new stdClass),
            array(array('{', 'k1', ':', 1, '}'),                             (object) array('k1' => 1)),
            array(
                array('{', 'k1', ':', 1, ',', 'k2', ':', 2, ',', 'k3', ':', 3, '}'),
                (object) array('k1' => 1, 'k2' => 2, 'k3' => 3)
            ),

            array(array('{', 'k1', ':', '{', '}', '}'),                      (object) array('k1' => new stdClass)),
            array(array('{', 'k1', ':', '{', 'k2', ':', '{', '}', '}', '}'), (object) array('k1' => (object) array('k2' => new stdClass))),
            array(array('{', 'k1', ':', '[', 1, ',', 2, ']', '}'),           (object) array('k1' => array(1, 2))),

        );
    }
}
