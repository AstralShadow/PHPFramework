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

    public function getKeys() {
        return $this->keys;
    }

}

/*
  'TARGET_CLASS' =>
  int(1)
  'TARGET_FUNCTION' =>
  int(2)
  'TARGET_METHOD' =>
  int(4)
  'TARGET_PROPERTY' =>
  int(8)
  'TARGET_CLASS_CONSTANT' =>
  int(16)
  'TARGET_PARAMETER' =>
  int(32)
  'TARGET_ALL' =>
  int(63)
  'IS_REPEATABLE' =>
  int(64)
*/