<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Attributes;

use Attribute;

/**
 * Attribute to define an entity's SQL primary key(s)
 * One can use compbination of arguments
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class PrimaryKey
{

    private array $keys = [];

    public function __construct(string ...$primaryKeys) {
        $this->keys = [...$primaryKeys];
    }

    public function keys() {
        return $this->keys;
    }

}
