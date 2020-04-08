<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps\Server\Protocol\HTTP;

use Simps\Config;
use function FastRoute\simpleDispatcher;

class SimpleRoute
{
    private static $instance;

    private static $config;

    private static $dispatcher = null;

    private static $cache = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();

            self::$config = Config::getInstance()->get('routes');
            self::$dispatcher = simpleDispatcher(
                function (\FastRoute\RouteCollector $routerCollector) {
                    foreach (self::$config as $routerDefine) {
                        $routerCollector->addRoute($routerDefine[0], $routerDefine[1], $routerDefine[2]);
                    }
                }
            );
        }
        return self::$instance;
    }

    /**
     * @param $server
     * @param $fd
     * @param $data
     * @throws \Exception
     * @return mixed
     */
    public function dispatch($server, $fd, $data)
    {
        $first_line = \strstr($data, "\r\n", true);
        $tmp = \explode(' ', $first_line, 3);
        $method = $tmp[0] ?? 'GET';
        $uri = $tmp[1] ?? '/';
        $routeInfo = self::$dispatcher->dispatch($method, $uri);

        //result status decide
        switch ($routeInfo[0]) {
//            \FastRoute\Dispatcher::FOUND eliminate fetch_cons opline
            case 1:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                if (isset(self::$cache[$handler])) {
                    $cache_entity = self::$cache[$handler];
                    return $cache_entity[0]->{$cache_entity[1]}($server, $fd, $vars ?? null);
                }

                //string rule is controllerName@functionName
                if (is_string($handler)) {
                    //decode handle setting
                    $handlerArr = explode('@', $handler);
                    if (count($handlerArr) != 2) {
                        throw new \Exception(
                            'Router Config error on handle.Handle only support two parameter with @' . $uri,
                            -105
                        );
                    }

                    $className = $handlerArr[0];
                    $func = $handlerArr[1];

                    //class check
                    if (! class_exists($className)) {
                        throw new \Exception("Router {$uri} Handle definded Class Not Found", -106);
                    }

                    //new controller
                    $controller = new $className();

                    //method check
                    if (! method_exists($controller, $func)) {
                        throw new \Exception("Router {$uri} Handle definded {$func} Method Not Found", -107);
                    }

                    self::$cache[$handler] = [$controller, $func];
                    //invoke controller and get result
                    return $controller->{$func}($server, $fd, $vars ?? null);
                }
                if (is_callable($handler)) {
                    //call direct when router define an callable function
                    return call_user_func_array($handler, [$server, $fd, $vars ?? null]);
                }
                throw new \Exception('Router Config error on handle.' . $uri, -108);
                break;
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                //try default router
                return $this->defaultRouter($server, $fd, $uri);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                //$allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                $server->send($fd, SimpleResponse::build('', 405));
                throw new \Exception('Request Method Not Allowed', 405);
                break;
        }
        throw new \Exception('Unknow Fast Router decide ' . $uri, -101);
    }

    /**
     * @param $server
     * @param $fd
     * @param $uri
     * @throws \Exception
     * @return mixed
     */
    public function defaultRouter($server, $fd, $uri)
    {
        if (empty($uri)) {
            throw new \Exception('uri is empty', -111);
        }

        $uri = trim($uri, '/');
        $uri = explode('/', $uri);

        if ($uri[0] === '') {
            $className = '\\App\\Controller\\IndexController';
            if (class_exists($className) && method_exists($className, 'index')) {
                return $className->index($server, $fd);
            }
            //找不到404
            $server->send($fd, SimpleResponse::build('', 404));
            throw new \Exception('Default Router index/index Handle define Class Not Found', 404);
        }
        $server->send($fd, SimpleResponse::build('', 404));
        throw new \Exception('Router Not Found', 404);
    }
}
