<?php

namespace Panada\Router;

/**
 * Routes enable more flexible URL since user may define their 
 * own URL to `Controller::action` mapping.
 *
 * @author Nurahmadie <nurahmadie@gmail.com>
 */
class Routes extends \Panada\Utility\Factory
{
    private $urlStaticMap = [
        'GET' =>    [],
        'POST' =>   [],
        'PUT' =>    [],
        'PATCH' =>  [],
        'DELETE' => [],
    ];

    private $urlPatternsMap = [
        'GET' =>    [],
        'POST' =>   [],
        'PUT' =>    [],
        'PATCH' =>  [],
        'DELETE' => [],
    ];
    
    // /books/:id/:title
    const PARAMRGX = "/\:([^\s\/]+)/";

    public static function map($urlPattern, $options = [], $subrouter = null)
    {
        self::getInstance()->addMap($urlPattern, $options, $subrouter);
    }

    public static function get($urlPattern, $options = [], $subrouter = null)
    {
        $options['methods'] = ['GET'];
        self::getInstance()->addMap($urlPattern, $options, $subrouter);
    }

    public static function post($urlPattern, $options = [], $subrouter = null)
    {
        $options['methods'] = ['POST'];
        self::getInstance()->addMap($urlPattern, $options, $subrouter);
    }

    public static function put($urlPattern, $options = [], $subrouter = null)
    {
        $options['methods'] = ['PUT'];
        self::getInstance()->addMap($urlPattern, $options, $subrouter);
    }

    public static function patch($urlPattern, $options = [], $subrouter = null)
    {
        $options['methods'] = ['PATCH'];
        self::getInstance()->addMap($urlPattern, $options, $subrouter);
    }

    public function parse($method, $request_uri)
    {
        if (!array_key_exists($method, $this->urlStaticMap) || $method === 'HEAD') {
            $method = 'GET';
        }

        if (array_key_exists($request_uri, $this->urlStaticMap[$method])) {
            return $this->urlStaticMap[$method][$request_uri];
        }
        foreach ($this->urlPatternsMap[$method] as $route) {
            preg_match($route['matcher'], $request_uri, $args);
            if (count($args) === count($route['params'])+1) {
                $route['args'] = array_combine($route['params'], array_slice($args, 1));
                return $route;
            }
        }
        return null;
    }

    public function addMap($urlPattern, $options = [], $subrouter = null)
    {
        preg_match_all(self::PARAMRGX, $urlPattern, $params);
        if (count($params) && count($params[0])) {
            $matcher = $urlPattern;
            foreach ($params[0] as $param) {
                $matcher = str_replace($param, "([^/]+)", $matcher);
            }
            $options['matcher'] = '|\A'.$matcher.'\z|';
            $options['params'] = $params[1];
            foreach($options['methods'] as $method) {
                $this->urlPatternsMap[$method][$urlPattern] = $options;
            }
            return;
        }
        foreach ($options['methods'] as $method) {
            $options['args'] = [];
            $this->urlStaticMap[$method][$urlPattern] = $options;
        }
    }

}
