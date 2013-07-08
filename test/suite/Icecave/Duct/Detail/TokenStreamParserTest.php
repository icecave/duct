<?php
namespace Icecave\Duct\Detail;

use Phake;
use PHPUnit_Framework_TestCase;
use stdClass;

class TokenStreamParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = Phake::partialMock(__NAMESPACE__ . '\TokenStreamParser');
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
        $values = array();

        $this->parser->on(
            'document',
            function ($value) use (&$values) {
                $values[] = $value;
            }
        );

        $tokens = $this->createTokens($tokens);
        $this->parser->feed($tokens);
        $this->parser->finalize();

        $this->assertTrue(is_array($values));
        $this->assertSame(1, count($values));
        $this->assertSame(gettype($expectedValue), gettype(end($values)));
        $this->assertEquals($expectedValue, end($values));
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

    /**
     * @dataProvider eventData
     */
    public function testParseEvents(array $tokens, $expectedEvents)
    {
        $tokens = $this->createTokens($tokens);
        $this->parser->feed($tokens);
        $this->parser->finalize();

        $verifiers = array();
        foreach ($expectedEvents as $eventArguments) {
            $verifiers[] = call_user_func_array(
                array(Phake::verify($this->parser), 'emit'),
                $eventArguments
            );
        }

        call_user_func_array(
            'Phake::inOrder',
            $verifiers
        );
    }

    public function eventData()
    {
        return array(
            array(array(1),                                                  array(array('value', array(1)))),
            array(array(1.1),                                                array(array('value', array(1.1)))),
            array(array(true),                                               array(array('value', array(true)))),
            array(array(false),                                              array(array('value', array(false)))),
            array(array(null),                                               array(array('value', array(null)))),
            array(array('foo'),                                              array(array('value', array('foo')))),

            array(
                array('[', ']'),
                array(
                    array('array-open'),
                    array('array-close'),
                ),
            ),

            array(
                array('[', 1, ',', 2, ',', 3, ']'),
                array(
                    array('array-open'),
                    array('value', array(1)),
                    array('value', array(2)),
                    array('value', array(3)),
                    array('array-close'),
                ),
            ),

            array(
                array('[', '{', '}', ']'),
                array(
                    array('array-open'),
                    array('object-open'),
                    array('object-close'),
                    array('array-close'),
                ),
            ),

            array(
                array('{', '}',),
                array(
                    array('object-open'),
                    array('object-close'),
                ),
            ),

            array(
                array('{', 'k1', ':', 1, ',', 'k2', ':', 2, '}',),
                array(
                    array('object-open'),
                    array('object.key', array('k1')),
                    array('value', array(1)),
                    array('object.key', array('k2')),
                    array('value', array(2)),
                    array('object-close'),
                ),
            )
        );
    }
}
