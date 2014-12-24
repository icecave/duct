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
    use ParserTrait {
        feed as private doFeed;
        finalize as private doFinalize;
    }

    /**
     * @param Lexer|null             $lexer  The lexer to use for tokenization, or NULL to use the default UTF-8 lexer.
     * @param TokenStreamParser|null $parser The token-stream parser to use for converting tokens into PHP values, or null to use the default.
     */
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        $this->depth = 0;

        $this->initialize($lexer, $parser);
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
            $this->doFeed($buffer);
        } catch (Exception $e) {
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
            $this->doFinalize();
        } catch (Exception $e) {
            $this->emit('error', array($e));
        }
    }

    /**
     * Called when the token stream parser emits a 'value' event.
     *
     * @param mixed $value The value emitted.
     */
    protected function onValue($value)
    {
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
        $this->emit('object-key', array($value));
    }

    private $depth;
}
