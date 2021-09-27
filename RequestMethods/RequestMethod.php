<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\RequestMethods;

/**
 * Serves as base for other classes in Core\RequestMethods
 * Provides constants for these methods
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
abstract class RequestMethod
{

    const STARTUP_METHOD = 0;
    const FALLBACK_METHOD = 1;
    const GET = 2;
    const POST = 3;
    const PUT = 4;
    const DELETE = 5;

    private string $path;
    private int $method;

    protected function __construct(int $method = self::STARTUP_METHOD,
                                   string $path = '/')
    {
        $this->path = $path;
        $this->method = $method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function method(): int
    {
        return $this->method;
    }

}
