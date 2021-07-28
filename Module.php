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
abstract class Module
{

    protected Controller $controller;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
    }

    abstract public function run(Request $request): RequestResponse;
}
