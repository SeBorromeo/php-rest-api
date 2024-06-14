<?php namespace App\Lib;

class App {
    public static $basePath = '/api';
    private static $routes = [];
    private static $middlewares = [];

    public static function run()
    {
        Logger::enableSystemLogs();
        self::matchRoute();
    }

    public static function useMiddleware(callable $middleware) {
        self::$middlewares[] = $middleware;
    }

    public static function useRouter(string $url, Router $router) {
        $routerRoutes = $router->getRoutes();
        foreach ($routerRoutes as $routerMethod => $routerURLs) {
            foreach ($routerURLs as $routerURL => $target) {
                $newUrl = $url . $routerURL;
                
                self::$routes[$routerMethod][$newUrl] = $target;
            }
        }    
    }

    public static function addRoute(string $method, string $uri, callable $target, array $middlewares = []) {
        $pathUri = $uri;

        //Remove trailing slash if there is one
        if (substr($pathUri, -1) === '/')
            $pathUri = substr($pathUri, 0, -1);

        self::$routes[$method][$pathUri] = ['middlewares' => $middlewares, 'target' =>  $target];
    }

    public static function get(string $uri, callable $target, array $middlewares = []) {
        self::addRoute('GET', $uri, $target, $middlewares);
    }

    public static function post(string $uri, callable $target, array $middlewares = []) {
        self::addRoute('POST', $uri, $target, $middlewares);
    }

    public static function put(string $uri, callable $target, array $middlewares = []) {
        self::addRoute('PUT', $uri, $target, $middlewares);
    }

    public static function delete(string $uri, callable $target, array $middlewares = []) {
        self::addRoute('DELETE', $uri, $target, $middlewares);
    }

    public static function matchRoute() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        //Remove trailing slash if there is one
        if (substr($uri, -1) === '/')
            $uri = substr($uri, 0, -1);

        /*Removed basePath to isolate just the route. This "basePath" is set in our .htaccess file specifying that any path beginning with /api/
        will route to this API */
        if (substr($uri, 0, strlen(self::$basePath)) === self::$basePath) {
            $uri = substr($uri, strlen(self::$basePath));
        }

        $res = new Response();

        if (isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $routeUrl => $routeInfo) {
                // Use named subpatterns in the regular expression pattern to capture each parameter value separately
                $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $routeUrl);
                if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                    // Pass the captured parameter values as named arguments to the target function
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Only keep named subpattern matches
                    
                    $req = new Request($params);

                    try {
                        $middlewareStack = MiddlewareHandler::configureMiddlewareStack(array_merge(self::$middlewares, $routeInfo['middlewares']), $routeInfo['target']);
                        $middlewareStack($req, $res);
                    } catch(\Throwable $e) {
                        if($_ENV['ENVIRONMENT'] === 'development') {
                            $res->status(500)->toJSON(['error' => [
                                'code' => 500,
                                'message' => $e->getMessage(),
                                'stack' => $e->getTraceAsString()
                            ]]);
                        } else {
                            self::throwError($res, 'Internal Server Error', 500);
                        }
                    }
                    return;
                }
            }
        }
        self::throwError($res, 'Not Found', 404);
    }

    public static function throwError(Response $res, string $message, int $code) {
        $res->status($code)->toJSON(['error' => [
            'code' => $code,
            'message' => $message,
        ]]);
    }
}