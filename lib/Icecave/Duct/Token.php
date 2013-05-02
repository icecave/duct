<?php
namespace Icecave\Duct;

class Token
{
    public function __construct(TokenType $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public static function createSpecial($value)
    {
        return new Token(TokenType::instanceByValue($value), $value);
    }

    public static function createLiteral($value)
    {
        if (is_integer($value) || is_float($value)) {
            return new Token(TokenType::NUMBER_LITERAL(), $value);
        } elseif (is_bool($value)) {
            return new Token(TokenType::BOOLEAN_LITERAL(), $value);
        } elseif (is_null($value)) {
            return new Token(TokenType::NULL_LITERAL(), null);
        } else {
            return new Token(TokenType::STRING_LITERAL(), strval($value));
        }
    }

    public function type()
    {
        return $this->type;
    }

    public function value()
    {
        return $this->value;
    }

    private $type;
    private $value;
}
