<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Parses template files
 *
 * @author azcraft
 */
class Template
{

    private string $file;
    private array $variables = [];
    private ?Request $request = null;

    /**
     * Takes the requested file.
     * Throws exception if not existing.
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = 'Templates/' . $file;
        if (!file_exists($this->file)){
            throw new Exception("Template $file do not exist.");
        }
    }

    /**
     * Addes a variable to be replaces within the template file
     * @param string $name
     * @param string $value
     */
    public function setValue(string $name, string $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Addes multiple variables to be replaced within the template file
     * @param array $variables
     */
    public function setValues(array $variables)
    {
        foreach ($variables as $name => $value){
            $this->variables[$name] = $value;
        }
    }

    /**
     * Returns the parsed content
     * @return string
     */
    public function run(Request $req = null): string
    {
        $this->request = $req;
        return $this->processFile($this->file);
    }

    /**
     * Processes a file.
     * @param string $file
     * @return string
     * @throws Exception
     */
    private function processFile(string $file): string
    {
        if (!file_exists($file)){
            throw new Exception("Template $file do not exist.");
        }

        $raw = file_get_contents($file);

        return $this->insertFiles($this->insertVariables($raw));
    }

    /**
     * Inserts variables within template string
     * @param string $input
     * @return string
     */
    private function insertVariables(string $input): string
    {
        $commands = [];
        preg_match_all('/\$\{([^{}:]*)\}/', $input, $commands);
        foreach ($commands[1] as $i => $command){
            $default = "\${$command}";
            if (strpos($command, '=')){
                $default = implode('=', array_slice(explode('=', $command), 1));
            }
            $command = explode('=', $command)[0];
            $value = $this->variables[$command] ?? $default;
            $input = str_replace($commands[0][$i], $value, $input);
        }
        return $input;
    }

    /**
     * Inserts more templates within template string
     * @param string $input
     * @return string
     */
    private function insertFiles(string $input): string
    {
        $commands = [];
        preg_match_all('/\$\{([^{}]*)\}/', $input, $commands);
        foreach ($commands[1] as $i => $command){
            if (strpos($command, "include:") === 0 ||
                strpos($command, "parse:") === 0){
                $raw = str_replace(["include:", "parse:"], "", $command);
                $subfile = $this->processFile("Templates/" . $raw);
                $input = str_replace($commands[0][$i], $subfile, $input);
            }
            if (strpos($command, "resource:") === 0 ||
                strpos($command, "path:") === 0){
                $raw = str_replace(["resoure:", "path:"], "", $command);
                $path = $this->request->path();
                $prefix = str_repeat("../", max(count($path) - 1, 0));
                $full_path = $prefix . "Resources/" . $raw;
                $input = str_replace($commands[0][$i], $full_path, $input);
            }
        }
        return $input;
    }

}
