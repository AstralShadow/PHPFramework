<?php

namespace Core\TemplateUtils;


class NodeString implements Node
{
    private string $text;


    public function __construct(string $text)
    {
        $this->text = $text;
    }


    public function run(array $vars = [],
                        array $macros = [],
                        string $path_prefix = "") : string
    {
        return $this->text;
    }
}

