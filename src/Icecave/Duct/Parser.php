<?php
namespace Icecave\Duct;

use Icecave\Collections\Stack;
use Icecave\Collections\Vector;
use Icecave\Duct\Detail\Lexer;
use Icecave\Duct\Detail\TokenStreamParser;
use Icecave\Duct\TypeCheck\TypeCheck;
use stdClass;

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

        $this->values = new Vector;
        $this->stack = new Stack;
    }

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset()
    {
        $this->typeCheck->reset(func_get_args());

        parent::reset();

        $this->values->clear();
        $this->stack->clear();
    }

    /**
     * Parse one or more complete JSON values.
     *
     * @param string $buffer The JSON data.
     *
     * @return Vector<mixed>                      The sequence of parsed JSON values.
     * @throws Exception\SyntaxExceptionInterface
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

    /**
     * Called when the token stream parser emits a 'value' event.
     *
     * @param mixed $value The value emitted.
     */
    protected function onValue($value)
    {
        $this->typeCheck->onValue(func_get_args());

        if ($this->stack->isEmpty()) {
            $this->values->pushBack($value);
        } else {
            $context = $this->stack->next();

            if (is_array($context->value)) {
                $context->value[] = $value;
            } else {
                $context->value->{$context->key} = $value;
                $context->key = null;
            }
        }
    }

    /**
     * Called when the token stream parser emits an 'array-open' event.
     */
    protected function onArrayOpen()
    {
        $this->typeCheck->onArrayOpen(func_get_args());

        $this->push(array());
    }

    /**
     * Called when the token stream parser emits an 'array-close' event.
     */
    protected function onArrayClose()
    {
        $this->typeCheck->onArrayClose(func_get_args());

        $this->pop();
    }

    /**
     * Called when the token stream parser emits an 'object-open' event.
     */
    protected function onObjectOpen()
    {
        $this->typeCheck->onObjectOpen(func_get_args());

        $this->push(new stdClass);
    }

    /**
     * Called when the token stream parser emits an 'object-close' event.
     */
    protected function onObjectClose()
    {
        $this->typeCheck->onObjectClose(func_get_args());

        $this->pop();
    }

    /**
     * Called when the token stream parser emits an 'object-key' event.
     *
     * @param string $value The key for the next object value.
     */
    protected function onObjectKey($value)
    {
        $this->typeCheck->onObjectKey(func_get_args());

        $this->stack->next()->key = $value;
    }

    /**
     * Push a value onto the object stack.
     *
     * @param array|stdClass $value
     */
    protected function push($value)
    {
        $this->typeCheck->push(func_get_args());

        $context = new stdClass;
        $context->value = $value;
        $context->key = null;

        $this->stack->push($context);
    }

    /**
     * Pop a value from the object stack, emitting it if the stack is empty.
     */
    protected function pop()
    {
        $this->typeCheck->pop(func_get_args());

        $context = $this->stack->pop();

        $this->onValue($context->value);
    }

    private $typeCheck;
    private $values;
    private $stack;
}
