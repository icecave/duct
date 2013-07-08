<?php
namespace Icecave\Duct;

use Icecave\Collections\Vector;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\TypeCheck\TypeCheck;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
class Parser extends AbstractParser
{
    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct($lexer, $parser);

        $this->values = $values = new Vector;

        $this->parser->on(
            'document',
            array($this->values, 'pushBack')
        );
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

        parent::parse($buffer);

        return $this->values();
    }

    /**
     * Fetch the values produced by the parser so far and remove them from the internal value sequence.
     *
     * @return Vector<mixed> The sequence of parsed JSON values.
     */
    public function values()
    {
        $this->typeCheck->values(func_get_args());

        $values = clone $this->values;

        $this->values->clear();

        return $values;
    }

    private $typeCheck;
    private $values;
}
