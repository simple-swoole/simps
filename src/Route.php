<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps;

use function FastRoute\simpleDispatcher;

class Route
{
    private static $instance;

    private static $config;

    private static $dispatcher = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();

            self::$config = Config::getInstance()->get('routes', []);
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
     * @param $request
     * @param $response
     * @throws \Exception
     * @return mixed|string
     */
    public function dispatch($request, $response)
    {
        $method = $request->server['request_method'] ?? 'GET';
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = self::$dispatcher->dispatch($method, $uri);

        //result status decide
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                //try default router
                return $this->defaultRouter($request, $response, $uri);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                //$allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                $response->status(405);
                throw new \Exception('Request Method Not Allowed', 405);
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                //string rule is controllerName@functionName
                if (is_string($handler)) {
                    //decode handle setting
                    $handler = explode('@', $handler);
                    if (count($handler) != 2) {
                        throw new \Exception(
                            'Router Config error on handle.Handle only support two parameter with @' . $uri,
                            -105
                        );
                    }

                    $className = $handler[0];
                    $func = $handler[1];

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

                    //invoke controller and get result
                    return $controller->{$func}($request, $response, $vars ?? null);
                }
                if (is_callable($handler)) {
                    //call direct when router define an callable function
                    return call_user_func_array($handler, [$request, $response, $vars ?? null]);
                }
                throw new \Exception('Router Config error on handle.' . $uri, -108);
                break;
        }
        throw new \Exception('Unknow Fast Router decide ' . $uri, -101);
    }

    /**
     * @param $request
     * @param $response
     * @param $uri
     * @throws \Exception
     * @return mixed
     */
    public function defaultRouter($request, $response, $uri)
    {
        if (empty($uri)) {
            throw new \Exception('uri is empty', -111);
        }

        $uri = trim($uri, '/');
        $uri = explode('/', $uri);

        if ($uri[0] === '') {
            $className = '\\App\\Controller\\IndexController';
            if (class_exists($className) && method_exists($className, 'index')) {
                return $className->index($request, $response);
            }
            //找不到404
            $response->status(404);
            throw new \Exception('Default Router index/index Handle define Class Not Found', 404);
        }

        $response->status(404);
        throw new \Exception('Router Not Found', 404);
    }
}
