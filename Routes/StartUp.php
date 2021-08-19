<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Routes;

use Attribute;

/**
 * Marks a method as startup
 * This module will be called with these arguments: Request, Controller
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class StartUp
{
    
}
