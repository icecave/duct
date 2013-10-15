<?php
namespace Icecave\Duct\TypeCheck;

abstract class AbstractValidator
{
    public function __construct()
    {
        $this->reflector = new \ReflectionObject($this);
    }

    public function __call($name, array $arguments)
    {
        $validatorMethodName = \sprintf('validate%s', \ucfirst(\ltrim($name, '_')));
        if (!$this->reflector->hasMethod($validatorMethodName)) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method %s::%s().', __CLASS__, $name));
        }
        return $this->reflector->getMethod($validatorMethodName)->invokeArgs($this, $arguments);
    }

    private $reflector;
}
