<?php
namespace Icecave\Duct;

use Exception;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\Exception\SyntaxExceptionInterface;
use SplStack;
use stdClass;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
class Parser implements ParserInterface
{
    /**
     * @param boolean                $produceAssociativeArrays True if JSON objects should produce arrays rather than objects; otherwise, false.
     * @param Lexer|null             $lexer                    The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser                   The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(
        $produceAssociativeArrays = false,
        Lexer $lexer = null,
        TokenStreamParser $parser = null
    ) {
        if (null === $lexer) {
            $lexer = new Lexer;
        }

        if (null === $parser) {
            $parser = new TokenStreamParser;
        }

        $this->produceAssociativeArrays = $produceAssociativeArrays;
        $this->lexer                    = $lexer;
        $this->parser                   = $parser;
        $this->values                   = [];
        $this->previousContexts         = [];
        $this->currentKey               = null;
        $this->currentValue             = null;

        $this->lexer->setCallback(
            [$this->parser, 'feedToken']
        );

        $this->parser->on(
            'value',
            function ($value) {
                $this->handleValue($value);
            }
        );

        $this->parser->on(
            'array-open',
            function () {
                $this->push([]);
            }
        );

        $this->parser->on(
            'array-close',
            function () {
                $this->pop();
            }
        );

        $this->parser->on(
            'object-open',
            function () {
                if ($this->produceAssociativeArrays) {
                    $this->push([]);
                } else {
                    $this->push(new stdClass);
                }
            }
        );

        $this->parser->on(
            'object-close',
            function () {
                $this->pop();
            }
        );

        $this->parser->on(
            'object-key',
            function ($value) {
                $this->currentKey = $value;
            }
        );
    }

    /**
     * Set whether or not to use associative arrays for JSON objects.
     *
     * @return boolean True if JSON objects should produce arrays rather than objects; otherwise, false.
     */
    public function produceAssociativeArrays()
    {
        return $this->produceAssociativeArrays;
    }

    /**
     * Set whether or not to use associative arrays for JSON objects.
     *
     * @param boolean $produceAssociativeArrays True if JSON objects should produce arrays rather than objects; otherwise, false.
     */
    public function setProduceAssociativeArrays($produceAssociativeArrays)
    {
        $this->produceAssociativeArrays = $produceAssociativeArrays;
    }

    /**
     * Parse one or more complete JSON values.
     *
     * This is a convenience method that feeds the buffer to the parser and
     * finalizes parsing.
     *
     * @param string $buffer The JSON data.
     *
     * @return array<mixed>             The sequence of parsed JSON values.
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
     */
    public function parse($buffer)
    {
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
        $this->lexer->reset();
        $this->parser->reset();

        $this->values           = [];
        $this->previousContexts = [];
        $this->currentKey       = null;
        $this->currentValue     = null;
    }

    /**
     * Feed (potentially incomplete) JSON data to the parser.
     *
     * @param string $buffer The JSON data.
     *
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
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
     * @throws SyntaxExceptionInterface If the JSON buffer is invalid.
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
     * Fetch the values produced by the parser so far and remove them from the
     * internal value sequence.
     *
     * @return array<mixed> The sequence of parsed JSON values.
     */
    public function values()
    {
        $values       = $this->values;
        $this->values = [];

        return $values;
    }

    /**
     * Handle an incoming value.
     *
     * @param mixed $value
     */
    private function handleValue($value)
    {
        if (null === $this->currentValue) {
            $this->values[] = $value;
        } else {
            if (null === $this->currentKey) {
                $this->currentValue[] = $value;
            } elseif ($this->produceAssociativeArrays) {
                $this->currentValue[$this->currentKey] = $value;
            } else {
                $this->currentValue->{$this->currentKey} = $value;
            }
        }
    }

    /**
     * Push a value onto the object stack.
     *
     * @param stdClass|array $value
     */
    private function push($value)
    {
        if ($this->currentValue) {
            $this->previousContexts[] = [$this->currentKey, $this->currentValue];
        }

        $this->currentKey   = null;
        $this->currentValue = $value;
    }

    /**
     * Pop a value from the object stack, emitting it if the stack is empty.
     */
    private function pop()
    {
        $value = $this->currentValue;

        list($this->currentKey, $this->currentValue) = array_pop($this->previousContexts);

        $this->handleValue($value);
    }

    private $produceAssociativeArrays;
    private $lexer;
    private $parser;
    private $values;
    private $previousContexts;
    private $currentKey;
    private $currentValue;
}
