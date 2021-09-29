<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Responses;

/**
 * Instantly prints input as JSON
 *
 * @author azcraft
 */
class ApiResponse extends InstantResponse
{

    /**
     * Outputs pretty JSON
     * @param string $output
     */
    public function echo($output)
    {
        echo json_encode($output, JSON_PRETTY_PRINT);
    }

}
