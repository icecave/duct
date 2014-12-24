<?php
namespace Icecave\Duct\Detail;

use Evenement\EventEmitter;
use Icecave\Duct\Detail\Exception\ParserException;
use SplStack;
use stdClass;

/**
 * Streaming token parser.
 *
 * Converts incoming streams of JSON tokens into PHP values.
 *
 * @internal
 */
class TokenStreamParser extends EventEmitter
{
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the parser, discarding any previously parsed input and values.
     */
    public function reset()
    {
        $this->state = null;
        $this->previousStates = [];
    }

    /**
     * Feed tokens to the parser.
     *
     * @param mixed<Token> $tokens The sequence of tokens.
     *
     * @throws ParserException
     */
    public function feed($tokens)
    {
        foreach ($tokens as $token) {
            $this->feedToken($token);
        }
    }

    /**
     * Finalize parsing.
     *
     * @throws ParserException Indicates that the token stream terminated midway through a JSON value.
     */
    public function finalize()
    {
        if ($this->state) {
            throw new ParserException('Token stream ended unexpectedly.');
        }
    }

    /**
     * @param Token $token
     */
    public function feedToken(Token $token)
    {
        switch ($this->state) {
            case ParserState::ARRAY_START:
                return $this->doArrayStart($token);
            case ParserState::ARRAY_VALUE_SEPARATOR:
                return $this->doArrayValueSeparator($token);
            case ParserState::OBJECT_START:
                return $this->doObjectStart($token);
            case ParserState::OBJECT_KEY:
                return $this->doObjectKey($token);
            case ParserState::OBJECT_KEY_SEPARATOR:
                return $this->doObjectKeySeparator($token);
            case ParserState::OBJECT_VALUE_SEPARATOR:
                return $this->doObjectValueSeparator($token);
        }

        return $this->doValue($token);
    }

    /**
     * @param Token $token
     */
    private function doValue(Token $token)
    {
        switch ($token->type) {
            case TokenType::BRACE_OPEN:
                $this->previousStates[] = $this->state;
                $this->state = ParserState::OBJECT_START;
                $this->emit('object-open');
                break;

            case TokenType::BRACKET_OPEN:
                $this->previousStates[] = $this->state;
                $this->state = ParserState::ARRAY_START;
                $this->emit('array-open');
                break;

            case TokenType::STRING_LITERAL:
            case TokenType::BOOLEAN_LITERAL:
            case TokenType::NULL_LITERAL:
            case TokenType::NUMBER_LITERAL:
                $this->endValue();
                $this->emit('value', [$token->value]);
                break;

            default:
                throw $this->createUnexpectedTokenException($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doObjectStart(Token $token)
    {
        if (TokenType::BRACE_CLOSE === $token->type) {
            $this->state = array_pop($this->previousStates);
            $this->endValue();
            $this->emit('object-close');
        } else {
            $this->state = ParserState::OBJECT_KEY;
            $this->feedToken($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doObjectKey(Token $token)
    {
        if (TokenType::STRING_LITERAL !== $token->type) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->state = ParserState::OBJECT_KEY_SEPARATOR;
        $this->emit('object-key', [$token->value]);
    }

    /**
     * @param Token $token
     */
    private function doObjectKeySeparator(Token $token)
    {
        if (TokenType::COLON !== $token->type) {
            throw $this->createUnexpectedTokenException($token);
        }

        $this->state = ParserState::OBJECT_VALUE;
    }

    /**
     * @param Token $token
     */
    private function doObjectValueSeparator(Token $token)
    {
        if (TokenType::BRACE_CLOSE === $token->type) {
            $this->state = array_pop($this->previousStates);
            $this->endValue();
            $this->emit('object-close');
        } elseif (TokenType::COMMA === $token->type) {
            $this->state = ParserState::OBJECT_KEY;
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doArrayStart(Token $token)
    {
        if (TokenType::BRACKET_CLOSE === $token->type) {
            $this->state = array_pop($this->previousStates);
            $this->endValue();
            $this->emit('array-close');
        } else {
            $this->state = ParserState::ARRAY_VALUE;
            $this->feedToken($token);
        }
    }

    /**
     * @param Token $token
     */
    private function doArrayValueSeparator(Token $token)
    {
        if (TokenType::BRACKET_CLOSE === $token->type) {
            $this->state = array_pop($this->previousStates);
            $this->endValue();
            $this->emit('array-close');
        } elseif (TokenType::COMMA === $token->type) {
            $this->state = ParserState::ARRAY_VALUE;
        } else {
            throw $this->createUnexpectedTokenException($token);
        }
    }

    private function endValue()
    {
        if (ParserState::ARRAY_VALUE === $this->state) {
            $this->state = ParserState::ARRAY_VALUE_SEPARATOR;
        } elseif (ParserState::OBJECT_VALUE === $this->state) {
            $this->state = ParserState::OBJECT_VALUE_SEPARATOR;
        }
    }

    /**
     * @param Token $token
     *
     * @return ParserException
     */
    private function createUnexpectedTokenException(Token $token)
    {
        if ($this->state) {
            return new ParserException(
                'Unexpected token "' . TokenType::memberByValue($token->type) . '" in state "' . ParserState::memberByValue($this->state) . '".'
            );
        }

        return new ParserException(
            'Unexpected token "' . TokenType::memberByValue($token->type) . '".'
        );
    }

    private $state;
    private $previousStates;
}
