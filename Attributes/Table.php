<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Attributes;

use Attribute;

/**
 * Attribute to define an entity's SQL table
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Table
{

    private string $table;

    public function __construct(string $table) {
        $this->table = $table;
    }

    public function table() {
        return $this->table;
    }

}
