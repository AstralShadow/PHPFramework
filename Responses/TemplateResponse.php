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
 * Uses php output buffering.
 * You can set headers at any time before end or request.
 *
 * @author azcraft
 */
class TemplateResponse implements RequestResponse
{

    private Template $template;

    /**
     * Sets http response code
     * @param int $httpResponseCode
     */
    public function __construct(int $code = 200, string $file = "default.html")
    {
        http_response_code($code);
        $this->template = new Template($file);
    }

    /**
     * Sets header
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader(string $key, string $value): void
    {
        header("$key: $value");
    }

    /**
     * Prints templates output
     * @return void
     */
    public function serve(Request $req = null): void
    {
        echo $this->template->run($req);
    }

    /**
     * Defines variable into the template namespace
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setValue(string $name, string $value): void
    {
        $this->template->setValue($name, $value);
    }

    /**
     * Defines multiple variables into the template namespace
     * @param array $variables
     * @return void
     */
    public function setValues(array $variables): void
    {
        $this->template->setValues($variables);
    }

}
