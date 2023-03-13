<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

use \Core\RequestResponse;

/**
 * Buffers response code, headers and output.
 * The simplest proper way to implement the RequestResponse.
 *
 * @author azcraft
 */
class BufferedResponse implements RequestResponse
{
    private int $code;
    private array $headers = [];
    private string $buffer = "";


    public function __construct(int $code = 200) {
        $this->code = $code;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }


    public function echo($output): void {
        $this->buffer .= $output;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function clearBuffer(): void {
        $this->buffer = "";
    }


    public function serve(): void {
        http_response_code($this->code);
        foreach($this->headers as $key => $value)
            header("$key: $value");

        echo $this->buffer;
    }

}
