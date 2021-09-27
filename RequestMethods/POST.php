<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\RequestMethods;

use Attribute;

/**
 * Marks a method to serve POST requests.
 * Takes route path as argument
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class POST extends RequestMethod
{

    public function __construct(string $path = '/')
    {
        parent::__construct(parent::POST, $path);
    }

}
