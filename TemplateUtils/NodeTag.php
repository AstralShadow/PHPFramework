<?php

namespace Core\TemplateUtils;


class NodeTag implements Node
{
    /** $pos is used for exception message composition */
    private int $pos;

    private array $nodes = [];


    public function __construct(int $pos)
    {
        $this->pos = $pos;
    }

    public function getPos() : int
    {
        return $this->pos;
    }

    public function add(Node $node)
    {
        $this->nodes[] = $node;
    }

}

