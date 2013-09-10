<?php
namespace Icecave\Duct\TypeCheck\Exception;

final class UnexpectedArgumentException extends UnexpectedInputException
{
    public function __construct($index, $value, \Exception $previous = null, \Icecave\Duct\TypeCheck\TypeInspector $typeInspector = null)
    {
        if (null === $typeInspector) {
            $typeInspector = new \Icecave\Duct\TypeCheck\TypeInspector();
        }
        $this->index = $index;
        $this->value = $value;
        $this->typeInspector = $typeInspector;
        $this->unexpectedType = $typeInspector->type($this->value);
        parent::__construct(\sprintf('Unexpected argument of type \'%s\' at index %d.', $this->unexpectedType, $index), $previous);
    }

    public function index()
    {
        return $this->index;
    }

    public function value()
    {
        return $this->value;
    }

    public function typeInspector()
    {
        return $this->typeInspector;
    }

    public function unexpectedType()
    {
        return $this->unexpectedType;
    }

    private $index;
    private $value;
    private $typeInspector;
    private $unexpectedValue;
}
