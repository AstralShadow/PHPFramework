<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


spl_autoload_register(function (string $class): void{

    $file = str_replace('\\', '/', $class . '.php');
    if (file_exists($file)){
        require $file;
        return;
    }

    function requireCaseInsensitive(array $path, $filename = '.') {
        if (count($path) && is_dir($filename)){
            foreach (scandir($filename) as $item){
                if (strtolower($item) === strtolower($path[0])){
                    $newPath = array_slice($path, 1);
                    $newFilename = $filename . '/' . $item;
                    return requireCaseInsensitive($newPath, $newFilename);
                }
            }
        } else if (file_exists($filename)){
            require $filename;
            return;
        }
    }

    $path = array_map('strtolower', explode('/', $file));
    requireCaseInsensitive($path);
});
