<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use Core\RequestMethods\RequestMethod;

/**
 * Used to parse http requests.
 * Do not include validation.
 * @author azcraft
 */
class Request implements \Serializable
{

    private array $path = [];
    private array $vars = [];
    private int $method = RequestMethod::GET;

    public function __construct(?string $uri = null, ?string $method = null)
    {
        if (isset($uri, $method)){
            $this->defineFromString($uri);
            $this->setMethod($method);
        } else {
            $this->defineFromServerGlobals();
        }
    }

    /**
     * Returns the Request's method
     * @return int The Request's method
     */
    public function method(): int
    {
        return $this->method;
    }

    /**
     * Overwrites the request method
     * @param int|string $method
     * @return void
     * @throws Exception
     */
    public function setMethod($method): void
    {
        if (is_string($method)){
            $method = strtolower($method);
        }

        switch ($method)
        {
            default:
            case "get":
            case RequestMethod::GET:
                $this->method = RequestMethod::GET;
                return;

            case "put":
            case RequestMethod::PUT:
                $this->method = RequestMethod::PUT;
                return;

            case "post":
            case RequestMethod::POST:
                $this->method = RequestMethod::POST;
                return;

            case "delete":
            case RequestMethod::DELETE:
                $this->method = RequestMethod::DELETE;
                return;
        }
    }

    /**
     * Returns the Request's arguments
     * They are the target path without the module
     * @return int The Request's arguments
     */
    public function path(): array
    {
        return $this->path;
    }

    /**
     * Overwrites the request arguments
     * @param array $args
     * @return void
     */
    public function setPath(array $path): void
    {
        $this->path = $path;
    }

    public function var($name): mixed
    {
        if (!isset($this->vars[$name])){
            return null;
        }
        return $this->vars[$name];
    }

    public function setVar(string $name, mixed $value)
    {
        $this->vars[$name] = urldecode($value);
    }

    public function vars(): array
    {
        return $this->vars;
    }

    public function clearVars(): void
    {
        $this->vars = [];
    }

    public function __get($name)
    {
        if (!isset($this->vars[$name])){
            throw new Exception("Undefined \$Request->$name");
        }
        return $this->vars[$name];
    }

    /**
     * Parses a string to target module and arguments
     * @param string $uri
     * @return void
     */
    private function defineFromString(string $uri): void
    {
        $pure_uri = self::stripVariablesFromPath($uri);
        $path = preg_split("/\//", $pure_uri, -1, PREG_SPLIT_NO_EMPTY);
        $this->path = $path;
        unset($path);
    }

    /**
     * Removes uri elements other than the resource path
     * @param string $input
     * @return string
     */
    private static function stripVariablesFromPath(string $input): string
    {
        $without_get_variables = explode('?', $input, 2)[0];
        $without_focus_element = explode('#', $without_get_variables, 2)[0];
        return $without_focus_element;
    }

    /**
     * Gathers information about the request from $_SERVER
     * @return void
     */
    private function defineFromServerGlobals(): void
    {
        $this->defineFromString($_SERVER["REQUEST_URI"]);
        $this->setMethod($_SERVER["REQUEST_METHOD"] ?? RequestMethod::GET);
    }

    /**
     * String representation of object
     * @return string
     */
    public function serialize(): string
    {
        return $this->method() . '*' . implode('/', $this->path);
    }

    /**
     * Constructs the object from its string representation
     * @param string $serialized
     * @return void
     */
    public function unserialize(string $serialized): void
    {
        $data = explode('*', $serialized, 2);

        $this->setMethod($data[0]);
        $this->defineFromString($data[1]);
    }

}
