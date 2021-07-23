<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Serves a Request
 *
 * @author azcraft
 */
interface Module
{

    public function run(Request $request): RequestResponse;
}
