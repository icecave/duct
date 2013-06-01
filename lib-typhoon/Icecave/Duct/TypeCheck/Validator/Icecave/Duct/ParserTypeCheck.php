<?php
namespace Icecave\Duct\TypeCheck\Validator\Icecave\Duct;

class ParserTypeCheck extends \Icecave\Duct\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 2) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function parse(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('buffer', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentValueException(
                'buffer',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function reset(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function feed(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('buffer', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentValueException(
                'buffer',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function finalize(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function values(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

}
