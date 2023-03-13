<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

use \Core\RequestResponse;
use \Core\Template;
use \Core\Request;

/**
 * Whaps Template as a RequestResponse
 *
 * @author azcraft
 */
class TemplateResponse implements RequestResponse
{

    private Template $template;

    private int $code;
    private array $headers = [];
    private string $output = "";
    private bool $output_modified = true;


    /**
     * Recommended usage:
     *  new TemplateResponse(file: "...", code: ...);
     *  new TemplateResponse(file: "...", code: ..., req: $req);
     *
     */
    public function __construct(int $code = 200,
                                string $file = "default.html")
    {
        $this->setCode($code);
        $this->template = new Template($file);
    }

    public function setCode(int $code)
    {
        $this->code = $code;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }


    public function getOutput(Request $req = null) : string
    {
        if($this->output_modified) {
            $this->output = $this->template->run($req);
            $this->output_modified = false;
        }
        return $this->output;
    }

    public function serve(Request $req = null): void
    {
        http_response_code($this->code);
        foreach($this->headers as $key => $value)
            header("$key: $value");

        echo $this->getOutput($req);
    }


    /* Template controls */

    public function setValue(string $name, string $value): void
    {
        $this->template->setValue($name, $value);
        $this->output_modified = true;
    }

    public function setValues(array $variables): void
    {
        $this->template->setValues($variables);
        $this->output_modified = true;
    }

    public function getValue(string $name)
    {
        return $this->template->getValue($name);
    }

}
