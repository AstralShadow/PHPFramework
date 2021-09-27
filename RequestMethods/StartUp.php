<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\RequestMethods;

use Attribute;

/**
 * Marks a method as startup
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class StartUp extends RequestMethod
{

    public function __constructor(string $path = "/")
    {
        parent::_constructor(self::STARTUP_METHOD, $path);
    }

}
