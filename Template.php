<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Inserts certain elements in template file file
 *
 * @author azcraft
 */
class Template
{

    private string $file;
    private array $variables = [];

    public function __construct(string $file) {
        $this->file = 'Templates/' . $file;
    }

    public function setVar(string $name, string $value) {
        $this->variables[$name] = $value;
    }

    public function setVars(array $variables) {
        foreach ($variables as $name => $value){
            $this->variables[$name] = $value;
        }
    }

    public function run(): string {
        return $this->processFile($this->file);
    }

    private function processFile(string $file): string {
        if (!file_exists($file)){
            throw new Exception("Template $file do not exist.");
        }

        $raw = file_get_contents($file);

        return $this->insertFiles($this->insertVariables($raw));
    }

    private function insertVariables(string $input): string {
        $keys = array_map(function ($a){
            return '${' . $a . '}';
        }, array_keys($this->variables));

        return str_replace($keys, array_values($this->variables), $input);
    }

    private function insertFiles(string $input): string {
        $commands = [];
        preg_match_all('/\$\{([^{}]*)\}/', $input, $commands);
        foreach ($commands[1] as $i => $command){
            if (strpos($command, '.')){
                $include = $this->processFile("Templates/" . $command);
                $input = str_replace($commands[0][$i], $include, $input);
            }
        }
        return $input;
    }

}
