<?php
namespace Icecave\Duct\TypeCheck\Exception;

final class MissingArgumentException extends UnexpectedInputException
{
    public function __construct($parameterName, $index, $expectedType, \Exception $previous = null)
    {
        $this->parameterName = $parameterName;
        $this->index = $index;
        $this->expectedType = $expectedType;
        parent::__construct(
            \sprintf(
                'Missing argument for parameter \'%s\' at index %d. Expected \'%s\'.',
                $parameterName,
                $index,
                $expectedType
            ),
            $previous
        );
    }

    public function parameterName()
    {
        return $this->parameterName;
    }

    public function index()
    {
        return $this->index;
    }

    public function expectedType()
    {
        return $this->expectedType;
    }

    private $parameterName;
    private $index;
    private $expectedType;
}
