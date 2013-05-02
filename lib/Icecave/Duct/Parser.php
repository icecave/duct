<?php
namespace Icecave\Duct;

class Parser
{
    public function __construct(Lexer $lexer = null, TokenStreamParser $parser = null)
    {
        if (null === $lexer) {
            $lexer = new Lexer;
        }

        if (null === $parser) {
            $parser = new TokenStreamParser;
        }

        $this->lexer = $lexer;
        $this->parser = $parser;
    }

    public function parse($json)
    {
        $this->reset();
        $this->feed($json);
        $this->finalize();

        return $this->values();
    }

    public function reset()
    {
        $this->lexer->reset();
        $this->parser->reset();
    }

    public function feed($buffer)
    {
        $this->lexer->feed($buffer);
        $this->parser->feed($this->lexer->tokens());
    }

    public function finalize()
    {
        $this->lexer->finalize();
        $this->parser->feed($this->lexer->tokens());
        $this->parser->finalize();
    }

    public function values()
    {
        return $this->parser->values();
    }

    private $lexer;
    private $parser;
}
