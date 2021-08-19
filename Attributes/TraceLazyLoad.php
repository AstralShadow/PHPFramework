<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\Attributes;

use Attribute;

/**
 * Loads trace methods on demand
 *
 * @author azcraft
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TraceLazyLoad
{

    private string $class;
    private array $methods = [];

    public function __construct(string $class, string ...$methods) {
        $this->class = $class;
        $this->methods = $methods;
    }

    public function contains(string $method): bool {
        return in_array($method, $this->methods);
    }

    public function load(): void {
        $this->class::init();
    }

    public function methods(): array {
        return $this->methods;
    }

    public function className(): string {
        return $this->class;
    }

}
