<?php
namespace Icecave\Duct\Detail\Exception;

use Icecave\Duct\Exception\SyntaxExceptionInterface;
use RuntimeException;

class ParserException extends RuntimeException implements SyntaxExceptionInterface
{
}
