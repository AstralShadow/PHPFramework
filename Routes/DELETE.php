<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Routes;

use Attribute;

/**
 * Marks this method to serve DELETE requests.
 * Takes route path as argument
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class DELETE
{

    private string $path;

    public function __construct(string $path = '/') {
        $this->path = $path;
    }

    public function path(): string {
        return $this->path;
    }

}
