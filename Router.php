<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use \ReflectionMethod;
use \ReflectionClass;
use Core\RequestMethods\RequestMethod;

/**
 * Handles module routes
 *
 * @author azcraft
 */
class Router
{

    private RouterNode $node;

    public function __construct()
    {
        $this->node = new RouterNode();
    }

    /**
     * Loads module into this route table.
     * @param string $moduleName
     * @throws Exception
     */
    public function add(string $moduleName, string $uri = "/")
    {
        $module = new ReflectionClass($moduleName);
        $methods = $module->getMethods();
        foreach ($methods as $method){
            $attributes = $method->getAttributes();
            if (!$method->isStatic()){
                throw new Exception("Module $moduleName can have only static members");
            }

            foreach ($attributes as $attribute){
                $instance = $attribute->newInstance();
                $instance->setTarget($method);
                if ($instance instanceof RequestMethod){
                    $methodURI = $uri . $instance->path();
                    $this->node->addByUri($instance, $methodURI);
                }
            }
        }
    }

    /**
     * Calls module startups and the requested method or the closest fallback method.
     * @param Request $req
     * @return RequestResponse
     */
    public function process(Request $req): RequestResponse
    {
        $response = $this->node->run($req);
        if (!isset($response)){
            throw new Exception("Could not handle request. No matching method or fallback found.");
        }
        return $response;
    }

}
