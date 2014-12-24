<?php
namespace Icecave\Duct\Detail;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @internal
 */
class LexerState extends AbstractEnumeration
{
    const BEGIN = 0;

    const STRING_VALUE         = 10;
    const STRING_VALUE_ESCAPED = 11;
    const STRING_VALUE_UNICODE = 12;

    const NUMBER_VALUE                = 20;
    const NUMBER_VALUE_NEGATIVE       = 21;
    const NUMBER_VALUE_LEADING_ZERO   = 22;
    const NUMBER_VALUE_DECIMAL        = 24;
    const NUMBER_VALUE_EXPONENT_START = 25;
    const NUMBER_VALUE_EXPONENT       = 26;

    const TRUE_VALUE = 30;

    const FALSE_VALUE = 40;

    const NULL_VALUE = 50;
}
