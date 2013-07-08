<?php
namespace Icecave\Duct;

use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\TypeCheck\TypeCheck;

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

    private $typeCheck;
    protected $lexer;
    protected $parser;
}
