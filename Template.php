<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;


class Template
{

    private string $path_prefix = "";
    //private ?Request $request = null;

    private string $file = "";


    use TemplateValueAccessors;
    use TemplateFileProcessing;


    public function __construct(string $file, Request $req = null)
    {
        $this->setFile($file);

        if($req != null)
            $this->setRequest($req);
    }

    public function setFile(string $file)
    {
        if (!file_exists($this->file))
            throw new Exception("Template $file do not exist.");

        $this->file = 'Templates/' . $file;
    }

    /** Request object used to compose relative resource paths */
    public function setRequest(Request $req): void
    {
        $path = $req->path();
        $nestedness = max(count($path) - 1, 0);
        $this->path_prefix = str_repeat("../", $nestedness);
    }

    public function run(Request $req = null): string
    {
        if($req != null)
            $this->setRequest($req);

        return $this->processFile($this->file);
    }


    private function processFile(string $file): string
    {
        if (!file_exists($file)){
            throw new Exception("Template $file do not exist.");
        }

        $output = file_get_contents($file);

        do {
            $output = $this->insertVariables($output);
            $output = $this->insertFiles($output);
        
            preg_match_all('/\$\{(.*)\}/', $output, $commands);
        } while(count($commands[0]) > 0);

        return $output;
    }

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
