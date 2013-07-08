<?php
namespace Icecave\Duct;

use Evenement\EventEmitterInterface;
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

        parent::__construct($lexer, $parser);
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function on($event, $listener)
    {
        $this->typeCheck->on(func_get_args());

        return $this->parser->on($event, $listener);
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function once($event, $listener)
    {
        $this->typeCheck->once(func_get_args());

        return $this->parser->once($event, $listener);
    }

    /**
     * @param string   $event
     * @param callable $listener
     */
    public function removeListener($event, $listener)
    {
        $this->typeCheck->removeListener(func_get_args());

        return $this->parser->removeListener($event, $listener);
    }

    /**
     * @param string|null $event
     */
    public function removeAllListeners($event = null)
    {
        $this->typeCheck->removeAllListeners(func_get_args());

        return $this->parser->removeAllListeners($event);
    }

    /**
     * @param string $event
     *
     * @return array<callable>
     */
    public function listeners($event)
    {
        $this->typeCheck->listeners(func_get_args());

        return $this->parser->listeners($event);
    }

    /**
     * @param string $event
     * @param array  $arguments
     */
    public function emit($event, array $arguments = array())
    {
        $this->typeCheck->emit(func_get_args());

        $this->parser->emit($event, $arguments);
    }

    private $typeCheck;
}
