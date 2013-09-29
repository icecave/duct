<?php
namespace Icecave\Duct;

use Icecave\Duct\Detail\Exception\ParserException;
use Phake;
use PHPUnit_Framework_TestCase;

class EventedParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = Phake::partialMock(__NAMESPACE__ . '\EventedParser');

        $this->callbackArguments = array();

        $self = $this;
        $this->callback = function() use ($self) {
            $self->callbackArguments[] = func_get_args();
        };
    }

    public function testFeed()
    {
        $this->parser->feed('[ 1, 2, { "foo" : "bar" }, 3, 4 ]');

        Phake::inOrder(
            Phake::verify($this->parser)->emit('array-open'),
            Phake::verify($this->parser)->emit('value', array(1)),
            Phake::verify($this->parser)->emit('value', array(2)),
            Phake::verify($this->parser)->emit('object-open'),
            Phake::verify($this->parser)->emit('object-key', array('foo')),
            Phake::verify($this->parser)->emit('value', array('bar')),
            Phake::verify($this->parser)->emit('object-close'),
            Phake::verify($this->parser)->emit('value', array(3)),
            Phake::verify($this->parser)->emit('value', array(4)),
            Phake::verify($this->parser)->emit('array-close'),
            Phake::verify($this->parser)->emit('document')
        );
    }

    public function testFeedEmitsDocumentEventWithObject()
    {
        $this->parser->feed('{}');

        Phake::inOrder(
            Phake::verify($this->parser)->emit('object-open'),
            Phake::verify($this->parser)->emit('object-close'),
            Phake::verify($this->parser)->emit('document')
        );
    }

    public function testFeedFailure()
    {
        $this->parser->feed('{ 1 :');

        $arguments = null;
        Phake::verify($this->parser)->emit('error', Phake::capture($arguments));

        $expected = array(
            new ParserException('Unexpected token "NUMBER_LITERAL" in state "OBJECT_KEY".')
        );

        $this->assertEquals($expected, $arguments);
    }

    public function testFinalize()
    {
        $this->parser->feed('10');

        Phake::verify($this->parser, Phake::never())->emit(Phake::anyParameters());

        $this->parser->finalize();

        Phake::inOrder(
            Phake::verify($this->parser)->emit('value', array(10)),
            Phake::verify($this->parser)->emit('document')
        );
    }

    public function testFinalizeFailure()
    {
        $this->parser->feed('{ 1');
        $this->parser->finalize();

        $arguments = null;
        Phake::verify($this->parser)->emit('error', Phake::capture($arguments));

        $expected = array(
            new ParserException('Unexpected token "NUMBER_LITERAL" in state "OBJECT_KEY".')
        );

        $this->assertEquals($expected, $arguments);
    }

    public function testOn()
    {
        $this->parser->on('foo', $this->callback);

        $this->parser->emit('foo', array(1, 2));
        $this->parser->emit('foo', array(3, 4));

        $this->assertSame(
            $this->callbackArguments,
            array(
                array(1, 2),
                array(3, 4)
            )
        );
    }

    public function testOnce()
    {
        $this->parser->once('foo', $this->callback);

        $this->parser->emit('foo', array(1, 2));
        $this->parser->emit('foo', array(3, 4));

        $this->assertSame(
            $this->callbackArguments,
            array(
                array(1, 2)
            )
        );
    }

    public function testRemoveListener()
    {
        $this->parser->on('foo', $this->callback);
        $this->parser->removeListener('foo', $this->callback);

        $this->parser->emit('foo');

        $this->assertSame(
            $this->callbackArguments,
            array()
        );
    }

    public function testRemoveAllListeners()
    {
        $this->parser->on('foo', $this->callback);
        $this->parser->removeAllListeners('foo');

        $this->parser->emit('foo');

        $this->assertSame(
            $this->callbackArguments,
            array()
        );
    }

    public function testRemoveAllListenersAllEvents()
    {
        $this->parser->on('foo', $this->callback);
        $this->parser->removeAllListeners(null);

        $this->parser->emit('foo');

        $this->assertSame(
            $this->callbackArguments,
            array()
        );
    }

    public function testListeners()
    {
        $callback = function() {};

        $this->parser->on('foo', $callback);

        $result = $this->parser->listeners('foo');

        $this->assertSame(array($callback), $result);
    }
}
