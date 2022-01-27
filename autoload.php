<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require "Core/utils.php";

spl_autoload_register(function (string $class) : void
{
    $file = str_replace('\\', '/', $class . '.php');
    if(file_exists($file))
    {
        require $file;
        \Core\initClass($class);
        return;
    }


    // The following code is case insensitive path search,
    //  in case you prefer different file naming conventions.
    // It actually should not affect some systems, so you
    //  better not remove it unless you know what you're doing.
    // Still, i recommend that you don't happen to use it,
    //  because scanning directories is slower than knowing
    //  the correct path from the beginning.

    $path = explode('/', strtolower($file));
    $target = '.';

    while(count($path) && is_dir($target))
    {
        foreach(scandir($target) as $item)
        {
            if(strtolower($item) == $path[0])
            {
                $path = array_slice($path, 1);
                $target = $target . '/' . $item;
            }
        }
    }

    if(file_exists($target))
    {
        require $target;
        \Core\initClass($class);
    }
});

