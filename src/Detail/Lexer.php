<?php
namespace Icecave\Duct\Detail;

use Icecave\Duct\Detail\Exception\LexerException;

/**
 * Streaming JSON lexer.
 *
 * Converts incoming streams of JSON data into tokens.
 *
 * @internal
 */
class Lexer
{
    /**
     * @param string $encoding The encoding of the incoming JSON data stream.
     */
    public function __construct($encoding = 'UTF-8')
    {
        $this->encoding = $encoding;

        $this->reset();
    }

    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Reset the lexer, discarding any untokened input.
     */
    public function reset()
    {
        $this->state         = LexerState::BEGIN;
        $this->inputBuffer   = '';
        $this->tokenBuffer   = '';
        $this->unicodeBuffer = '';
    }

    /**
     * Feed JSON data to the lexer.
     *
     * @param string $buffer The JSON data.
     *
     * @throws Exception\LexerException
     */
    public function feed($buffer)
    {
        $this->inputBuffer .= $buffer;

        $byteCount = 0;
        $charCount = 0;

        while (true) {
            $char = mb_substr($this->inputBuffer, 0, 1, $this->encoding);

            if ($char === false || $char === '') {
                break;
            }

            $this->inputBuffer = substr($this->inputBuffer, strlen($char));

            if (LexerState::STRING_VALUE === $this->state) {
                $this->doStringValue($char);
            } elseif (LexerState::STRING_VALUE_ESCAPED === $this->state) {
                $this->doStringValueEscaped($char);
            } elseif (LexerState::STRING_VALUE_UNICODE === $this->state) {
                $this->doStringValueUnicode($char);
            } elseif (LexerState::NUMBER_VALUE === $this->state) {
                $this->doNumberValue($char);
            } elseif (LexerState::NUMBER_VALUE_NEGATIVE === $this->state) {
                $this->doNumberValueNegative($char);
            } elseif (LexerState::NUMBER_VALUE_LEADING_ZERO === $this->state) {
                $this->doNumberValueLeadingZero($char);
            } elseif (LexerState::NUMBER_VALUE_DECIMAL === $this->state) {
                $this->doNumberValueDecimal($char);
            } elseif (LexerState::NUMBER_VALUE_EXPONENT_START === $this->state) {
                $this->doNumberValueExponentStart($char);
            } elseif (LexerState::NUMBER_VALUE_EXPONENT === $this->state) {
                $this->doNumberValueExponent($char);
            } elseif (LexerState::TRUE_VALUE === $this->state) {
                $this->doTrueValue($char);
            } elseif (LexerState::FALSE_VALUE === $this->state) {
                $this->doFalseValue($char);
            } elseif (LexerState::NULL_VALUE === $this->state) {
                $this->doNullValue($char);
            } else {
                $this->doBegin($char);
            }
        }
    }

    /**
     * Complete tokenization.
     *
     * @throws Exception\LexerException Indicates that the input terminated midway through a token.
     */
    public function finalize()
    {
        switch ($this->state) {
            case LexerState::NUMBER_VALUE_NEGATIVE:
            case LexerState::NUMBER_VALUE_EXPONENT_START:
            case LexerState::STRING_VALUE:
            case LexerState::STRING_VALUE_ESCAPED:
            case LexerState::STRING_VALUE_UNICODE:
            case LexerState::TRUE_VALUE:
            case LexerState::FALSE_VALUE:
            case LexerState::NULL_VALUE:
                throw new LexerException('Character stream ended while scanning literal value.');

            case LexerState::NUMBER_VALUE:
            case LexerState::NUMBER_VALUE_LEADING_ZERO:
                $this->emitLiteral(TokenType::NUMBER_LITERAL, intval($this->tokenBuffer));
                break;

            case LexerState::NUMBER_VALUE_DECIMAL:
            case LexerState::NUMBER_VALUE_EXPONENT:
                $this->emitLiteral(TokenType::NUMBER_LITERAL, floatval($this->tokenBuffer));
                break;
        }
    }

    /**
     * @param string $char
     */
    private function doBegin($char)
    {
        if ('"' === $char) {
            $this->state = LexerState::STRING_VALUE;
        } elseif ('0' === $char) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::NUMBER_VALUE_LEADING_ZERO;
        } elseif ('-' === $char) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::NUMBER_VALUE_NEGATIVE;
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::NUMBER_VALUE;
        } elseif ('t' === $char) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::TRUE_VALUE;
        } elseif ('f' === $char) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::FALSE_VALUE;
        } elseif ('n' === $char) {
            $this->tokenBuffer = $char;
            $this->state       = LexerState::NULL_VALUE;
        } elseif (false !== strpos('{}[]:,', $char)) {
            $this->emitSpecial($char);
        } elseif (!ctype_space($char)) {
            throw new LexerException('Unexpected character: "' . $char . '".');
        }
    }

    /**
     * @param string $char
     */
    private function doStringValue($char)
    {
        if ('\\' === $char) {
            $this->state = LexerState::STRING_VALUE_ESCAPED;
        } elseif (null !== $this->unicodeHighSurrogate) {
            throw new LexerException('Missing low surrogate for unicode surrogate pair.');
        } elseif ('"' === $char) {
            $this->emitLiteral(TokenType::STRING_LITERAL, $this->tokenBuffer);
        } else {
            $this->tokenBuffer .= $char;
        }
    }

    /**
     * @param string $char
     */
    private function doStringValueEscaped($char)
    {
        if ('u' === $char) {
            $this->unicodeBuffer = '';
            $this->state         = LexerState::STRING_VALUE_UNICODE;
        } elseif (null !== $this->unicodeHighSurrogate) {
            throw new LexerException('Missing low surrogate for unicode surrogate pair.');
        } elseif (array_key_exists($char, self::$escapeSequences)) {
            $this->tokenBuffer .= self::$escapeSequences[$char];
            $this->state = LexerState::STRING_VALUE;
        } else {
            throw new LexerException('Invalid escape sequence.');
        }
    }

    /**
     * @param string $char
     */
    private function doStringValueUnicode($char)
    {
        if (!ctype_xdigit($char)) {
            throw new LexerException('Invalid escape sequence.');
        }

        $this->unicodeBuffer .= $char;

        if (4 === strlen($this->unicodeBuffer)) {
            $codepoint = hexdec($this->unicodeBuffer);

            // Store high surrogate for combination later if is high surrogate...
            if ($codepoint >= 0xd800 && $codepoint <= 0xdbff) {
                if (null !== $this->unicodeHighSurrogate) {
                    throw new LexerException('Multiple high surrogates for unicode surrogate pair.');
                }
                $this->unicodeHighSurrogate = $codepoint;

            // Combine high + low surrogate if is low surrogate ...
            } elseif ($codepoint >= 0xdc00 && $codepoint <= 0xdfff) {
                if (null === $this->unicodeHighSurrogate) {
                    throw new LexerException('Missing high surrogate for unicode surrogate pair.');
                }
                $codepoint = $this->combineUnicodeSurrogateCodepoints($this->unicodeHighSurrogate, $codepoint);
                $this->tokenBuffer .= $this->convertUnicodeCodepoint($codepoint);
                $this->unicodeHighSurrogate = null;

            // Regular (non-surrogate) code-point ...
            } else {
                $this->tokenBuffer .= $this->convertUnicodeCodepoint($codepoint);
            }

            $this->state = LexerState::STRING_VALUE;
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValue($char)
    {
        if (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
        } elseif ('.' === $char) {
            $this->tokenBuffer .= '.';
            $this->state = LexerState::NUMBER_VALUE_DECIMAL;
        } elseif ('e' === $char || 'E' === $char) {
            $this->tokenBuffer .= 'e';
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START;
        } else {
            $this->emitLiteral(TokenType::NUMBER_LITERAL, intval($this->tokenBuffer));
            $this->doBegin($char);
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueNegative($char)
    {
        if ('0' === $char) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE_LEADING_ZERO;
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE;
        } else {
            throw new LexerException('Expected digit after negative sign.');
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueLeadingZero($char)
    {
        if ('.' === $char) {
            $this->tokenBuffer .= '.';
            $this->state = LexerState::NUMBER_VALUE_DECIMAL;
        } elseif ('e' === $char || 'E' === $char) {
            $this->tokenBuffer .= 'e';
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START;
        } else {
            $this->emitLiteral(TokenType::NUMBER_LITERAL, intval($this->tokenBuffer));
            $this->doBegin($char);
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueDecimal($char)
    {
        if (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
        } elseif ('e' === $char || 'E' === $char) {
            $this->tokenBuffer .= 'e';
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START;
        } elseif ('.' === substr($this->tokenBuffer, -1)) {
            throw new LexerException('Expected digit after decimal point.');
        } else {
            $this->emitLiteral(TokenType::NUMBER_LITERAL, floatval($this->tokenBuffer));
            $this->doBegin($char);
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueExponentStart($char)
    {
        if ('+' === $char || '-' === $char) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE_EXPONENT;
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE_EXPONENT;
        } else {
            throw new LexerException('Expected digit or +/- as exponent.');
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueExponent($char)
    {
        if (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
        } else {
            $this->emitLiteral(TokenType::NUMBER_LITERAL, floatval($this->tokenBuffer));
            $this->doBegin($char);
        }
    }

    /**
     * @param string $char
     */
    private function doTrueValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('true')) {
            $this->emitLiteral(TokenType::BOOLEAN_LITERAL, true);
        }
    }

    /**
     * @param string $char
     */
    private function doFalseValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('false')) {
            $this->emitLiteral(TokenType::BOOLEAN_LITERAL, false);
        }
    }

    /**
     * @param string $char
     */
    private function doNullValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('null')) {
            $this->emitLiteral(TokenType::NULL_LITERAL, null);
        }
    }

    /**
     * @param string $char
     */
    private function emitSpecial($char)
    {
        $callback = $this->callback;
        $callback(new Token($char, $char));

        $this->tokenBuffer = '';
        $this->state       = LexerState::BEGIN;
    }

    /**
     * @param integer $type
     * @param string  $value
     */
    private function emitLiteral($type, $value)
    {
        $callback = $this->callback;
        $callback(new Token($type, $value));

        $this->tokenBuffer = '';
        $this->state       = LexerState::BEGIN;
    }

    /**
     * @param string $string
     */
    private function expectString($string)
    {
        if ($this->tokenBuffer === $string) {
            return true;
        } elseif (substr($string, 0, strlen($this->tokenBuffer)) === $this->tokenBuffer) {
            return false;
        }

        throw new LexerException('Expected "' . $string . '", got "' . $this->tokenBuffer . '".');
    }

    /**
     * @param integer $highSurrogate
     * @param integer $lowSurrogate
     */
    private function combineUnicodeSurrogateCodepoints($highSurrogate, $lowSurrogate)
    {
        return 0x10000 + ($highSurrogate - 0xd800) * 0x400 + ($lowSurrogate - 0xdc00);
    }

    /**
     * @param integer $codepoint
     */
    private function convertUnicodeCodepoint($codepoint)
    {
        return mb_convert_encoding(
            sprintf('&#%04d;', $codepoint),
            $this->encoding,
            'HTML-ENTITIES'
        );
    }

    private static $escapeSequences = [
        'b'  => "\x08",
        'f'  => "\f",
        'n'  => "\n",
        'r'  => "\r",
        't'  => "\t",
        '"'  => '"',
        '/'  => '/',
        '\\' => '\\',
    ];

    private $encoding;
    private $callback;
    private $state;
    private $inputBuffer;
    private $tokenBuffer;
    private $unicodeBuffer;
    private $unicodeHighSurrogate;
}
