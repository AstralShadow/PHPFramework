<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

use Core\RequestResponse;


/**
 * Prints input as JSON
 *
 * @author azcraft
 */
class ApiResponse implements RequestResponse
{

    private int $code;
    private $headers = [];
    private $output = null;


    /**
     * Sets http response code
     * @param int $code
     */
    public function __construct(int $code = 200)
    {
        $this->setCode($code);
        $this->setHeader("Content-Type", "text/json");
    }

    /**
     * Sets http response code
     * @param int $code
     * @return void
     */
    public function setCode(int $code) : void
    {
        $this->code = $code;
    }

    public function getCode() : int
    {
        return $this->code;
    }

    /**
     * Sets header
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Outputs pretty JSON
     * @param string $output
     */
    public function echo($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Does nothing, since text is already printed
     * @return void
     */
    public function serve(): void
    {
        http_response_code($this->code);
        foreach($this->headers as $key => $value)
            header("$key: $value");
        echo json_encode($this->output, JSON_PRETTY_PRINT);
    }

}
