<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use \ReflectionClass;

/**
 * Check if module implements Core\Module
 * Load the module if not loaded. (autoloader)
 * @param string $name
 * @return bool
 */
function isModule(string $name): bool {
    $class = new ReflectionClass($name);
    $parent = $class->getParentClass();
    return $parent && $parent->getName() == 'Core\\Module';
}

/**
 * Check if module implements Core\Entity
 * Load the module if not loaded. (autoloader)
 * @param string $name
 * @return bool
 */
function isEntity(string $name): bool {
    $class = new ReflectionClass($name);
    $parent = $class->getParentClass();
    return $parent && $parent->getName() == 'Core\\Entity';
}

/**
 * Calls custom init functions, if applicable
 * @param string $name
 * @return void
 */
function initClass(string $name): void {
    if (isModule($name)){
        if (defined("DEBUG_AUTOLOAD_LOG")){
            echo "[+] $name (Module) <br />\n";
        }
        return;
    }

    if (isEntity($name)){
        if (defined("DEBUG_AUTOLOAD_LOG")){
            echo "[+] $name (Entity) <br />\n";
        }
        $name::init();
        return;
    }

    if (defined("DEBUG_AUTOLOAD_LOG")){
        echo "[+] $name <br />\n";
    }
}

/**
 * Scan for avaliable module names
 * Do not check if they are valid.
 * @return array
 */
function getModuleNames(): array {
    $names = [];
    foreach (scandir("Modules") as $name){
        if (!strpos($name, '.php')){
            continue;
        }
        $names[] = 'Modules\\' . str_replace('.php', '', $name);
    }
    return $names;
}

/**
 * Returns formatted memory usage string
 * @param $real pass this to PHP's memory_get_usage
 * @return string
 */
function getMemoryUsage(bool $real = false): string {
    $units = ['B', 'KiB', 'MiB', 'GiB'];
    $memory_usage_raw = memory_get_usage($real);
    $unit = floor(log($memory_usage_raw, 1024));
    $memory_usage = round($memory_usage_raw / pow(1024, $unit));
    return $memory_usage . ' ' . $units[$unit];
}
