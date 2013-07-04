<?php
namespace Icecave\Duct;

use Phake;
use PHPUnit_Framework_TestCase;


class ParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->tokenStreamParser = Phake::partialMock(__NAMESPACE__ . '\TokenStreamParser');
        $this->parser = new Parser(null, $this->tokenStreamParser);
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

    public function testOn()
    {
        $callback = function() {};

        $this->parser->on('foo', $callback);

        Phake::verify($this->tokenStreamParser)->on('foo', $this->identicalTo($callback));
    }

    public function testOnce()
    {
        $callback = function() {};

        $this->parser->once('foo', $callback);

        Phake::verify($this->tokenStreamParser)->once('foo', $this->identicalTo($callback));
    }

    public function testRemoveListener()
    {
        $callback = function() {};

        $this->parser->removeListener('foo', $callback);

        Phake::verify($this->tokenStreamParser)->removeListener('foo', $this->identicalTo($callback));
    }

    public function testRemoveAllListeners()
    {
        $this->parser->removeAllListeners('foo');

        Phake::verify($this->tokenStreamParser)->removeAllListeners('foo');
    }

    public function testRemoveAllListenersAllEvents()
    {
        $this->parser->removeAllListeners();

        Phake::verify($this->tokenStreamParser)->removeAllListeners(null);
    }

    public function testListeners()
    {
        $callback = function() {};

        $this->parser->on('foo', $callback);

        $result = $this->parser->listeners('foo');

        $this->assertSame(array($callback), $result);
    }

    public function testEmit()
    {
        $this->parser->emit('foo', array(1, 2, 3));

        Phake::verify($this->tokenStreamParser)->emit('foo', array(1, 2, 3));
    }
}
