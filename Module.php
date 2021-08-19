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

    protected static Controller $controller;

    public static function load(Controller $controller): void {
        self::$controller = $controller;
    }

    abstract public static function run(Request $request): RequestResponse;
}
