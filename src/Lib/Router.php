<?php namespace App\Lib;

class Router {
    protected $routes = [];
    protected $middlewares = [];

    public function useMiddleware(callable $middleware) {
        $this->middlewares[] = $middleware;
    }

    public function addRoute(string $method, string $uri, callable $target, array $middlewares = []) {
        $pathUri = $uri;

        //Remove trailing slash if there is one
        if (substr($pathUri, -1) === '/')
            $pathUri = substr($pathUri, 0, -1);

        $this->routes[$method][$pathUri] = ['middlewares' => $middlewares, 'target' =>  $target];
    }

    public function get(string $uri, callable $target, array $middlewares = []) {
        $this->addRoute('GET', $uri, $target, $middlewares);
    }

    public function post(string $uri, callable $target, array $middlewares = []) {
        $this->addRoute('POST', $uri, $target, $middlewares);
    }

    public function put(string $uri, callable $target, array $middlewares = []) {
        $this->addRoute('PUT', $uri, $target, $middlewares);
    }

    public function delete(string $uri, callable $target, array $middlewares = []) {
        $this->addRoute('DELETE', $uri, $target, $middlewares);
    }
    
    public function getRoutes() {
        return $this->routes;
    }    
}