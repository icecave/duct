<?php
namespace Icecave\Duct;

use stdClass;
use Icecave\Collections\Stack;
use Icecave\Collections\Vector;
/**
*
*/

// 3.  Encoding

//    JSON text SHALL be encoded in Unicode.  The default encoding is
//    UTF-8.

//    Since the first two characters of a JSON text will always be ASCII
//    characters [RFC0020], it is possible to determine whether an octet
//    stream is UTF-8, UTF-16 (BE or LE), or UTF-32 (BE or LE) by looking
//    at the pattern of nulls in the first four octets.

//            00 00 00 xx  UTF-32BE
//            00 xx 00 xx  UTF-16BE
//            xx 00 00 00  UTF-32LE
//            xx 00 xx 00  UTF-16LE
//            xx xx xx xx  UTF-8

class Parser
{
    public function __construct($encoding = 'UTF-8')
    {
        $this->encoding = $encoding;

        $this->reset();
    }

    public function reset()
    {
        $this->state = ParserState::BEGIN_OBJECT();
        $this->inputBuffer = '';
        $this->objects = new Vector;
        $this->stack = new Stack;
    }

    /**
     * @param string $buffer
     *
     * @return array<mixed>
     */
    public function feed($buffer)
    {
        $length = strlen($buffer);

        for ($index = 0; $index < $length; ++$buffer) {
            $this->inputBuffer .= $buffer[$index];
            $this->consume();
        }

        $objects = clone $this->objects;
        $this->objects->clear();

        return $objects;
    }

    protected function consume()
    {
        if (!mb_check_encoding($this->inputBuffer, $this->encoding)) {
            return;
        }

        $char = $this->inputBuffer;
        $this->inputBuffer = '';

        if ($this->isWhitespace($char) && $this->ignoreWhitespace()) {
            return;
        }

        switch ($this->state) {
            case ParserState::BEGIN_OBJECT():
                return $this->parseBeginObject($char);
            case ParserState::BEGIN_KEY():
                return $this->parseBeginKey($char);
        }
    }

    protected function parseBegin($char)
    {
        if ($char !== '{') {
            throw new Exception\ParseException('Expected JSON object.');
        }

        $this->stack->push(new stdClass);
        $this->state = ParserState::BEGIN_KEY();
    }

    protected function parseBeginKey($char)
    {
        $this->doBeginValue($char);

        if ($this->state != ParserState::STRING_VALUE()) {
            throw new Exception\ParseException('Expected JSON object key.');
        }
    }

    protected function isWhitespace($char)
    {
        return preg_match('/\s/u', $char);
    }

    protected function ignoreWhitespace()
    {
        return $this->state->anyOf(
            ParserState::BEGIN_OBJECT()
        );
    }

    private $encoding;
    private $state;
    private $inputBuffer;
    private $objects;
    private $stack;
}
