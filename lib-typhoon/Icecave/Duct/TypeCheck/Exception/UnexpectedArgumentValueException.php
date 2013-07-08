<?php
namespace Icecave\Duct\TypeCheck\Exception;

final class UnexpectedArgumentValueException extends UnexpectedInputException
{
    public function __construct($parameterName, $index, $value, $expectedType, \Exception $previous = null, \Icecave\Duct\TypeCheck\TypeInspector $typeInspector = null)
    {
        if (null === $typeInspector) {
            $typeInspector = new \Icecave\Duct\TypeCheck\TypeInspector();
        }
        $this->parameterName = $parameterName;
        $this->index = $index;
        $this->value = $value;
        $this->expectedType = $expectedType;
        $this->typeInspector = $typeInspector;
        $this->unexpectedType = $typeInspector->type($this->value);
        parent::__construct(
            \sprintf(
                'Unexpected argument of type \'%s\' for parameter \'%s\' at index %d. Expected \'%s\'.',
                $this->unexpectedType,
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

    public function value()
    {
        return $this->value;
    }

    public function expectedType()
    {
        return $this->expectedType;
    }

    public function typeInspector()
    {
        return $this->typeInspector;
    }

    public function unexpectedType()
    {
        return $this->unexpectedType;
    }

    private $parameterName;
    private $index;
    private $value;
    private $expectedValue;
    private $typeInspector;
    private $unexpectedValue;
}
