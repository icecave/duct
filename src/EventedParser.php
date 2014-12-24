<?php
namespace Icecave\Duct;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;
use Exception;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\ParserTrait;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\Exception\SyntaxExceptionInterface;

/**
 * Streaming JSON parser.
 *
 * Converts incoming streams of JSON data into PHP values.
 */
class EventedParser implements ParserInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(
        Lexer $lexer = null,
        TokenStreamParser $parser = null
    ) {
        if (null === $lexer) {
            $lexer = new Lexer();
        }

        if (null === $parser) {
            $parser = new TokenStreamParser();
        }

        $this->lexer  = $lexer;
        $this->parser = $parser;
        $this->depth  = 0;

        $this->lexer->on(
            'token',
            [$this->parser, 'feedToken']
        );

        $this->parser->on(
            'value',
            function ($value) {
                if (0 === $this->depth) {
                    $this->emit('document-open');
                    $this->emit('value', array($value));
                    $this->emit('document-close');
                } else {
                    $this->emit('value', array($value));
                }
            }
        );

        $this->parser->on(
            'array-open',
            function () {
                if (0 === $this->depth++) {
                    $this->emit('document-open');
                }

                $this->emit('array-open');
            }
        );

        $this->parser->on(
            'array-close',
            function () {
                $this->emit('array-close');

                if (0 === --$this->depth) {
                    $this->emit('document-close');
                }
            }
        );

        $this->parser->on(
            'object-open',
            function () {
                if (0 === $this->depth++) {
                    $this->emit('document-open');
                }

                $this->emit('object-open');
            }
        );

        $this->parser->on(
            'object-close',
            function () {
                $this->emit('object-close');

                if (0 === --$this->depth) {
                    $this->emit('document-close');
                }
            }
        );

        $this->parser->on(
            'object-key',
            function ($value) {
                $this->emit('object-key', array($value));
            }
        );
    }

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
            $this->emit('error', array($e));
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
            $this->emit('error', array($e));
        }
    }

    private $lexer;
    private $parser;
    private $depth;
}
