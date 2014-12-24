<?php
namespace Icecave\Duct\Detail;

/**
 * A JSON token produced by the lexer.
 *
 * @internal
 */
class Token
{
    public $type;
    public $value;

    /**
     * @param string $type  The type of this token.
     * @param mixed  $value The token's value.
     */
    public function __construct($type, $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }
}
