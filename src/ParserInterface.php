<?php
namespace Icecave\Duct;

use Icecave\Duct\Exception\SyntaxExceptionInterface;

/**
 * The input interface for a JSON parser.
 */
interface ParserInterface
{
    /**
     * Parse one or more complete JSON values.
     *
     * This is a convenience method that feeds the buffer to the parser and
     * finalizes parsing.
     *
     * @param string $buffer The JSON data.
     *
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
     */
    public function parse($buffer);

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset();

    /**
     * Feed (potentially incomplete) JSON data to the parser.
     *
     * @param string $buffer The JSON data.
     *
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
     */
    public function feed($buffer);

    /**
     * Finalize parsing.
     *
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
     */
    public function finalize();
}
