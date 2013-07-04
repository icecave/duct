<?php
namespace Icecave\Duct;

use Icecave\Duct\TypeCheck\TypeCheck;
use Evenement\EventEmitterInterface;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
class Parser implements EventEmitterInterface
{
    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $lexer) {
            $lexer = new Lexer;
        }

        if (null === $parser) {
            $parser = new TokenStreamParser;
        }

        $this->lexer = $lexer;
        $this->parser = $parser;
    }

    /**
     * Parse one or more complete JSON values.
     *
     * @param string $buffer The JSON data.
     *
     * @return Vector<mixed>             The sequence of parsed JSON values.
     * @throws Exception\ParserException Indicates that the JSON stream terminated midway through a JSON value.
     */
    public function parse($buffer)
    {
        $this->typeCheck->parse(func_get_args());

        $this->reset();
        $this->feed($buffer);
        $this->finalize();

        return $this->values();
    }

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset()
    {
        $this->typeCheck->reset(func_get_args());

        $this->lexer->reset();
        $this->parser->reset();
    }

    /**
     * Feed (potentially incomplete) JSON data to the parser.
     *
     * @param string $buffer The JSON data.
     */
    public function feed($buffer)
    {
        $this->typeCheck->feed(func_get_args());

        $this->lexer->feed($buffer);
        $this->parser->feed($this->lexer->tokens());
    }

    /**
     * Finalize parsing.
     *
     * @throws Exception\ParserException Indicates that the JSON stream terminated midway through a JSON value.
     */
    public function finalize()
    {
        $this->typeCheck->finalize(func_get_args());

        $this->lexer->finalize();
        $this->parser->feed($this->lexer->tokens());
        $this->parser->finalize();
    }

    /**
     * Fetch the values produced by the parser so far and remove them from the internal value sequence.
     *
     * @return Vector<mixed> The sequence of parsed JSON values.
     */
    public function values()
    {
        $this->typeCheck->values(func_get_args());

        return $this->parser->values();
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
    private $lexer;
    private $parser;
}
