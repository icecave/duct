<?php
namespace Icecave\Duct\Detail\Exception;

use Icecave\Duct\Exception\SyntaxExceptionInterface;
use RuntimeException;

/**
 * @internal
 */
class LexerException extends RuntimeException implements SyntaxExceptionInterface
{
}
