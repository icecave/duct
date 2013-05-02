<?php
namespace Icecave\Duct;

use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = new Parser;
    }

    /**
     * @dataProvider parseData
     */
    public function testParse($json)
    {
        $expected = json_decode($json);

        $result = $this->parser->parse($json);

        $this->assertSame(1, $result->size());
        $this->assertEquals($expected, $result->back());
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
