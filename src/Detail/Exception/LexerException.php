<?php
namespace Icecave\Duct\Detail\Exception;

use Icecave\Duct\Exception\SyntaxExceptionInterface;
use RuntimeException;

class LexerException extends RuntimeException implements SyntaxExceptionInterface
{
}
