<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\RequestMethods;

use \ReflectionMethod;

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
    const PUT = 3;
    const POST = 4;
    const DELETE = 5;

    private string $path;
    private array $var_names = [];
    private int $method;
    private ?ReflectionMethod $target = null;

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

    public function pushVarName(string $name): void
    {
        $this->var_names[] = $name;
    }

    public function varNames(): array
    {
        return $this->var_names;
    }

    public function setTarget(\ReflectionMethod $target): void
    {
        $this->target = $target;
    }

    public function target(): ?ReflectionMethod
    {
        return $this->target;
    }

    public static function getMethodName(int $method): string
    {
        switch ($method)
        {
            case self::STARTUP_METHOD:
                return "StartUp";
            case self::FALLBACK_METHOD:
                return "Fallback";
            case self::GET:
                return "GET";
            case self::PUT:
                return "PUT";
            case self::POST:
                return "POST";
            case self::DELETE:
                return "DELETE";
            default: return "unknown";
        }
    }

}
