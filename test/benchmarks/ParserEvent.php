<?php
namespace Icecave\Duct;

use Athletic\AthleticEvent;

class ParserEvent extends AthleticEvent
{
    protected function setUp()
    {
        $this->parser = new Parser;
    }

    /**
     * @iterations 10
     */
    public function parseBuffer()
    {
        $data = file_get_contents(__DIR__ . '/input.json');

        $this->parser->parse($data);
    }

    /**
     * @iterations 10
     */
    public function parseStream()
    {
        $fp = fopen(__DIR__ . '/input.json', 'r');

        while ($s = fread($fp, 1024)) {
            $this->parser->feed($s);
        }

        $this->parser->finalize();
    }
}
