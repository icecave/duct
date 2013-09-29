<?php
namespace Icecave\Duct;

use Exception;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\TypeCheck\TypeCheck;
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
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $lexer) {
            $lexer = new Lexer;
        }

        if (null === $parser) {
            $parser = new TokenStreamParser;
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
        $this->typeCheck->parse(func_get_args());

        $this->reset();
        $this->feed($buffer);
        $this->finalize();
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
     * @param  string                             $buffer The JSON data.
     * @throws Exception\SyntaxExceptionInterface
     */
    public function feed($buffer)
    {
        $this->typeCheck->feed(func_get_args());

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
        $this->typeCheck->finalize(func_get_args());

        try {
            $this->lexer->finalize();
            $this->parser->finalize();
        } catch (Exception $e) {
            $this->reset();
            throw $e;
        }
    }

    /**
     * @param mixed $value
     */
    protected function onValue($value)
    {
    }

    protected function onArrayOpen()
    {
    }

    protected function onArrayClose()
    {
    }

    protected function onObjectOpen()
    {
    }

    protected function onObjectClose()
    {
    }

    /**
     * @param mixed $value
     */
    protected function onObjectKey($value)
    {
    }

    protected function makePublicWrapper($method)
    {
        $self = $this;
        $reflector = new ReflectionMethod($this, $method);
        $reflector->setAccessible(true);

        return function () use ($self, $reflector) {
            return $reflector->invokeArgs($self, func_get_args());
        };
    }

    private $typeCheck;
    protected $lexer;
    protected $parser;
}
