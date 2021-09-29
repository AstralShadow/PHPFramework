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
use Core\Router;
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
    private Router $router;
    private RequestResponse $response;

    /**
     * Parses the request from $_SERVER and loads the module.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->request = new Request();
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
                                  string $passwd = null): void
    {
        self::$pdo = new \PDO($dsn, $username, $passwd, [
            PDO::ATTR_PERSISTENT => true
        ]);
    }

    /**
     * Returns current PDO connection or null
     * @return PDO|null
     */
    public static function getPDO(): ?PDO
    {
        return self::$pdo;
    }

    /**
     * Executes and serves the request
     * @return void
     */
    public function run(): void
    {
        $this->response = $this->router->process($this->request);
        $this->response->serve($this->request);
    }

    /**
     * Asks the RouteTable to process the Request
     * Stores and returns the response
     * @return RequestResponse
     */
    public function execute(): RequestResponse
    {
        $response = $this->router->process($this->request);
        $this->response = $response;
        return $response;
    }

    /**
     * Serves stored request response
     * @return void
     */
    public function serve(): void
    {
        $this->response->serve($this->request);
    }

}
