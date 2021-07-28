<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Used to parse http requests.
 * Do not include validation.
 * @author azcraft
 */
class Request implements \Serializable
{

    const METHOD_GET = "get";
    const METHOD_POST = "post";
    const METHOD_PUT = "put";
    const METHOD_DELETE = "delete";

    private ?string $module;
    private array $args = [];
    private string $method = self::METHOD_GET;

    /**
     * 
     * @param string|null $uri
     */
    public function __construct(?string $uri = null, ?string $method = null) {
        if (!isset($uri, $method)){
            $this->defineFromServerGlobals();
        } else {
            $this->defineFromString($uri);
            $this->setRequestMethod($method || "get");
        }
    }

    /**
     * Returns the Request's target module
     * @return string The Requrest's target module
     */
    public function module(): ?string {
        return $this->module;
    }

    /**
     * Overwrites the target module
     * @param string $module
     * @return void
     */
    public function setModule(string $module): void {
        $this->module = $module;
    }

    /**
     * Returns the Request's method
     * @return int The Request's method
     */
    public function method(): string {
        return $this->method;
    }

    /**
     * Overwrites the request method
     * @param int $method
     * @return void
     */
    public function setMethod(string $method): void {
        $this->module = $method;
    }

    /**
     * Returns the Request's arguments
     * They are the target path without the module
     * @return int The Request's arguments
     */
    public function args(): array {
        return $this->args;
    }

    /**
     * Overwrites the request arguments
     * @param array $args
     * @return void
     */
    public function setArgs(array $args): void {
        $this->args = $args;
    }

    /**
     * Parses a string to target module and arguments
     * @param string $uri
     * @return void
     */
    private function defineFromString(string $uri): void {
        $pure_uri = $this->stripVariablesFromPath($uri);
        $path = preg_split("/\//", $pure_uri, -1, PREG_SPLIT_NO_EMPTY);
        $this->module = $path[0] ?? null;
        $this->args = array_slice($path, 1);
        unset($path);
    }

    /**
     * Removes uri elements other than the resource path
     * @param string $input
     * @return string
     */
    private function stripVariablesFromPath(string $input): string {
        $without_get_variables = explode('?', $input, 2)[0];
        $without_focus_element = explode('#', $without_get_variables, 2)[0];
        return $without_focus_element;
    }

    /**
     * Gathers information about the request from $_SERVER
     * @return void
     */
    private function defineFromServerGlobals(): void {
        $this->defineFromString($_SERVER["REQUEST_URI"]);
        $this->setRequestMethod($_SERVER["REQUREST_METHOD"] ?? "get");
    }

    /**
     * Parses the request method from string to local constant
     * @param string $method
     * @return void
     */
    private function setRequestMethod(string $method): void {
        switch (strtolower($method)){
            case "get":
                $this->method = self::METHOD_GET;
                break;
            case "put":
                $this->method = self::METHOD_PUT;
                break;
            case "post":
                $this->method = self::METHOD_POST;
                break;
            case "delete":
                $this->method = self::METHOD_DELETE;
                break;
        }
    }

    /**
     * String representation of object
     * @return string
     */
    public function serialize(): string {
        $path = array_merge([$this->module], $this->args);
        $method = (string) $this->method;

        $data = $method . '*' . implode('/', $path);
        return $data;
    }

    /**
     * Constructs the object from its string representation
     * @param string $serialized
     * @return void
     */
    public function unserialize(string $serialized): void {
        $data = explode('*', $serialized, 2);

        $this->method = (int) $data[0];
        $this->defineFromString($data[1]);
    }

}
