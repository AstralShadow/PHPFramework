<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

use \Core\RequestResponse;
use \Core\Template;

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
    public function __construct(int $httpResponseCode = 200, string $file = "default.html") {
        http_response_code($httpResponseCode);
        $this->template = new Template($file);
    }

    /**
     * Sets header
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader(string $key, string $value): void {
        header("$key: $value");
    }

    /**
     * Prints templates output
     * @return void
     */
    public function serve(): void {
        echo $this->template->run();
    }

    public function __call($name, $arguments) {
        $this->template->$name(...$arguments);
    }

}
