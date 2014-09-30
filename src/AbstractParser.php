<?php
namespace Icecave\Duct;

use Exception;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use ReflectionMethod;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
abstract class AbstractParser
{
    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        if (null === $lexer) {
            $lexer = new Lexer();
        }

        if (null === $parser) {
            $parser = new TokenStreamParser();
        }

        $this->lexer = $lexer;
        $this->parser = $parser;

        $this->lexer->on(
            'token',
            array($this->parser, 'feedToken')
        );

        $this->parser->on(
            'value',
            $this->makePublicWrapper('onValue')
        );

        $this->parser->on(
            'array-open',
            $this->makePublicWrapper('onArrayOpen')
        );

        $this->parser->on(
            'array-close',
            $this->makePublicWrapper('onArrayClose')
        );

        $this->parser->on(
            'object-open',
            $this->makePublicWrapper('onObjectOpen')
        );

        $this->parser->on(
            'object-close',
            $this->makePublicWrapper('onObjectClose')
        );

        $this->parser->on(
            'object-key',
            $this->makePublicWrapper('onObjectKey')
        );
    }

    /**
     * Parse one or more complete JSON values.
     *
     * @param string $buffer The JSON data.
     *
     * @throws Exception\SyntaxExceptionInterface
     */
    public function parse($buffer)
    {
        $this->reset();
        $this->feed($buffer);
        $this->finalize();
    }

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset()
    {
        $this->lexer->reset();
        $this->parser->reset();
    }

    /**
     * Feed (potentially incomplete) JSON data to the parser.
     *
     * @param  string                             $buffer The JSON data.
     * @throws Exception\SyntaxExceptionInterface
     */
    public function feed($buffer)
    {
        try {
            $this->lexer->feed($buffer);
        } catch (Exception $e) {
            $this->reset();
            throw $e;
        }
    }

    /**
     * Finalize parsing.
     *
     * @throws Exception\SyntaxExceptionInterface
     */
    public function finalize()
    {
        try {
            $this->lexer->finalize();
            $this->parser->finalize();
        } catch (Exception $e) {
            $this->reset();
            throw $e;
        }
    }

    /**
     * Called when the token stream parser emits a 'value' event.
     *
     * @param mixed $value The value emitted.
     */
    abstract protected function onValue($value);

    /**
     * Called when the token stream parser emits an 'array-open' event.
     */
    abstract protected function onArrayOpen();

    /**
     * Called when the token stream parser emits an 'array-close' event.
     */
    abstract protected function onArrayClose();

    /**
     * Called when the token stream parser emits an 'object-open' event.
     */
    abstract protected function onObjectOpen();

    /**
     * Called when the token stream parser emits an 'object-close' event.
     */
    abstract protected function onObjectClose();

    /**
     * Called when the token stream parser emits an 'object-key' event.
     *
     * @param string $value The key for the next object value.
     */
    abstract protected function onObjectKey($value);

    /**
     * @param string $method
     */
    protected function makePublicWrapper($method)
    {
        $self = $this;
        $reflector = new ReflectionMethod($this, $method);
        $reflector->setAccessible(true);

        return function () use ($self, $reflector) {
            return $reflector->invokeArgs($self, func_get_args());
        };
    }

    protected $lexer;
    protected $parser;
}
