<?php
namespace Icecave\Duct;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitter;
use Exception;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\TypeCheck\TypeCheck;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
class EventedParser extends AbstractParser implements EventEmitterInterface
{
    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->depth = 0;
        $this->eventEmitterImpl = new EventEmitter;

        parent::__construct($lexer, $parser);
    }

    /**
     * Feed (potentially incomplete) JSON data to the parser.
     *
     * @param  string                             $buffer The JSON data.
     * @throws Exception\SyntaxExceptionInterface
     */
    public function feed($buffer)
    {
        $this->typeCheck->feed(func_get_args());

        try {
            parent::feed($buffer);
        } catch (Exception $e) {
            $this->emit('error', array($e));
        }
    }

    /**
     * Finalize parsing.
     *
     * @throws Exception\SyntaxExceptionInterface
     */
    public function finalize()
    {
        $this->typeCheck->finalize(func_get_args());

        try {
            parent::finalize();
        } catch (Exception $e) {
            $this->emit('error', array($e));
        }
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function on($event, $listener)
    {
        $this->typeCheck->on(func_get_args());

        return $this->eventEmitterImpl->on($event, $listener);
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function once($event, $listener)
    {
        $this->typeCheck->once(func_get_args());

        return $this->eventEmitterImpl->once($event, $listener);
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function removeListener($event, $listener)
    {
        $this->typeCheck->removeListener(func_get_args());

        return $this->eventEmitterImpl->removeListener($event, $listener);
    }

    /**
     * @param string|null $event
     */
    public function removeAllListeners($event = null)
    {
        $this->typeCheck->removeAllListeners(func_get_args());

        return $this->eventEmitterImpl->removeAllListeners($event);
    }

    /**
     * @param string $event
     *
     * @return array<callable>
     */
    public function listeners($event)
    {
        $this->typeCheck->listeners(func_get_args());

        return $this->eventEmitterImpl->listeners($event);
    }

    /**
     * @param string $event
     * @param array  $arguments
     */
    public function emit($event, array $arguments = array())
    {
        $this->typeCheck->emit(func_get_args());

        $this->eventEmitterImpl->emit($event, $arguments);
    }

    /**
     * Called when the token stream parser emits a 'value' event.
     *
     * @param mixed $value The value emitted.
     */
    protected function onValue($value)
    {
        $this->typeCheck->onValue(func_get_args());

        if (0 === $this->depth) {
            $this->emit('document-open');
            $this->emit('value', array($value));
            $this->emit('document-close');
        } else {
            $this->emit('value', array($value));
        }
    }

    /**
     * Called when the token stream parser emits an 'array-open' event.
     */
    protected function onArrayOpen()
    {
        $this->typeCheck->onArrayOpen(func_get_args());

        if (0 === $this->depth++) {
            $this->emit('document-open');
        }

        $this->emit('array-open');
    }

    /**
     * Called when the token stream parser emits an 'array-close' event.
     */
    protected function onArrayClose()
    {
        $this->typeCheck->onArrayClose(func_get_args());

        $this->emit('array-close');

        if (0 === --$this->depth) {
            $this->emit('document-close');
        }
    }

    /**
     * Called when the token stream parser emits an 'object-open' event.
     */
    protected function onObjectOpen()
    {
        $this->typeCheck->onObjectOpen(func_get_args());

        if (0 === $this->depth++) {
            $this->emit('document-open');
        }

        $this->emit('object-open');
    }

    /**
     * Called when the token stream parser emits an 'object-close' event.
     */
    protected function onObjectClose()
    {
        $this->typeCheck->onObjectClose(func_get_args());

        $this->emit('object-close');

        if (0 === --$this->depth) {
            $this->emit('document-close');
        }
    }

    /**
     * Called when the token stream parser emits an 'object-key' event.
     *
     * @param string $value The key for the next object value.
     */
    protected function onObjectKey($value)
    {
        $this->typeCheck->onObjectKey(func_get_args());

        $this->emit('object-key', array($value));
    }

    private $typeCheck;
    private $depth;
}
