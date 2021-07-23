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
    }
});
