<?php
namespace Icecave\Duct;

use Eloquent\Enumeration\Enumeration;

class ParserState extends Enumeration
{
    const BEGIN_OBJECT = 0;
    const BEGIN_KEY = 1;
}
