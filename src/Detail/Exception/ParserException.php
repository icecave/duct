<?php
namespace Icecave\Duct\Detail\Exception;

use Icecave\Duct\Exception\SyntaxExceptionInterface;
use RuntimeException;

/**
 * @internal
 */
class ParserException extends RuntimeException implements SyntaxExceptionInterface
{
}
