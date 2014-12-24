<?php
namespace Icecave\Duct;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = Phake::partialMock(__NAMESPACE__ . '\Parser');
    }

    /**
     * @dataProvider parseData
     */
    public function testParse($json)
    {
        $expected = json_decode($json);

        $result = $this->parser->parse($json);

        Phake::inOrder(
            Phake::verify($this->parser)->reset(),
            Phake::verify($this->parser)->feed($json),
            Phake::verify($this->parser)->finalize(),
            Phake::verify($this->parser)->values()
        );

        $this->assertEquals(array($expected), $result);

        $this->assertEquals(array(), $this->parser->values());
    }

    public function parseData()
    {
        return array(
            array('{}'),
            array('[]'),
            array('{ "a" : 1, "b" : 2, "c" : 3 }'),
            array('{ "a" : 1, "nested" : { "b" : 2, "c" : 3, "d" : 4 }, "e" : 5 }'),
            array('[ 1, 2, 3 ]'),
            array('[ 1, [ 2, 3, 4 ], 5 ]'),
        );
    }

    public function testParseObjectAsAssociativeArray()
    {
        $this->assertFalse(
            $this->parser->produceAssociativeArrays()
        );

        $this->parser->setProduceAssociativeArrays(true);

        $this->assertTrue(
            $this->parser->produceAssociativeArrays()
        );

        $json = '{ "a" : 1, "nested" : { "b" : 2, "c" : 3, "d" : 4 }, "e" : 5 }';

        $result = $this->parser->parse($json);

        $this->assertEquals(
            array(
                json_decode($json, true)
            ),
            $result
        );
    }

    public function testParseWithConstructorDefaults()
    {
        $parser = new Parser();

        $result = $parser->parse('[1, 2, 3]');

        $this->assertSame(array(array(1, 2, 3)), $result);
    }

    public function testFeedFailure()
    {
        $this->setExpectedException('Icecave\Duct\Detail\Exception\ParserException', 'Unexpected token "BRACKET_CLOSE".');

        try {
            $this->parser->feed(']');
        } catch (Exception $e) {
            Phake::verify($this->parser)->reset();
            throw $e;
        }
    }

    public function testFinalizeFailure()
    {
        $this->setExpectedException('Icecave\Duct\Detail\Exception\ParserException', 'Unexpected token "NUMBER_LITERAL" in state "OBJECT_KEY".');

        try {
            $this->parser->feed('{ 1');
            $this->parser->finalize();
        } catch (Exception $e) {
            Phake::verify($this->parser)->reset();
            throw $e;
        }
    }
}
