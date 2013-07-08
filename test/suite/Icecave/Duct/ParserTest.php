<?php
namespace Icecave\Duct;

use Phake;
use PHPUnit_Framework_TestCase;

/**
 * @covers Icecave\Duct\Parser
 * @covers Icecave\Duct\AbstractParser
 */
class ParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = Phake::partialMock(__NAMESPACE__ . '\Parser');
    }

    public function testParseWithConstructorDefaults()
    {
        $parser = new Parser;

        $result = $parser->parse('[1, 2, 3]');

        $this->assertSame(array(1, 2, 3), $result->front());
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

        $this->assertSame(1, $result->size());
        $this->assertEquals($expected, $result->back());

        $this->assertTrue($this->parser->values()->isEmpty());
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
}
