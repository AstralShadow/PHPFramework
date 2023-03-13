<?php

namespace Core\TemplateUtils;


class NodeString implements Node
{
    private string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }
}

