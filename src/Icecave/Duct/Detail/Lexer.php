<?php
namespace Icecave\Duct\Detail;

use Evenement\EventEmitter;
use Icecave\Duct\TypeCheck\TypeCheck;

/**
 * Streaming JSON lexer.
 *
 * Converts incoming streams of JSON data into tokens.
 */
class Lexer extends EventEmitter
{
    /**
     * @param string $encoding The encoding of the incoming JSON data stream.
     */
    public function __construct($encoding = 'UTF-8')
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->encoding = $encoding;

        $this->reset();
    }

    /**
     * Reset the lexer, discarding any untokened input.
     */
    public function reset()
    {
        $this->typeCheck->reset(func_get_args());

        $this->state = LexerState::BEGIN();
        $this->inputBuffer = '';
        $this->tokenBuffer = '';
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
        $this->typeCheck->feed(func_get_args());

        $length = strlen($buffer);

        for ($index = 0; $index < $length; ++$index) {
            $this->inputBuffer .= $buffer[$index];
            $this->consume();
        }
    }

    /**
     * Complete tokenization.
     *
     * @throws Exception\LexerException Indicates that the input terminated midway through a token.
     */
    public function finalize()
    {
        $this->typeCheck->finalize(func_get_args());

        switch ($this->state) {
            case LexerState::NUMBER_VALUE_NEGATIVE():
            case LexerState::NUMBER_VALUE_EXPONENT_START():
            case LexerState::STRING_VALUE():
            case LexerState::STRING_VALUE_ESCAPED():
            case LexerState::STRING_VALUE_UNICODE():
            case LexerState::TRUE_VALUE():
            case LexerState::FALSE_VALUE():
            case LexerState::NULL_VALUE():
                throw new Exception\LexerException('Character stream ended while scanning literal value.');

            case LexerState::NUMBER_VALUE():
            case LexerState::NUMBER_VALUE_LEADING_ZERO():
                $this->emitLiteral(intval($this->tokenBuffer));
                break;

            case LexerState::NUMBER_VALUE_DECIMAL():
            case LexerState::NUMBER_VALUE_EXPONENT():
                $this->emitLiteral(floatval($this->tokenBuffer));
                break;
        }
    }

    private function consume()
    {
        if (!mb_check_encoding($this->inputBuffer, $this->encoding)) {
            return;
        }

        $char = $this->inputBuffer;
        $this->inputBuffer = '';

        switch ($this->state) {
            case LexerState::STRING_VALUE():
                return $this->doStringValue($char);
            case LexerState::STRING_VALUE_ESCAPED():
                return $this->doStringValueEscaped($char);
            case LexerState::STRING_VALUE_UNICODE():
                return $this->doStringValueUnicode($char);
            case LexerState::NUMBER_VALUE():
                return $this->doNumberValue($char);
            case LexerState::NUMBER_VALUE_NEGATIVE():
                return $this->doNumberValueNegative($char);
            case LexerState::NUMBER_VALUE_LEADING_ZERO():
                return $this->doNumberValueLeadingZero($char);
            case LexerState::NUMBER_VALUE_DECIMAL():
                return $this->doNumberValueDecimal($char);
            case LexerState::NUMBER_VALUE_EXPONENT_START():
                return $this->doNumberValueExponentStart($char);
            case LexerState::NUMBER_VALUE_EXPONENT():
                return $this->doNumberValueExponent($char);
            case LexerState::TRUE_VALUE():
                return $this->doTrueValue($char);
            case LexerState::FALSE_VALUE():
                return $this->doFalseValue($char);
            case LexerState::NULL_VALUE():
                return $this->doNullValue($char);
        }

        return $this->doBegin($char);
    }

    /**
     * @param string $char
     */
    private function doBegin($char)
    {
        if ('"' === $char) {
            $this->state = LexerState::STRING_VALUE();
        } elseif ('0' === $char) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::NUMBER_VALUE_LEADING_ZERO();
        } elseif ('-' === $char) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::NUMBER_VALUE_NEGATIVE();
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::NUMBER_VALUE();
        } elseif ('t' === $char) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::TRUE_VALUE();
        } elseif ('f' === $char) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::FALSE_VALUE();
        } elseif ('n' === $char) {
            $this->tokenBuffer = $char;
            $this->state = LexerState::NULL_VALUE();
        } elseif (false !== strpos('{}[]:,', $char)) {
            $this->emitSpecial($char);
        } elseif (!$this->isWhitespace($char)) {
            throw new Exception\LexerException('Unexpected character: "' . $char . '".');
        }
    }

    /**
     * @param string $char
     */
    private function doStringValue($char)
    {
        if ('\\' === $char) {
            $this->state = LexerState::STRING_VALUE_ESCAPED();
        } elseif (null !== $this->unicodeHighSurrogate) {
            throw new Exception\LexerException('Missing low surrogate for unicode surrogate pair.');
        } elseif ('"' === $char) {
            $this->emitLiteral($this->tokenBuffer);
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
            $this->state = LexerState::STRING_VALUE_UNICODE();
        } elseif (null !== $this->unicodeHighSurrogate) {
            throw new Exception\LexerException('Missing low surrogate for unicode surrogate pair.');
        } elseif (array_key_exists($char, self::$escapeSequences)) {
            $this->tokenBuffer .= self::$escapeSequences[$char];
            $this->state = LexerState::STRING_VALUE();
        } else {
            throw new Exception\LexerException('Invalid escape sequence.');
        }
    }

    /**
     * @param string $char
     */
    private function doStringValueUnicode($char)
    {
        if (!ctype_xdigit($char)) {
            throw new Exception\LexerException('Invalid escape sequence.');
        }

        $this->unicodeBuffer .= $char;

        if (4 === strlen($this->unicodeBuffer)) {
            $codepoint = hexdec($this->unicodeBuffer);

            // Store high surrogate for combination later ...
            if ($this->isUnicodeHighSurrogate($codepoint)) {
                if (null !== $this->unicodeHighSurrogate) {
                    throw new Exception\LexerException('Multiple high surrogates for unicode surrogate pair.');
                }
                $this->unicodeHighSurrogate = $codepoint;

            // Combine high + low surrogate ...
            } elseif ($this->isUnicodeLowSurrogate($codepoint)) {
                if (null === $this->unicodeHighSurrogate) {
                    throw new Exception\LexerException('Missing high surrogate for unicode surrogate pair.');
                }
                $codepoint = $this->combineUnicodeSurrogateCodepoints($this->unicodeHighSurrogate, $codepoint);
                $this->tokenBuffer .= $this->convertUnicodeCodepoint($codepoint);
                $this->unicodeHighSurrogate = null;

            // Regular (non-surrogate) code-point ...
            } else {
                $this->tokenBuffer .= $this->convertUnicodeCodepoint($codepoint);
            }

            $this->state = LexerState::STRING_VALUE();
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
            $this->state = LexerState::NUMBER_VALUE_DECIMAL();
        } elseif ('e' === $char || 'E' === $char) {
            $this->tokenBuffer .= 'e';
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START();
        } else {
            $this->emitLiteral(intval($this->tokenBuffer));
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
            $this->state = LexerState::NUMBER_VALUE_LEADING_ZERO();
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE();
        } else {
            throw new Exception\LexerException('Expected digit after negative sign.');
        }
    }

    /**
     * @param string $char
     */
    private function doNumberValueLeadingZero($char)
    {
        if ('.' === $char) {
            $this->tokenBuffer .= '.';
            $this->state = LexerState::NUMBER_VALUE_DECIMAL();
        } elseif ('e' === $char || 'E' === $char) {
            $this->tokenBuffer .= 'e';
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START();
        } else {
            $this->emitLiteral(intval($this->tokenBuffer));
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
            $this->state = LexerState::NUMBER_VALUE_EXPONENT_START();
        } elseif ('.' === substr($this->tokenBuffer, -1)) {
            throw new Exception\LexerException('Expected digit after decimal point.');
        } else {
            $this->emitLiteral(floatval($this->tokenBuffer));
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
            $this->state = LexerState::NUMBER_VALUE_EXPONENT();
        } elseif (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
            $this->state = LexerState::NUMBER_VALUE_EXPONENT();
        } else {
            throw new Exception\LexerException('Expected digit or +/- as exponent.');
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
            $this->emitLiteral(floatval($this->tokenBuffer));
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
            $this->emitLiteral(true);
        }
    }

    /**
     * @param string $char
     */
    private function doFalseValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('false')) {
            $this->emitLiteral(false);
        }
    }

    /**
     * @param string $char
     */
    private function doNullValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('null')) {
            $this->emitLiteral(null);
        }
    }

    /**
     * @param string $char
     */
    private function isWhitespace($char)
    {
        return preg_match('/\s/u', $char);
    }

    /**
     * @param string $char
     */
    private function emitSpecial($char)
    {
        $this->emit('token', array(Token::createSpecial($char)));
        $this->tokenBuffer = '';
        $this->state = LexerState::BEGIN();
    }

    /**
     * @param string $value
     */
    private function emitLiteral($value)
    {
        $this->emit('token', array(Token::createLiteral($value)));
        $this->tokenBuffer = '';
        $this->state = LexerState::BEGIN();
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

        throw new Exception\LexerException('Expected "' . $string . '", got "' . $this->tokenBuffer . '".');
    }

    /**
     * @param integer $codepoint
     */
    private function isUnicodeHighSurrogate($codepoint)
    {
        return $codepoint >= 0xd800 && $codepoint < 0xdbff;
    }

    /**
     * @param integer $codepoint
     */
    private function isUnicodeLowSurrogate($codepoint)
    {
        return $codepoint >= 0xdc00 && $codepoint < 0xdfff;
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

    private static $escapeSequences = array(
        'b'  => "\x08",
        'f'  => "\f",
        'n'  => "\n",
        'r'  => "\r",
        't'  => "\t",
        '"'  => '"',
        '/'  => '/',
        '\\' => '\\',
    );

    private $typeCheck;
    private $encoding;
    private $state;
    private $inputBuffer;
    private $tokenBuffer;
    private $unicodeBuffer;
    private $unicodeHighSurrogate;
}
