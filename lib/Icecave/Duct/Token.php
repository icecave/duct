<?php
namespace Icecave\Duct;

class Token
{
    public function __construct(TokenType $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function type()
    {
        return $this->type;
    }

    public function value()
    {
        return $this->value;
    }

    private $type;
    private $value;
}
