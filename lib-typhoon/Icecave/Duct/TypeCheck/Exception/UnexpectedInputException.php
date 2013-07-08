<?php
namespace Icecave\Duct\TypeCheck\Exception;

abstract class UnexpectedInputException extends \InvalidArgumentException
{
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

}
