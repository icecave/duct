<?php
namespace Icecave\Duct\Detail;

use PHPUnit_Framework_TestCase;
use Phake;

class TokenStreamParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = Phake::partialMock(__NAMESPACE__ . '\TokenStreamParser');
    }

    protected function createTokens(array $tokens)
    {
        $result = [];

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
        $tokens = $this->createTokens(['{']);
        $this->parser->feed($tokens);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Token stream ended unexpectedly.');
        $this->parser->finalize();
    }

    public function testFinalizeFailsWithPartialArray()
    {
        $tokens = $this->createTokens(['[']);
        $this->parser->feed($tokens);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Token stream ended unexpectedly.');
        $this->parser->finalize();
    }

    public function testFeedFailsOnNonStringKey()
    {
        $tokens = $this->createTokens(['{', 1]);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "NUMBER_LITERAL" in state "OBJECT_KEY".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterObjectKey()
    {
        $tokens = $this->createTokens(['{', "foo", ',']);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COMMA" in state "OBJECT_KEY_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterObjectValue()
    {
        $tokens = $this->createTokens(['{', "foo", ':', "bar", ':']);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COLON" in state "OBJECT_VALUE_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    public function testFeedFailsUnexpectedTokenAfterArrayValue()
    {
        $tokens = $this->createTokens(['[', "foo", ':']);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "COLON" in state "ARRAY_VALUE_SEPARATOR".');
        $this->parser->feed($tokens);
    }

    /**
     * @dataProvider invalidStartToken
     */
    public function testFeedFailsOnInvalidStartingToken($token)
    {
        $tokens = $this->createTokens([$token]);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ParserException', 'Unexpected token "' . $tokens[0]->type() . '".');
        $this->parser->feed($tokens);
    }

    public function invalidStartToken()
    {
        return [
            ['}'],
            [']'],
            [':'],
            [','],
        ];
    }

    /**
     * @dataProvider eventData
     */
    public function testParseEvents(array $tokens, $expectedEvents)
    {
        $tokens = $this->createTokens($tokens);
        $this->parser->feed($tokens);
        $this->parser->finalize();

        $verifiers = [];
        foreach ($expectedEvents as $eventArguments) {
            $verifiers[] = call_user_func_array(
                [Phake::verify($this->parser), 'emit'],
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
        return [
            [[1],                                                  [['value', [1]]]],
            [[1.1],                                                [['value', [1.1]]]],
            [[true],                                               [['value', [true]]]],
            [[false],                                              [['value', [false]]]],
            [[null],                                               [['value', [null]]]],
            [['foo'],                                              [['value', ['foo']]]],

            [
                ['[', ']'],
                [
                    ['array-open'],
                    ['array-close'],
                ],
            ],

            [
                ['[', 1, ',', 2, ',', 3, ']'],
                [
                    ['array-open'],
                    ['value', [1]],
                    ['value', [2]],
                    ['value', [3]],
                    ['array-close'],
                ],
            ],

            [
                ['[', '{', '}', ']'],
                [
                    ['array-open'],
                    ['object-open'],
                    ['object-close'],
                    ['array-close'],
                ],
            ],

            [
                ['{', '}'],
                [
                    ['object-open'],
                    ['object-close'],
                ],
            ],

            [
                ['{', 'k1', ':', 1, ',', 'k2', ':', 2, '}'],
                [
                    ['object-open'],
                    ['object-key', ['k1']],
                    ['value', [1]],
                    ['object-key', ['k2']],
                    ['value', [2]],
                    ['object-close'],
                ],
            ],
        ];
    }
}
