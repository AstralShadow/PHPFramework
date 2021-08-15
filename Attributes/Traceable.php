<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Attributes;

use Attribute;

/**
 * Attribute adding given function to referenced object
 * The function conducts find() on referencing class only
 *  for referencing entries
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Traceable
{

    private string $name;

    public function __construct(string $customFunctionName) {
        $this->name = $customFunctionName;
    }

    public function getName(): string {
        return $this->name;
    }

}
