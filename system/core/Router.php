<?php
/**
 * Xylophone
 *
 * An open source HMVC application development framework for PHP 5.3 or newer
 * Derived from CodeIgniter, Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst. It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to licensing@xylophone.io
 * licensing@xylophone.io so we can send you a copy immediately.
 *
 * @package     Xylophone
 * @author      Xylophone Dev Team, EllisLab Dev Team
 * @copyright   Copyright (c) 2014, Xylophone Team (http://xylophone.io/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://xylophone.io
 * @since       Version 1.0
 * @filesource
 */
namespace Xylophone\core;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/general/routing.html
 */
class Router
{
    /** @var    array   List of routes */
    public $routes = array();

    /** @var    array   Array of route parts */
    public $route = array('path' => '', 'class' => '', 'method' => '', 'args' => array());

    /** @var    string  Default controller (and method if specific) */
    public $default_controller = false;

    /**
     * Constructor
     *
     * Determines what should be served based on the URI request,
     * as well as any routes that have been set in the routing config file.
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // Load the routes.php file and set routes
        $this->routes = $XY->config->get('routes.php', 'route');
        is_array($this->routes) || $this->routes = array();

        // Extract the default controller
        empty($this->routes['default_controller']) ||
            $this->default_controller = explode('/', strtolower($this->routes['default_controller']));
        unset($this->routes['default_controller']);

        // Do we have query strings enabled and a controller trigger?
        $ctlr_trig = $XY->config['controller_trigger'];
        if ($XY->config['enable_query_strings'] === true && isset($_GET[$ctlr_trig])) {
            // Add directory segment if provided
            $segments = array();
            $dir_trig = $XY->config['directory_trigger'];
            !isset($_GET[$dir_trig]) || $segments[] = trim($XY->uri->filterUri($_GET[$dir_trig]));

            // Add controller segment
            $class = trim($XY->uri->filterUri($_GET[$ctl_trig]));
            $segments[] = $class;

            // Add function segment if provided
            $func_trig = $XY->config['function_trigger'];
            !isset($_GET[$func_trig]) || $segments[] = trim($XY->uri->filterUri($_GET[$func_trig]));

            // Determine if segments point to a valid route
            ($this->route = $this->validateRoute($segments)) || $XY->show404(implode('/', $segments));
        }
        else {
            // Load the URI and parse for custom routing
            $XY->uri->load();
            $route = $this->parseRoutes($XY->uri->segments);

            // Determine if the route is valid and set in URI
            ($this->route = $this->validateRoute($route)) || $XY->show404($route);
            $XY->uri->setRuriString($route);
        }

        $XY->logger->debug('Router Class Initialized');
    }

    /**
     * Parse Routes
     *
     * Matches any routes that may exist in the config/routes.php file
     * against the URI to determine if the class/method need to be remapped.
     *
     * @param   mixed   $uri    URI string or array of URI segments
     * @return  string  Route string
     */
    public function parseRoutes($uri)
    {
        // Make sure we have a URI string
        is_string($uri) || $uri = implode('/', $uri);

        // Is there a literal match? If so we're done
        if (isset($this->routes[$uri]) && is_string($this->routes[$uri])) {
            return $this->routes[$uri];
        }

        // Loop through the route array looking for wildcards
        foreach ($this->routes as $key => $val) {
            // Convert wildcards to regex
            $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);

            // Does the regex match?
            if (preg_match('#^'.$key.'$#', $uri, $matches)) {
                // Process callbacks or back-references
                if (!is_string($val) && is_callable($val)) {
                    // Remove the original string from the matches array.
                    array_shift($matches);

                    // Get the match count
                    $match_count = count($matches);

                    // Determine how many parameters the callback has.
                    $reflection = new ReflectionFunction($val);
                    $param_count = $reflection->getNumberOfParameters();

                    // Are there more parameters than matches?
                    if ($param_count > $match_count) {
                        // Any params without matches will be set to an empty string.
                        $matches = array_merge($matches, array_fill($match_count, $param_count - $match_count, ''));
                        $match_count = $param_count;
                    }

                    // Get the parameters so we can use their default values.
                    $params = $reflection->getParameters();

                    for ($m = 0; $m < $match_count; $m++) {
                        // Is the match empty and does a default value exist?
                        if (empty($matches[$m]) && $params[$m]->isDefaultValueAvailable()) {
                            // Substitute the empty match for the default value.
                            $matches[$m] = $params[$m]->getDefaultValue();
                        }
                    }

                    // Execute the callback using the values in matches as its parameters.
                    $val = call_user_func_array($val, $matches);
                }
                elseif (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    // Use the default routing method for back-references
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                return $val;
            }
        }

        // Return the un-translated URI
        return $uri;
    }

    /**
     * Validate supplied segments
     *
     * This function attempts to determine the path to the controller.
     * On success, a route array is returned with the following keys:
     *      path    Validated path to source file if no namespace
     *      class   Validated Controller class with full namespace if applicable
     *      method  Method, which may be 'index'
     *      args    Array of remaining segments as arguments
     *
     * @param   mixed   $route  Route string or segments
     * @return  mixed   Route stack array if valid, otherwise FALSE
     */
    public function validateRoute($route)
    {
        global $XY;

        // Check for a passed or default route and convert to array
        if (empty($route) && !($route = $this->default_controller)) {
            return false;
        }
        is_array($route) || $route = explode('/', $route);

        // Search each namespace for the route
        foreach ($XY->ns_paths as $ns => $path) {
            // Start with no path subdirectory and no controllers subdirectory
            $sub = '';
            $ctlrs = 'controllers'.DIRECTORY_SEPARATOR;

            // Iterate route segments
            while (count($route)) {
                // Get next segment and build file path
                $seg = array_shift($route);
                $file = $path.$sub.$ctlrs.$seg;

                // Is this a controller file in the current path?
                if (file_exists($file.'.php')) {
                    // Found our controller - return route stack
                    return $this->makeStack($ns, $sub.$ctlrs, $seg, $route);
                }

                // Is this a subdirectory under controllers or our current path?
                if (is_dir($file)) {
                    // Add it to controllers path
                    $ctlrs .= $seg.DIRECTORY_SEPARATOR;
                }
                elseif (is_dir($path.$sub.$seg)) {
                    // Add it to the sub-namespace path
                    $sub .= $seg.DIRECTORY_SEPARATOR;
                }
                else {
                    // Segment not found here - try next namespace
                    continue 2;
                }
            }

            // We ran out of segments - is there a default controller here?
            if (isset($this->default_controller[0])) {
                $class = $this->default_controller[0];
                if (file_exists($path.$sub.$ctlrs.$class.'.php')) {
                    // Found the default controller - return route stack
                    $method = array_slice($this->default_controller, 1);
                    return $this->makeStack($ns, $sub.$ctlrs, $class, $method);
                }
            }
        }

        // No valid route found
        return false;
    }

    /**
     * Make a route stack
     *
     * @param   string  $ns     Namespace
     * @param   string  $path   Path
     * @param   string  $class  Class name
     * @param   mixed   $method Method name string or array of method and arguments
     * @return  array   Route stack array
     */
    public function makeStack($ns, $path, $class, $method)
    {
        // Check namespace
        $stack = array();
        if ($ns) {
            // Set no path and assemble namespace
            $stack['path'] = '';
            $stack['class'] = $ns.'\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $path).'\\'.ucfirst($class);
        }
        else {
            // Set path and bare class
            $stack['path'] = str_replace(DIRECTORY_SEPARATOR, '/', $path);
            $stack['class'] = $class;
        }

        // Check for method/args array
        if (is_array($method)) {
            // Extract method and set remainder to args
            $stack['method'] = count($method) ? array_shift($method) : 'index';
            $stack['args'] = $method;
        }
        else {
            // Set method and empty args
            $stack['method'] = empty($method) ? 'index' : $method;
            $stack['args'] = array();
        }

        // Return stack
        return $stack;
    }

    /**
     * Get error route
     *
     * Identifies the 404 or error override route, if defined, and validates it.
     *
     * @param   string  $template   Name of error template to override
     * @return  mixed   FALSE if route doesn't exist, otherwise array of 4+ segments
     */
    public function getErrorRoute($template = 'error_general')
    {
        // Select route and return it or FALSE
        $route = $template.'_override';
        return empty($this->routes[$route]) ? false : $this->validateRoute($this->routes[$route]);
    }
}

