<?php
namespace Icecave\Duct;

use Eloquent\Enumeration\Enumeration;

class ParserState extends Enumeration
{
    const BEGIN = 0;

    const ARRAY_START           = 10;
    const ARRAY_VALUE_SEPARATOR = 11;

    const OBJECT_START           = 20;
    const OBJECT_KEY             = 21;
    const OBJECT_KEY_SEPARATOR   = 22;
    const OBJECT_VALUE_SEPARATOR = 23;
}
