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
use Core\RouteTable;
use PDO;

/**
 * Serves http request
 * Uses Modules/* to process the request.
 *
 * @author azcraft
 */
class Controller
{

    private static ?PDO $pdo = null;
    private Request $request;
    private string $module;
    private RouteTable $router;
    private RequestResponse $response;

    /**
     * Parses the request from $_SERVER and loads the module.
     * @param string $default_module 
     */
    public function __construct(string $defaultModule) {
        $this->parseRequest();
        $this->setDefaultModule('Modules\\' . $defaultModule);
        $this->loadModuleRouter();
    }

    /**
     * Creates PDO connection.
     * @param string $dsn
     * @param string $username
     * @param string $passwd
     * @return void
     */
    public static function usePDO(string $dsn,
                                  string $username = null,
                                  string $passwd = null): void {
        self::$pdo = new \PDO($dsn, $username, $passwd, [
            PDO::ATTR_PERSISTENT => true
        ]);
    }

    /**
     * Returns current PDO connection or null
     * @return PDO|null
     */
    public static function getPDO(): ?PDO {
        return self::$pdo;
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
     * Asks the RouteTable to process the Request
     * Stores and returns the response
     * @return RequestResponse
     */
    public function execute(): RequestResponse {
        $response = $this->router->process($this->request);
        $this->response = $response;
        return $response;
    }

    /**
     * Serves stored request response
     * @return void
     */
    public function serve(): void {
        $this->response->serve();
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
        $modules = getModuleNames();
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
     * Parses module routes
     * @return void
     */
    private function loadModuleRouter(): void {
        $name = $this->request->module();
        $this->router = new RouteTable();
        $this->router->loadModule($name);
    }

}
