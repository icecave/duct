<?php
namespace Icecave\Duct\Detail;

use Eloquent\Enumeration\AbstractEnumeration;

class ParserState extends AbstractEnumeration
{
    const BEGIN = 0;

    const ARRAY_START           = 10;
    const ARRAY_VALUE           = 11;
    const ARRAY_VALUE_SEPARATOR = 12;

    const OBJECT_START           = 20;
    const OBJECT_KEY             = 21;
    const OBJECT_KEY_SEPARATOR   = 22;
    const OBJECT_VALUE           = 23;
    const OBJECT_VALUE_SEPARATOR = 24;
}
