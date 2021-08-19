<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use \ReflectionMethod;
use \ReflectionClass;
use Core\Routes\GET;
use Core\Routes\PUT;
use Core\Routes\POST;
use Core\Routes\DELETE;
use Core\Routes\NotFound;
use Core\Routes\StartUp;

/**
 * Handles module routes
 *
 * @author azcraft
 */
class RouteTable
{

    private array $routes_get = [];
    private array $routes_put = [];
    private array $routes_post = [];
    private array $routes_delete = [];

    /**
     * Fallback method. If not defined, a InstantResponse(404) is used
     * @var ReflectionMethod
     */
    private ReflectionMethod $fallback;

    /**
     * Loads module into this route table.
     * @param string $moduleName
     * @throws Exception for the sole purpose to keep people from doing stupid stuff.
     */
    public function loadModule(string $moduleName) {
        $module = new ReflectionClass($moduleName);
        $methods = $module->getMethods();
        foreach ($methods as $method){
            $method_name = $method->getName();
            $attributes = $method->getAttributes();
            if (!$method->isStatic()){
                throw new Exception("Module $moduleName can have only static members");
            }
            foreach ($attributes as $attribute){
                $instance = $attribute->newInstance();
                if ($instance instanceof StartUp){
                    $moduleName::$method_name();
                    continue;
                }
                if (self::isInstanceOfRouteAttribute($instance)){
                    $this->addRoute($instance, $method);
                }
            }
        }
    }

    /**
     * Returns true if $object is instance of GET, PUT, POST, DELETE or NotFound
     * @param type $object
     * @return bool
     */
    private static function isInstanceOfRouteAttribute($object): bool {
        if ($object instanceof GET){
            return true;
        }
        if ($object instanceof PUT){
            return true;
        }
        if ($object instanceof POST){
            return true;
        }
        if ($object instanceof DELETE){
            return true;
        }
        if ($object instanceof NotFound){
            return true;
        }
        return false;
    }

    /**
     * Addes route for certain method
     * @param object $attribute instance of attribute
     * @param ReflectionMethod $method
     * @return void
     */
    private function addRoute(object $attribute, ReflectionMethod $method): void {
        if ($attribute instanceof NotFound){
            $this->fallback = $method;
            return;
        }
        var_dump($attribute, $method);
    }

    public function process(Request $req): RequestResponse {
        var_dump($req);
        return new Responses\InstantResponse(404);
    }

}
