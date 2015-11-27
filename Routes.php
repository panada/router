<?php

namespace Panada\router;

/**
 * Routes enable more flexible URL since user may define their
 * own URL to `Controller::action` mapping.
 *
 * @author  bijanebrahimi
 *
 * @link    https://github.com/bijanebrahimi/Another-php-Router
 *
 * @license http://www.gnu.org/copyleft/gpl.html
 *
 * @since   version 2.0.0
 */
class Routes extends \Panada\Utility\Factory
{
    public static $aliases = [];
    public static $defaults = [];
    public static $patterns = [];
    public static $variables = [];
    public static $alias_name = '';

    private static function isPattern($name)
    {
        if (preg_match('/(.*)?:(.*)/', $name, $matched)) {
            return array($matched[1], $matched[2]);
        }
    }
    
    private static function parseHTTPhost($host)
    {
        list($host, $port) = preg_split("/\:/", $host);
        if ($tld = strrchr($host, '.')) {
            $tld = substr($tld, 1);
            $domain = substr($host, 0, strlen($host) - strlen($tld) - 1);
            if ($sub = strrchr($domain, '.')) {
                $subdomain = substr($domain, 0, strlen($domain) - strlen($sub));
            }
            $sld = substr($domain, strlen($subdomain) + ($subdomain != ''));

            return [$subdomain, "$sld.$tld"];
        } else {
            return [null, $host];
        }
    }

    public static function defaults($name, $value = null, $pattern = null)
    {
        return self::$defaults[$name] = [$value, $pattern];
    }
    
    public static function pattern($name, $regex = null)
    {
        if ($regex) {
            if (!array_key_exists($name, self::$patterns)) {
                self::$patterns[$name] = $regex;
            }
        } else {
            if (array_key_exists($name, self::$patterns)) {
                return self::$patterns[$name];
            }
        }
    }
    
    public static function route($name, $array, $value)
    {
        $options = [];

        list($methodValue, $methodPattern) = self::defaults('method');
        if (array_key_exists('method', $array)) {
            if (!is_array($array['method'])) {
                $array['method'] = [$array['method']];
            }
            foreach ($array['method'] as $method) {
                if (self::isPattern($method)) {
                    $methodPattern = $method;
                } else {
                    $methodValue = $method;
                }
            }
        }

        list($protocolValue, $protocolPattern) = self::defaults('protocol');
        if (array_key_exists('protocol', $array)) {
            if (!is_array($array['protocol'])) {
                $array['protocol'] = [$array['protocol']];
            }
            foreach ($array['protocol'] as $protocol) {
                if (self::isPattern($protocol)) {
                    $protocolPattern = $protocol;
                } else {
                    $protocolValue = $protocol;
                }
            }
        }

        list($subdomainValue, $subdomainPattern) = self::defaults('subdomain');
        if (array_key_exists('subdomain', $array)) {
            if (!is_array($array['subdomain'])) {
                $array['subdomain'] = [$array['subdomain']];
            }
            foreach ($array['subdomain'] as $subdomain) {
                if (self::isPattern($subdomain)) {
                    $subdomainPattern = $subdomain;
                } else {
                    $subdomainValue = $subdomain;
                }
            }
        }

        list($domainValue, $domainPattern) = self::defaults('domain');
        if (array_key_exists('domain', $array)) {
            if (!is_array($array['domain'])) {
                $array['domain'] = [$array['domain']];
            }
            foreach ($array['domain'] as $domain) {
                if (self::isPattern($domain)) {
                    $domainPattern = $domain;
                } else {
                    $domainValue = $domain;
                }
            }
        }

        list($portValue, $portPattern) = self::defaults('port');
        if (array_key_exists('port', $array)) {
            if (!is_array($array['port'])) {
                $array['port'] = array($array['port']);
            }
            foreach ($array['port'] as $port) {
                if (self::isPattern($port)) {
                    $portPattern = $port;
                } else {
                    $portValue = $port;
                }
            }
        }

        list($portValue, $portPattern) = self::defaults('port');
        if (array_key_exists('port', $array)) {
            if (!is_array($array['port'])) {
                $array['port'] = array($array['port']);
            }
            foreach ($array['port'] as $port) {
                if (self::isPattern($port)) {
                    $portPattern = $port;
                } else {
                    $portValue = $port;
                }
            }
        }

        $options = array(
            'method' => array($methodValue, $methodPattern),
            'protocol' => array($protocolValue, $protocolPattern),
            'subdomain' => array($subdomainValue, $subdomainPattern),
            'domain' => array($domainValue, $domainPattern),
            'port' => array($portValue, $portPattern),
            'url' => [],
            'value' => $value,
        );
        $urls = explode('/', $array['url']);
        if (is_array($urls)) {
            $options['url'] = array_filter($urls, 'strlen');
        }

        if (!isset(self::$aliases[$name])) {
            self::$aliases[$name] = [];
        }
        array_push(self::$aliases[$name], $options);

        return $options;
    }
    
    public static function get($name)
    {
        if (array_key_exists($name, self::$variables)) {
            return self::$variables[$name];
        }
    }
    
    public static function getAlias()
    {
        return self::$alias_name;
    }

    public static function find($dispatcher)
    {
        self::$variables = [];
        self::$alias_name = [];
        list($subdomain, $domain) = self::parseHTTPhost($dispatcher['host'].':'.$dispatcher['port']);
        $request = array(
            'method' => $dispatcher['method'],
            'protocol' => preg_replace('/[^a-z]/i', '', $dispatcher['scheme']),
            'subdomain' => $subdomain,
            'domain' => $domain,
            'port' => $_SERVER['SERVER_PORT'],
            'url' => array_filter(explode('/', $dispatcher['path']), 'strlen'),
        );
        foreach (self::$aliases as $alias_name => $alias_group) {
            foreach ($alias_group as $index => $alias) {
                $variables = [];
                //~ Method
                list($method, $pattern) = (is_array($alias['method'])) ? $alias['method'] : array($alias['method']);
                if (!$pattern) {
                    $regex = "/($method)/i";
                    $var = 'method';
                } elseif (preg_match('/(.*)?:(.*)/i', $pattern, $matched)) {
                    $var = ($matched[1]) ? $matched[1] : 'method';
                    $regex = self::pattern($matched[2]);
                }
                if (!preg_match($regex, $request['method'])) {
                    continue;
                }
                $variables[$var] = $request['method'];

                //~ Protocol
                list($protocol, $pattern) = (is_array($alias['protocol'])) ? $alias['protocol'] : array($alias['protocol']);
                if (!$pattern) {
                    $regex = "/($protocol)/i";
                    $var = 'protocol';
                } elseif (preg_match('/(.*)?:(.*)/i', $pattern, $matched)) {
                    $var = ($matched[1]) ? $matched[1] : 'protocol';
                    $regex = self::pattern($matched[2]);
                }
                if (!preg_match($regex, $request['protocol'])) {
                    continue;
                }
                $variables[$var] = $request['protocol'];

                //~ subdomain
                list($subdomain, $pattern) = (is_array($alias['subdomain'])) ? $alias['subdomain'] : array($alias['subdomain']);
                if (!$pattern) {
                    $regex = "/($subdomain)/i";
                    $var = 'subdomain';
                } elseif (preg_match('/(.*)?:(.*)/i', $pattern, $matched)) {
                    $var = ($matched[1]) ? $matched[1] : 'subdomain';
                    $regex = self::pattern($matched[2]);
                }
                if (!preg_match($regex, $request['subdomain'])) {
                    continue;
                }
                $variables[$var] = $request['subdomain'];

                //~ domain
                list($domain, $pattern) = (is_array($alias['domain'])) ? $alias['domain'] : array($alias['domain']);
                if (!$pattern) {
                    $regex = "/($domain)/i";
                    $var = 'domain';
                } elseif (preg_match('/(.*)?:(.*)/i', $pattern, $matched)) {
                    $var = ($matched[1]) ? $matched[1] : 'domain';
                    $regex = self::pattern($matched[2]);
                }
                if (!preg_match($regex, $request['domain'])) {
                    continue;
                }
                $variables[$var] = $request['domain'];

                //~ port
                list($port, $pattern) = (is_array($alias['port'])) ? $alias['port'] : array($alias['port']);
                if (!$pattern) {
                    $regex = "/($port)/i";
                    $var = 'port';
                } elseif (preg_match('/(.*)?:(.*)/i', $pattern, $matched)) {
                    $var = ($matched[1]) ? $matched[1] : 'port';
                    $regex = self::pattern($matched[2]);
                }
                if (!preg_match($regex, $request['port'])) {
                    continue;
                }
                $variables[$var] = $request['port'];

                $found = true;
                $urls = array_reverse($request['url']);
                foreach ($alias['url'] as $alias_url) {
                    $url = array_pop($urls);
                    if (preg_match('/(.*)?:(.*)/', $alias_url, $matched)) {
                        $pattern_name = $matched[2];
                        $pattern_var = $matched[1];
                        $pattern_regex = self::pattern($pattern_name);
                        if (preg_match($pattern_regex, $url)) {
                            if ($pattern_var) {
                                $variables[$pattern_var] = $url;
                            } else {
                                $variables[$pattern_name] = $url;
                            }
                        } else {
                            $found = false;
                            break;
                        }
                    } else {
                        if ($url != $alias_url) {
                            $found = false;
                            break;
                        }
                    }
                }

                if ($found && count($urls) == 0) {
                    self::$variables = $variables;
                    self::$alias_name = $alias_name;

                    return $alias['value'];
                }
            }
        }
    }
    public static function link($name, $param, $fqdn = true)
    {
        $link = '';
        if (!array_key_exists($name, self::$aliases)) {
            return;
        }
        foreach (self::$aliases[$name] as $index => $aliases) {
            $params = $param;
            $found = true;
            $link = '';
            foreach ($aliases as $key => $alias) {
                if ($key != 'method') {
                    switch ($key) {
                        case 'protocol':
                        case 'subdomain':
                        case 'domain':
                        case 'port':
                            $$key = (($params[$key]) ? $params[$key] : $alias[0]);
                            unset($params[$key]);
                            break;
                        case 'url':
                            $url = '';
                            $params = array_reverse($params);
                            foreach ($alias as $value) {
                                if (list($var, $pattern) = self::isPattern($value)) {
                                    if (!$var) {
                                        $var = $pattern;
                                    }
                                    if (array_key_exists($var, $params)) {
                                        $arg = $params[$var];
                                        unset($params[$var]);
                                    } else {
                                        $arg = array_pop($params);
                                    }
                                    $regex = self::pattern($pattern);
                                    if (preg_match($regex, $arg)) {
                                        $url .= "/$arg";
                                    } else {
                                        $found = false;
                                        break;
                                    }
                                } else {
                                    $url .= "/$value";
                                }
                            }
                            break;
                    }
                    if (!$found) {
                        break;
                    }
                }
            }
            if ($found && count($params) == 0) {
                if ($fqdn) {
                    $host = (($subdomain) ? "$subdomain." : '').$domain;
                    if ($port != 80) {
                        return "$protocol://$host:$port$url";
                    } else {
                        return "$protocol://$host$url";
                    }
                } elseif ($url) {
                    return "$url";
                } else {
                    return "/$url";
                }

                return $link;
            }
        }
    }
}
