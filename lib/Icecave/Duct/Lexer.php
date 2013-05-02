<?php
namespace Icecave\Duct;

use Icecave\Collections\Vector;

class Lexer
{
    public function __construct($encoding = 'UTF-8')
    {
        $this->encoding = $encoding;

        $this->reset();
    }

    public function reset()
    {
        $this->state = LexerState::BEGIN();
        $this->inputBuffer = '';
        $this->tokenBuffer = '';
        $this->unicodeBuffer = '';
        $this->tokens = new Vector;
    }

    public function lex($buffer)
    {
        $this->reset();
        $this->feed($buffer);
        $this->finalize();

        return $this->tokens();
    }

    /**
     * @param string $buffer
     */
    public function feed($buffer)
    {
        $length = strlen($buffer);

        for ($index = 0; $index < $length; ++$index) {
            $this->inputBuffer .= $buffer[$index];
            $this->consume();
        }
    }

    public function finalize()
    {
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

    public function tokens()
    {
        $tokens = clone $this->tokens;
        $this->tokens->clear();

        return $tokens;
    }

    protected function consume()
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

    protected function doBegin($char)
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

    protected function doStringValue($char)
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

    protected function doStringValueEscaped($char)
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

    protected function doStringValueUnicode($char)
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

    protected function doNumberValue($char)
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

    protected function doNumberValueNegative($char)
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

    protected function doNumberValueLeadingZero($char)
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

    protected function doNumberValueDecimal($char)
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

    protected function doNumberValueExponentStart($char)
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

    protected function doNumberValueExponent($char)
    {
        if (ctype_digit($char)) {
            $this->tokenBuffer .= $char;
        } else {
            $this->emitLiteral(floatval($this->tokenBuffer));
            $this->doBegin($char);
        }
    }

    protected function doTrueValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('true')) {
            $this->emitLiteral(true);
        }
    }

    protected function doFalseValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('false')) {
            $this->emitLiteral(false);
        }
    }

    protected function doNullValue($char)
    {
        $this->tokenBuffer .= $char;

        if ($this->expectString('null')) {
            $this->emitLiteral(null);
        }
    }

    protected function isWhitespace($char)
    {
        return preg_match('/\s/u', $char);
    }

    protected function emitSpecial($char)
    {
        $this->tokens->pushBack(Token::createSpecial($char));
        $this->tokenBuffer = '';
        $this->state = LexerState::BEGIN();
    }

    protected function emitLiteral($value)
    {
        $this->tokens->pushBack(Token::createLiteral($value));
        $this->tokenBuffer = '';
        $this->state = LexerState::BEGIN();
    }

    protected function expectString($string)
    {
        if ($this->tokenBuffer === $string) {
            return true;
        } elseif (substr($string, 0, strlen($this->tokenBuffer)) === $this->tokenBuffer) {
            return false;
        }

        throw new Exception\LexerException('Expected "' . $string . '", got "' . $this->tokenBuffer . '".');
    }

    protected function isUnicodeHighSurrogate($codepoint)
    {
        return $codepoint >= 0xd800 && $codepoint < 0xdbff;
    }

    protected function isUnicodeLowSurrogate($codepoint)
    {
        return $codepoint >= 0xdc00 && $codepoint < 0xdfff;
    }

    protected function combineUnicodeSurrogateCodepoints($highSurrogate, $lowSurrogate)
    {
        return 0x10000 + ($highSurrogate - 0xd800) * 0x400 + ($lowSurrogate - 0xdc00);
    }

    protected function convertUnicodeCodepoint($codepoint)
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

    private $encoding;
    private $state;
    private $inputBuffer;
    private $tokenBuffer;
    private $unicodeBuffer;
    private $unicodeHighSurrogate;
    private $tokens;
}
