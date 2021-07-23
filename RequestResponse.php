<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 *
 * @author azcraft
 */
interface RequestResponse
{

    /**
     * Takes http response code as construction variable
     * @param int $statusCode The response status code
     */
    public function __constructor(int $httpResponseCode = 200);

    /**
     * Sets a header
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader(string $key, string $value): void;

    /**
     * Appends body content to the response
     * @param string $output
     */
    public function echo(string $output);

    /**
     * Serves the response from the module.
     * @return void
     */
    public function serve(): void;
}
