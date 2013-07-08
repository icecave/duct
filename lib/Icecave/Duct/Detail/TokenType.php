<?php
namespace Icecave\Duct\Detail;

use Eloquent\Enumeration\Enumeration;

class TokenType extends Enumeration
{
    const BRACE_OPEN      = '{';
    const BRACE_CLOSE     = '}';
    const BRACKET_OPEN    = '[';
    const BRACKET_CLOSE   = ']';
    const COLON           = ':';
    const COMMA           = ',';
    const STRING_LITERAL  = 'string_literal';
    const BOOLEAN_LITERAL = 'boolean_literal';
    const NULL_LITERAL    = 'null_literal';
    const NUMBER_LITERAL  = 'number_literal';
}
