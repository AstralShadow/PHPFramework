<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use Core\Request;
use Core\Exception;
use Core\RequestResponse;
use \ReflectionClass;

/**
 * Serves http request
 * Uses Modules/* to process the request.
 *
 * @author azcraft
 */
class Controller
{

    private Request $request;
    private Module $module;
    private RequestResponse $response;

    /**
     * Parses the request from $_SERVER and loads the module.
     * @param string $default_module 
     */
    public function __construct(string $default_module) {
        $this->parseRequest();
        $this->setDefaultModule('Modules\\' . $default_module);
        $this->validateRequest();
        $this->loadModule();
    }

    /**
     * Executes and serves the request
     * Use this one if you dont plan to perform magic outside the framework.
     * @return void
     */
    public function run(): void {
        $this->execute();
        $this->serve();
    }

    /**
     * Asks the module to respond the request
     * Stores and returns the response
     * @return RequestResponse
     */
    public function execute(): RequestResponse {
        $response = $this->module->run($this->request);
        $this->response = $response;
        return $response;
    }

    /**
     * Serves stored request response and disconnects the script
     * @return void
     */
    public function serve(): void {
        $response = $this->response;
        $response->serve();
        fastcgi_finish_request();
    }

    /**
     * Create the Request object for the current request
     * @return void
     */
    private function parseRequest(): void {
        $this->request = new Request();
    }

    /**
     * Set the default module if not specified.
     * @param type $default
     * @return void
     * @throws Exception
     */
    private function setDefaultModule($default = null): void {
        $modules = $this->getModuleNames();
        if (!in_array($default, $modules)){
            throw new Exception("The specified default module does not exist");
        }

        $module = 'Modules\\' . $this->request->module();
        if (!in_array(strtolower($module), array_map('strtolower', $modules))){
            $module = $default;
        }

        $this->request->setModule($module);
    }

    /**
     * Scan for avaliable module names
     * Do not check if they are valid.
     * @return array
     */
    private function getModuleNames(): array {
        $names = [];
        foreach (scandir("Modules") as $name){
            if (!strpos($name, '.php')){
                continue;
            }
            $names[] = 'Modules\\' . str_replace('.php', '', $name);
        }
        return $names;
    }

    /**
     * Check if the request points to real Module
     * @return void
     * @throws Exception
     */
    private function validateRequest(): void {
        $module = $this->request->module();

        if (!$this->isModule($module)){
            throw new Exception("$module does not implement Core\Module");
        }
    }

    /**
     * Create private instance of the requested module 
     * @return void
     */
    private function loadModule(): void {
        $module_name = $this->request->module();
        $this->module = new $module_name($this->request);
    }

    /**
     * Check if module implements Core\Module
     * Load the module if not loaded.
     * @param string $name
     * @return bool
     */
    private function isModule(string $name): bool {
        $a = new ReflectionClass($name);
        $interfaces = $a->getInterfaceNames();
        return in_array('Core\\Module', $interfaces);
    }

}
