<?php
namespace Icecave\Duct\TypeCheck\Validator\Icecave\Duct;

class TokenStreamParserTypeCheck extends \Icecave\Duct\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function parse(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('tokens', 0, 'mixed<Icecave\\Duct\\Token>');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        $check = function ($value) {
            if (!\is_array($value) && !$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!$subValue instanceof \Icecave\Duct\Token) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[0])) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentValueException(
                'tokens',
                0,
                $arguments[0],
                'mixed<Icecave\\Duct\\Token>'
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
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('tokens', 0, 'mixed<Icecave\\Duct\\Token>');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        $check = function ($value) {
            if (!\is_array($value) && !$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!$subValue instanceof \Icecave\Duct\Token) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[0])) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentValueException(
                'tokens',
                0,
                $arguments[0],
                'mixed<Icecave\\Duct\\Token>'
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

    public function feedToken(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doValue(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doObjectStart(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doObjectKey(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doObjectKeySeparator(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doObjectValueSeparator(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doArrayStart(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function doArrayValueSeparator(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function emit(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('value', 0, 'mixed');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function setState(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('state', 0, 'Icecave\\Duct\\ParserState');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function setObjectKey(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('key', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentValueException(
                'key',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function push(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('value', 0, 'mixed');
            }
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('state', 1, 'Icecave\\Duct\\ParserState');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function pop(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function createUnexpectedTokenException(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\MissingArgumentException('token', 0, 'Icecave\\Duct\\Token');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Duct\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
