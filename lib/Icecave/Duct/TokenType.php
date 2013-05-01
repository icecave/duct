<?php
namespace Icecave\Duct;

use Eloquent\Enumeration\Enumeration;

class TokenType extends Enumeration
{
    const BRACE_OPEN = '{';
    const BRACE_CLOSE = '}';
    const BRACKET_OPEN = '[';
    const BRACKET_CLOSE = ']';
    const COLON = ':';
    const COMMA = ',';
    const SCALAR = 'scalar';
}
