<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use Core\RequestMethods\RequestMethod;

/**
 * A data structure, used in Router
 *
 * @author azcraft
 */
class RouterNode
{

    /**
     * RequestMethod[] for StartUp methods at /
     * @var array
     */
    public array $startups = [];

    /**
     * path => RouterNode
     * @var array
     */
    public array $path_nodes = [];

    /**
     * RouterNode for elements with variable
     * @var array
     */
    public ?RouterNode $variable_node = null;

    /**
     * RequestMethod[] for Fallback methods at /
     * @var array
     */
    public ?RequestMethod $fallback = null;

    /**
     * Handlers for current node's root uri.
     * Keys are request methods.
     * @var array
     */
    public array $handlers = [];

    /**
     * Calls addByPath.
     * @param RequestMethod $handler
     * @param string $uri
     * @return void
     * @throws Exception
     */
    public function addByUri(RequestMethod $handler,
                             string $uri): void
    {
        $path = self::getPath($uri);
        $this->addByPath($handler, $path);
    }

    /**
     * Adds a handler in current tree
     * @param RequestMethod $handler
     * @param array $path
     * @return void
     * @throws Exception
     */
    public function addByPath(RequestMethod $handler,
                              array $path): void
    {
        if (count($path) > 0){
            if (self::isVariable($path[0])){
                if (!isset($this->variable_node)){
                    $this->variable_node = new RouterNode();
                }
                $handler->pushVarName(substr($path[0], 1, -1));
                $this->variable_node->addByPath($handler,
                                                array_slice($path, 1));
            } else {
                if (!isset($this->path_nodes[$path[0]])){
                    $this->path_nodes[$path[0]] = new RouterNode();
                }
                $this->path_nodes[$path[0]]->addByPath($handler,
                                                       array_slice($path, 1));
            }
            return;
        }

        $method = $handler->method();
        if ($method == RequestMethod::STARTUP_METHOD){
            $this->startups[] = $handler;
            return;
        }

        if ($method == RequestMethod::FALLBACK_METHOD){
            if (isset($this->fallback)){
                $type = RequestMethod::getMethodName($method);
                $old = $this->fallback->target();
                $target = $handler->target();

                $msg = "Trying to register second fallback for same path<br />\n";
                $msg .= "at $target->class:$target->name <br />\n";
                $msg .= "Already registered: $old->class:$old->name";

                throw new Exception($msg);
            }
            $this->fallback = $handler;
            return;
        }

        if (isset($this->handlers[$method])){
            $type = RequestMethod::getMethodName($method);
            $old = $this->handlers[$method]->target();
            $target = $handler->target();

            $msg = "Trying to register second $type handler for same path<br />\n";
            $msg .= "at $target->class:$target->name <br />\n";
            $msg .= "Already registered: $old->class:$old->name";

            throw new Exception($msg);
        }

        $this->handlers[$method] = $handler;
    }

    /**
     * Parses usi path to array
     * @param string $uri
     * @return void
     */
    private static function getPath(string $uri): array
    {
        $pure_uri = self::stripVariablesFromURI($uri);
        return preg_split("/\//", $pure_uri, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Removes uri elements other than the resource path
     * @param string $input
     * @return string
     */
    private static function stripVariablesFromURI(string $input): string
    {
        $without_get_variables = explode('?', $input, 2)[0];
        $without_focus_element = explode('#', $without_get_variables, 2)[0];
        return $without_focus_element;
    }

    /**
     * A piece of path is variable if it looks like {...}
     * @param string $path_token
     * @return bool
     */
    private static function isVariable(string $path_token): bool
    {
        $begin = $path_token[0];
        $end = $path_token[strlen($path_token) - 1];
        return $begin == '{' && $end == '}';
    }

    public function run(Request $req, $path = null, $vars = []): ?RequestResponse
    {
        $path ??= $req->path();
        $this->callStartUps($req, $vars);
        $result = null;

        if (count($path) > 0){
            if (isset($this->path_nodes[$path[0]])){
                $new_path = array_slice($path, 1);
                $result = $this->path_nodes[$path[0]]->run($req, $new_path, $vars);
            } else if (isset($this->variable_node)){
                $vars[] = $path[0];
                $new_path = array_slice($path, 1);
                $result = $this->variable_node->run($req, $new_path, $vars);
            }
        } else {
            $method = $req->method();
            if (isset($this->handlers[$method])){
                $handler = $this->handlers[$method];
                return $this->callHandler($handler, $req, $vars);
            }
        }
        if (!isset($result) && isset($this->fallback)){
            return $this->callHandler($this->fallback, $req, $vars);
        }
        return $result;
    }

    private function callStartUps(Request $req, $vars): void
    {
        foreach ($this->startups as $startup){
            $this->callHandler($startup, $req, $vars);
        }
    }

    private function callHandler(RequestMethod $handler, Request $req, array $vars)
    {
        $target = $handler->target();
        $keys = $handler->varNames();
        $req->clearVars();
        for ($i = 0; $i < count($keys); $i++){
            $req->setVar($keys[$i], $vars[$i]); // ?? null
        }

        $class = $target->class;
        $method = $target->name;
        return $class::$method($req);
    }

}
