<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

/**
 * Uses php output buffering.
 * You can set headers at any time before end or request.
 *
 * @author azcraft
 */
class BufferedResponse extends InstantResponse
{

    /**
     * Sets http response code
     * @param int $httpResponseCode
     */
    public function __constructor(int $code = 200) {
        http_response_code($code);
        ob_start();
    }

    public function getBuffer(): string {
        return ob_get_contents();
    }

    public function clearBuffer(): string {
        ob_clean();
    }

    /**
     * Does nothing, since text is already printed
     * @return void
     */
    public function serve(): void {
        ob_end_flush();
    }

}
