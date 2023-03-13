<?php

namespace Core\TemplateUtils;

use Core\Request;


trait TraitFileProcessing
{
    use TraitValueAccessors;

    private string $path_prefix = "";


    private function processFile(string $file) : string
    {
        $tree = TemplateParser::parseFile($file);

        $answer = "";
        foreach($tree as $node)
            $answer .= $node->run(
                vars: $this->variables,
                macros: $this->macros,
                path_prefix: $this->path_prefix
            );

        return $answer;
    }


    /** Request object used to compose relative resource paths */
    public function setRequest(Request $req): void
    {
        $path = $req->path();
        $nestedness = max(count($path) - 1, 0);
        $this->path_prefix = str_repeat("../", $nestedness);
    }

}

