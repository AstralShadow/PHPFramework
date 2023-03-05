<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

use \Core\RequestResponse;

/**
 * The most straightforward implementation demonstration of RequestResponse
 * This is not the right way to implement RequestResponse,
 *  but is the easiest way to understand its usage.
 *
 * @author azcraft
 */
class InstantResponse implements RequestResponse
{

    /**
     * Sets http response code
     * @param int $code
     */
    public function __construct(int $code = 200)
    {
        http_response_code($code);
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
     * Simply prints text.
     * You can use echo instead.
     * @param string $output
     */
    public function echo(string $output)
    {
        echo $output;
    }

    /**
     * Does nothing, since text is already printed
     * @return void
     */
    public function serve(): void
    {
        
    }

}
