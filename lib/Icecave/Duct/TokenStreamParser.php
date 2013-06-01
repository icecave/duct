<?php
namespace Icecave\Duct;

use Icecave\Collections\Stack;
use Icecave\Collections\Vector;
use Icecave\Duct\TypeCheck\TypeCheck;
use stdClass;

/**
 * Streaming token parser.
 *
 * Converts incoming streams of JSON tokens into PHP values.
 */
class TokenStreamParser
{
    public function __construct()
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->reset();
    }

    /**
     * Parse a stream of tokens representing one or more complete JSON values.
     *
     * @param mixed<Token> $tokens The stream of tokens to parse.
     *
     * @return Vector<mixed>             The sequence of parsed JSON values.
     * @throws Exception\ParserException Indicates that the token stream terminated midway through a JSON value.
     */
    public function parse($tokens)
    {
        $this->typeCheck->parse(func_get_args());

        $this->reset();
        $this->feed($tokens);
        $this->finalize();

        return $this->values();
    }

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset()
    {
        $this->typeCheck->reset(func_get_args());

        $this->stack = new Stack;
        $this->values = new Vector;
    }

    /**
     * Feed tokens to the parser.
     *
     * @param mixed<Token> $tokens The sequence of tokens.
     */
    public function feed($tokens)
    {
        $this->typeCheck->feed(func_get_args());

        foreach ($tokens as $token) {
            $this->feedToken($token);
        }
    }

    /**
     * Finalize parsing.
     *
     * @throws Exception\ParserException Indicates that the token stream terminated midway through a JSON value.
     */
    public function finalize()
    {
        $this->typeCheck->finalize(func_get_args());

        if (!$this->stack->isEmpty()) {
            throw new Exception\ParserException('Token stream ended while parsing ' . gettype($this->stack->next()->value) . '.');
        }
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
     * @param Token $token
     */
    private function feedToken(Token $token)
    {
        if (!$this->stack->isEmpty()) {
            switch ($this->stack->next()->state) {
                case ParserState::ARRAY_START():
                    return $this->doArrayStart($token);
                case ParserState::ARRAY_VALUE_SEPARATOR():
                    return $this->doArrayValueSeparator($token);
                case ParserState::OBJECT_START():
                    return $this->doObjectStart($token);
                case ParserState::OBJECT_KEY():
                    return $this->doObjectKey($token);
                case ParserState::OBJECT_KEY_SEPARATOR():
                    return $this->doObjectKeySeparator($token);
                case ParserState::OBJECT_VALUE_SEPARATOR():
                    return $this->doObjectValueSeparator($token);
            }
        }

        return $this->doValue($token);
    }

    /**
     * @param Token $token
     */
    private function doValue(Token $token)
    {
        switch ($token->type()) {
            case TokenType::BRACE_OPEN():
                $this->push(new stdClass, ParserState::OBJECT_START());
                break;

            case TokenType::BRACKET_OPEN():
                $this->push(array(), ParserState::ARRAY_START());
                break;

            case TokenType::STRING_LITERAL():
            case TokenType::BOOLEAN_LITERAL():
            case TokenType::NULL_LITERAL():
            case TokenType::NUMBER_LITERAL():
                $this->emit($token->value());
                break;

            case TokenType::BRACE_CLOSE():
            case TokenType::BRACKET_CLOSE():
            case TokenType::COLON():
            case TokenType::COMMA():
                throw $this->createUnexpectedTokenException($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doObjectStart(Token $token)
    {
        if (TokenType::BRACE_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } else {
            $this->setState(ParserState::OBJECT_KEY());
            $this->doObjectKey($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doObjectKey(Token $token)
    {
        if (TokenType::STRING_LITERAL() !== $token->type()) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->setObjectKey($token->value());
        $this->setState(ParserState::OBJECT_KEY_SEPARATOR());
    }

    /**
     * @param Token $token
     */
    private function doObjectKeySeparator(Token $token)
    {
        if (TokenType::COLON() !== $token->type()) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->setState(ParserState::BEGIN());
    }

    /**
     * @param Token $token
     */
    private function doObjectValueSeparator(Token $token)
    {
        if (TokenType::BRACE_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } elseif (TokenType::COMMA() === $token->type()) {
            $this->setState(ParserState::OBJECT_KEY());
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doArrayStart(Token $token)
    {
        if (TokenType::BRACKET_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } else {
            $this->setState(ParserState::BEGIN());
            $this->doValue($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doArrayValueSeparator(Token $token)
    {
        if (TokenType::BRACKET_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } elseif (TokenType::COMMA() === $token->type()) {
            $this->setState(ParserState::BEGIN());
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    /**
     * @param mixed $value
     */
    private function emit($value)
    {
        if ($this->stack->isEmpty()) {
            $this->values->pushBack($value);

            return;
        }

        $entry = $this->stack->next();

        if (is_object($entry->value)) {
            $entry->value->{$entry->key} = $value;
            $entry->state = ParserState::OBJECT_VALUE_SEPARATOR();
            $entry->key = null;
        } elseif (is_array($entry->value)) {
            $entry->value[] = $value;
            $entry->state = ParserState::ARRAY_VALUE_SEPARATOR();
        }
    }

    /**
     * @param ParserState $state
     */
    private function setState(ParserState $state)
    {
        $this->stack->next()->state = $state;
    }

    /**
     * @param string $key
     */
    private function setObjectKey($key)
    {
        $this->stack->next()->key = $key;
    }

    /**
     * @param mixed       $value
     * @param ParserState $state
     */
    private function push($value, ParserState $state)
    {
        $entry = new stdClass;
        $entry->value = $value;
        $entry->key = null;
        $entry->state = $state;
        $this->stack->push($entry);
    }

    /**
     * @return stdClass
     */
    private function pop()
    {
        return $this->stack->pop()->value;
    }

    /**
     * @param Token $token
     *
     * @return Exception\ParserException
     */
    private function createUnexpectedTokenException(Token $token)
    {
        if ($this->stack->isEmpty()) {
            return new Exception\ParserException('Unexpected token "' . $token->type() . '".');
        }

        return new Exception\ParserException('Unexpected token "' . $token->type() . '" in state "' . $this->stack->next()->state . '".');
    }

    private $typeCheck;
    private $stack;
    private $values;
}
