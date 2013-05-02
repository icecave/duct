<?php
namespace Icecave\Duct;

use Icecave\Collections\Stack;
use Icecave\Collections\Vector;
use stdClass;

class TokenStreamParser
{
    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->stack = new Stack;
        $this->values = new Vector;
    }

    public function parse($tokens)
    {
        $this->reset();
        $this->feed($tokens);
        $this->finalize();

        return $this->values();
    }

    public function feed($tokens)
    {
        foreach ($tokens as $token) {
            $this->feedToken($token);
        }
    }

    public function finalize()
    {
        if (!$this->stack->isEmpty()) {
            throw new Exception\ParserException('Token stream ended while parsing ' . gettype($this->stack->next()->value) . '.');
        }
    }

    public function values()
    {
        $values = clone $this->values;
        $this->values->clear();

        return $values;
    }

    protected function feedToken(Token $token)
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

    protected function doValue(Token $token)
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

    protected function doObjectStart(Token $token)
    {
        if (TokenType::BRACE_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } else {
            $this->setState(ParserState::OBJECT_KEY());
            $this->doObjectKey($token);
        }
    }

    protected function doObjectKey(Token $token)
    {
        if (TokenType::STRING_LITERAL() !== $token->type()) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->setObjectKey($token->value());
        $this->setState(ParserState::OBJECT_KEY_SEPARATOR());
    }

    protected function doObjectKeySeparator(Token $token)
    {
        if (TokenType::COLON() !== $token->type()) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->setState(ParserState::BEGIN());
    }

    protected function doObjectValueSeparator(Token $token)
    {
        if (TokenType::BRACE_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } elseif (TokenType::COMMA() === $token->type()) {
            $this->setState(ParserState::OBJECT_KEY());
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    protected function doArrayStart(Token $token)
    {
        if (TokenType::BRACKET_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } else {
            $this->setState(ParserState::BEGIN());
            $this->doValue($token);
        }
    }

    protected function doArrayValueSeparator(Token $token)
    {
        if (TokenType::BRACKET_CLOSE() === $token->type()) {
            $this->emit($this->pop());
        } elseif (TokenType::COMMA() === $token->type()) {
            $this->setState(ParserState::BEGIN());
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    protected function emit($value)
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

    protected function setState(ParserState $state)
    {
        $this->stack->next()->state = $state;
    }

    protected function setObjectKey($key)
    {
        $this->stack->next()->key = $key;
    }

    protected function push($value, ParserState $state)
    {
        $entry = new stdClass;
        $entry->value = $value;
        $entry->key = null;
        $entry->state = $state;
        $this->stack->push($entry);
    }

    protected function pop()
    {
        return $this->stack->pop()->value;
    }

    protected function createUnexpectedTokenException(Token $token)
    {
        if ($this->stack->isEmpty()) {
            return new Exception\ParserException('Unexpected token "' . $token->type() . '".');
        }

        return new Exception\ParserException('Unexpected token "' . $token->type() . '" in state "' . $this->stack->next()->state . '".');
    }

    private $stack;
    private $values;
}
