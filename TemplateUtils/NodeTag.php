<?php

namespace Core\TemplateUtils;

use Core\Template;


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


    private array $vars;
    private array $macros;
    private string $path_prefix;

    public function run(array $vars = [],
                        array $macros = [],
                        string $path_prefix = "") : string
    {
        $this->vars = $vars;
        $this->macros = $macros;
        $this->path_prefix = $path_prefix . Template::$resource_prefix;

        $command = "";
        foreach($this->nodes as $node)
            $command .= $node->run([], $this->macros);

        return $this->processCommand($command);
    }


    private function processCommand(string $input)
    {
        $input = explode('=', $input, 2);
        $default = $input[1] ?? null;
        $input = explode(":", $input[0], 2);
        $cmd = count($input) == 2 ? $input[0] : "var";
        $value = $input[count($input) - 1];

        if($default !== null || $cmd == "var") {
            if(isset($this->macros[$value]))
                $value = $this->processMacro($value);
            else if(isset($this->vars[$value]))
                $value = $this->vars[$value];
            else if($default !== null)
                $value = $default;
        }

        switch($cmd) {
            case "var":
                return $value;

            case "parse":
            case "include":
                $tree = TemplateParser::parseFile(Template::$file_prefix . $value);
                $answer = "";
                foreach($tree as $node)
                    $answer .= $node->run(
                        vars: $this->vars,
                        macros: $this->macros
                    );
                return $answer;

            case "path":
            case "resource":
                return $this->path_prefix . $value;
        }
    }

    private function processMacro(string $key)
    {
        $tokens = TemplateParser::parseString($this->macros[$key]);
        $command = "";
        foreach($tokens as $node)
            $command .= $node->run([], $this->macros);

        return $this->processCommand($command);
    }

}

