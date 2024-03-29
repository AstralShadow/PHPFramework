<?php

namespace Core\TemplateUtils;


interface Node
{
    function run(array $vars = [],
                 array $macros = [],
                 string $path_prefix = "") : string;
}

