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
 * so we can send you a copy immediately.
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
 * Loader Class
 *
 * Loads framework components.
 *
 * @package     Xylophone
 * @subpackage  core
 * @author      Xylophone Dev Team
 * @link        http://xylophone.io/user_guide/libraries/loader.html
 */
class Loader
{
    /** @var    array   List of class name mappings */
    protected $varmap = array(
        'unit_test' => 'unit',
        'user_agent' => 'agent'
    );

    /**
     * Constructor
     *
     * @return    void
     */
    public function __construct()
    {
        global $XY;
        $XY->logger->debug('Loader Class Initialized');
    }

    /**
     * Load a library
     *
     * @uses    Loader::loadObject()
     *
     * @param   string|array    $library    Library name or array of names w/ optional object name keys
     * @param   array           $params     Optional parameters
     * @param   string          $obj_name   Optional object name
     * @return  void
     */
    public function library($library = '', $params = null, $obj_name = null)
    {
        // Load library resource(s)
        $this->loadObject($library, 'libraries', $obj_name, $params, true);
    }

    /**
     * Load a driver
     *
     * Drivers may be specified by a fully namespaced class for the library or
     * driver, a bare library name with optional path, or a library name with
     * a colon-separated driver and optional subdriver name. Some examples are:
     * - Lib
     * - Lib:Driver
     * - Lib:Driver:Subdriver
     * - path/to/Lib
     * - path/to/Lib:Driver
     * - path/to/Lib:Driver:Subdriver
     * - path\to\Lib\Lib
     * - path\to\Lib\Driver\Driver
     * - path\to\Lib\Driver\subdrivers\Subdriver
     *
     * @uses    Loader::makeDriverPath()
     * @uses    Loader::loadObject()
     *
     * @param   string|array    $library    Driver name or array of names
     * @param   array           $params     Optional parameters
     * @param   array           $extras     Optional extra parameters
     * @param   string          $obj_name   Optional object name
     * @return  object|bool     Object or FALSE on failure if $library is a string
     *                          and $obj_name is set. VOID otherwise.
     */
    public function driver($library = '', $params = null, $extras = null, $obj_name = null)
    {
        global $XY;

        // Check for namespace
        if (strpos($library, '\\') === false) {
            // Break out path parts and separate driver from lib
            $path = explode('/', $library);
            $lib = explode(':', array_pop($path));

            // Extract driver name if given and re-assemble lib
            $subdrv = count($lib) > 2 ? array_pop($lib) : false;
            $driver = count($lib) > 1 ? array_pop($lib) : false;
            $lib = implode(':', $lib);
            $library = $this->makeDriverPath($lib, $path, $driver, $subdrv).'/'.$lib;
        }
        else {
            // Break apart namespace
            $path = explode('\\', $library);
            $ct = count($path);

            // Check for drivers or subdrivers subdir
            if ($ct > 3 && $path[$ct - 2] == 'subdrivers') {
                // Subdriver specified
                $lib = $path[$ct - 4];
                $driver = end($path);
            }
            elseif ($ct > 2 && strtolower($path[$ct - 3].$path[$ct - 2]) == strtolower($path[$ct - 1])) {
                // Driver with subdir specified
                $lib = $path[$ct - 3];
                $driver = end($path);
            }
            else {
                // Only lib specified
                $lib = end($path);
                $driver = false;
            }
        }

        // Set object name if not passed in
        $lib = strtolower($lib);
        $obj_name || $obj_name = ($lib == 'database') ? 'db' : $lib;

        // Check for param string
        if (is_string($params)) {
            // Is it a valid DSN string? Database supports these.
            if (strpos($params, '://') === false) {
                // This will not do
                $XY->showError('Invalid driver DSN string');
            }

            // Check for active group
            if (isset($extras['active_group'])) {
                // Get active group name
                $active = $extras['active_group'];
            }
            else {
                // Set default active group
                $active = 'default';
                $extras['active_group'] = $active;
            }

            // Set DSN in active group in params array
            $params = array($active => array('dsn' => $params));
        }

        // Set config params and load resource
        $config = array('file' => $lib, 'driver' => !$driver, 'extras' => $extras);
        $this->loadObject($library, 'libraries', $obj_name, $params, $config);
    }

    /**
     * Load a controller
     *
     * This function lets users load and instantiate (sub)controllers.
     *
     * @uses    Loader::loadObject()
     * @uses    Xylophone::callController()
     *
     * @param   string|array    $route      Controller route string or route stack array
     * @param   string          $obj_name   Object name to load as
     * @param   bool            $call       Whether to call controller method
     * @param   bool            $return     Whether to return output (depends on $call == TRUE)
     * @return  string|bool     Output if $return, TRUE on success, otherwise FALSE
     */
    public function controller($route, $obj_name = null, $call = true, $return = false)
    {
        global $XY;

        // Check for missing class
        if (empty($route)) {
            return false;
        }

        // Get instance and establish segment stack
        if (is_array($route)) {
            // Assume segments have been pre-parsed by Router::validateRoute() - make sure we have a class
            if (!isset($route['class'])) {
                return false;
            }
        }
        else {
            // Call validateRoute() to break URI into segments
            if (!($route = $XY->router->validateRoute(explode('/', $route)))) {
                return false;
            }
        }

        // Get class name and path from route
        $ctlr = $route['class'];
        empty($route['path']) || $ctlr = $route['path'].'/'.$ctlr;
        $this->loadObject($ctlr, 'controllers', $obj_name);

        // Call method if requested
        if (!$call) {
            return true;
        }
        return $XY->callController($route, null, null, $name, $return);
    }

    /**
     * Load a model
     *
     * Loads a model and passes in a database connection. The $db arg may be:
     * - FALSE to use $XY->db if already loaded
     * - TRUE to use $XY->db, autoloading from config as needed
     * - A string to use as a database object name, autoloading from config as needed
     * - An array with 'db' set to an array of database config params to load from
     * - A previously loaded database object
     * - An array of parameters for the model, with the 'db' element set to one of the above
     *
     * The model will be passed parameters, including 'db' as an object or
     * FALSE if none was specified and $XY->db is not loaded.
     * The Xylophone Model base class, if used, will set the 'db' object to
     * $this->db inside the model.
     *
     * @param   string  $model      Model name
     * @param   string  $obj_name   Optional object name
     * @param   mixed   $db         Optional database connection
     * @return  void
     */
    public function model($model, $obj_name = null, $db = false)
    {
        global $XY;

        // Force db to array with 'db' element
        is_array($db) || $db = array('db' => $db);
        isset($db['db']) || $db['db'] = false;

        // Check for database object
        if (!is_object($db['db'])) {
            // What do we need to use?
            if (is_bool($db['db']) && isset($XY->db)) {
                // Use loaded db
                $db['db'] = $XY->db;
            }
            else if ($db['db'] === true) {
                // Autoload default db from config
                $this->driver('database');
                $db['db'] = $XY->db;
            }
            else if (is_string($db['db'])) {
                // Use object name if loaded, autoload if needed
                $name = $db['db'];
                isset($XY->$name) || $this->driver('database', null, null, $name);
                $db['db'] = $XY->$name;
            }
            else {
                // Autoload db with params as 'model' group
                $name = $obj_name ? $obj_name.'_db' : $model.'_db';
                $params = array('model' => $db['db']);
                $extras = array('active_group' => 'model', 'query_builder' => true);
                $this->driver('database', $params, $extras, $name);
                $db['db'] = $XY->$name;
            }
        }

        // Load model resource(s)
        $this->loadObject($model, 'models', $obj_name, $db);
    }

    /**
     * Load a view (an alias for Output::file())
     *
     * @param   string  $view   View name
     * @param   array   $vars   An associative array of data to be extracted for use in the view
     * @param   bool    $return Whether to return the view output or leave it to the Output class
     * @return  void
     */
    public function view($view, $vars = array(), $return = false)
    {
        global $XY;

        // Convert vars if object and process file
        is_array($vars) || $vars = get_object_vars($vars);
        return $XY->output->file($view, true, $return, $vars);
    }

    /**
     * Load a generic file (an alias for Output::file())
     *
     * @param   string  $path   File path
     * @param   bool    $return Whether to return the file output
     * @return  void
     */
    public function file($path, $return = false)
    {
        global $XY;

        // Process file
        return $XY->output->file($path, false, $return);
    }

    /**
     * Load a config file (an alias for Config::load())
     *
     * @uses    Config::load()
     *
     * @param   string|array    $file       Configuration file name or array of names
     * @param   bool            $sections   Whether configuration values should be loaded into their own section
     * @param   bool            $graceful   Whether to just return FALSE or display an error message
     * @return  bool            TRUE if the file was loaded correctly or FALSE on failure
     */
    public function config($file = '', $sections = false, $graceful = false)
    {
        global $XY;

        // Pass params to config loader
        return $XY->config->load($file, $sections, $graceful);
    }

    /**
     * Load a language file (an alias for Lang::load())
     *
     * @param   string|array    $files  Language file name or array of names
     * @param   string          $lang   Language name
     * @return  void
     */
    public function language($files = array(), $lang = '')
    {
        global $XY;

        // Pass params to language loader
        return $XY->lang->load($file, $lang);
    }

    /**
     * Build path to driver or subdriver file
     *
     * @used-by Loader::driver()
     * @used-by Loader::loadObject()
     *
     * @param   string  $lib    Reference to library class name
     * @param   mixed   $path   Path to base driver library as string or array
     * @param   string  $driver Driver name
     * @param   string  $subdrv Optional subdriver name
     * @return  string  Driver namespace or path
     */
    protected function makeDriverPath(&$lib, $path, $driver, $subdrv)
    {
        // Check for namespace
        if (strpos($lib, '\\') === false) {
            // Path - convert to array
            $ns = false;
            is_array($path) || $path = explode('/', $path);
        }
        else {
            // Namespace - break out as path array and lib class name
            $ns = explode('\\', $lib);
            $lib = array_pop($ns);
            $path =& $ns;
        }

        // Ensure lib dir is in path
        $llib = strtolower($lib);
        $ulib = ucfirst($lib);
        (!empty($path) && strtolower(end($path)) == $llib) || $path[] = $ulib;

        // Check for driver name
        if ($driver) {
            // Add drivers subdir
            $path[] = 'drivers';
            $path[] = $driver;
            $ldrv = strtolower($driver);
            $udrv = ucfirst($driver);

            // Check for subdriver
            if ($subdrv) {
                // Add subdrivers subdir, and subdriver name with lib prefix if not present
                $lsub = strtolower($subdrv);
                $usub = ucfirst($subdrv);
                $path[] = 'subdrivers';
                $path[] = strpos($lsub, $llib.$ldrv) === false ? $ulib.$udrv.$usub : $usub;
            }
            else {
                // Add driver name with lib prefix if not present
                $path[] = strpos($ldrv, $llib) === false ? $ulib.$udrv : $udrv;
            }
        }
        else {
            // Add library class name
            $path[] = $lib;
        }

        // Determine which format to return
        if ($ns) {
            // Set lib to final namespace\class and return empty hint, as
            // loadObject() doesn't need one for a namespaced class
            $lib = implode('\\', $path);
            return '';
        }
        else {
            // Set lib to final class and return imploded path
            // loadObject() needs them separate, and driver() will reassemble
            $lib = array_pop($path);
            return implode('/', $path);
        }
    }

    /**
     * Load a resource object
     *
     * Optionally loads resource configuration, parses name to establish
     * class and hint for loadClass, and attaches loaded object to framework.
     *
     * Resource name may have a full namespace (with backslashes) or a path
     * (with slashes) from the namespace root or the type folder to the class.
     *
     * @uses    Xylophone::loadClass()
     * @uses    Loader::makeDriverPath()
     * @used-by Loader::library()
     * @used-by Loader::driver()
     * @used-by Loader::controller()
     * @used-by Loader::model()
     *
     * @param   string|array    $resource   Resource name or array of names with optional object name keys
     * @param   string          $type       Resource type
     * @param   string          $obj_name   Optional object name
     * @param   array           $params     Optional parameters
     * @param   bool|array      $config     Load configuration flag or array of config params
     * @return  void
     */
    protected function loadObject($resource, $type, $obj_name = null, $params = null, $config = false)
    {
        global $XY;

        // Check for empty name
        if (empty($resource)) {
            return;
        }

        // Check for array
        if (is_array($resource)) {
            // Iterate array
            foreach ($resource as $key => $name) {
                // Use string keys as object names
                $obj_name = is_int($key) ? null : $key;
                $this->loadObject($name, $type, $obj_name, $params, $config);
            }
            return;
        }

        // Trim slashes
        $resource = trim($resource, '/');

        // Check for any path
        if (($slash = strrpos($resource, '/')) !== false) {
            // Set class and extract path as hint
            $class = substr($resource, $slash + 1);
            $hint = substr($resource, 0, $slash);

            // Prepend type to hint if missing
            strpos($hint, $type) === false && $hint = $type.'/'.$hint;
        }
        else {
            // Set class and use type as hint
            // If class has namespace, loadClass will handle that and ignore hint
            $class = $resource;
            $hint = $type;
        }

        // Determine object name and check if used
        $obj_name || $obj_name = strtolower($class);
        isset($this->varmap[$obj_name]) && $obj_name = $this->varmap[$obj_name];
        if (isset($XY->$obj_name)) {
            // Name in use - check class
            $exists = get_class($XY->$obj_name);
            if ($exists === $class) {
                $XY->logger->debug($class.' already loaded as "'.$obj_name.'". Second attempt ignored.');
                return;
            }
            else {
                // Error out
                $XY->showError('Resource "'.$obj_name.'" already exists as a '.$exists.' instance.');
            }
        }

        // Clean params and check config flag
        is_array($params) || $params = null;
        $extras = false;
        if ($config) {
            // Get file name
            $file = strtolower($class);

            // Check for extra config params
            if (is_array($config)) {
                // Use file name if provided and gather any extras
                isset($config['file']) && $file = $config['file'];
                $extras = array();
            }

            // See if there's a config file for the class
            $main = $XY->config->getExtra($file.'.php', 'config', $extras);
            if (is_array($main)) {
                // Override config with any params
                $params = $params ? array_merge_recursive($main, $params) : $main;
            }
            if (isset($config['extras']) && is_array($config['extras'] && !empty($config['extras']))) {
                // Override extras with any passed in
                $extras = is_array($extras) ? array_merge_recursive($extras, $config['extras']) : $config['extras'];
            }

            // See if we need to add a driver name to the class
            if (isset($config['driver']) && $config['driver']) {
                // Check for driver or active group config
                $driver = false;
                $subdriver = false;
                if (isset($extras['driver'])) {
                    // Use driver name
                    $driver = $extras['driver'];
                    if (isset($extras['subdriver'])) {
                        $subdriver = $extras['subdriver'];
                    }
                }
                else if (isset($extras['active_group'])) {
                    // Check for database active group driver
                    $group = $extras['active_group'];
                    if (isset($params[$group]['driver'])) {
                        // Use active group driver
                        $driver = $params[$group]['driver'];
                        if (isset($params[$group]['subdriver'])) {
                            // Use active group subdriver
                            $subdriver = $params[$group]['subdriver'];
                        }
                        else if (($pos = strpos($params[$group]['dsn'])) !== false) {
                            // Check DSN for subdriver
                            $temp = substr($params[$group]['dsn'], 0, $pos);
                            $temp === $driver || $subdriver = $temp;
                        }
                    }
                    else if (isset($params[$group]['dsn'])) {
                        // Extract driver from active group DSN
                        ($pos = strpos($params[$group]['dsn'])) === false ||
                            $driver = substr($params[$group]['dsn'], 0, $pos);
                    }
                }

                // Add driver/subdriver to namespace or path
                $hint = $this->makeDriverPath($class, $hint, $driver, $subdriver);
            }
        }

        // Null extras if not loaded
        $extras || $extras = null;

        // Set global query builder flag for database. When the DbLibrary class
        // gets loaded, this will tell it which base class to extend.
        $GLOBALS['XY_DB_QB'] = (isset($extras['query_builder']) && $extras['query_builder']);

        // Allow 5 retries for unsupported classes
        for ($i = 0; $i < 5; ++$i) {
            try {
                // Load class and set to object name
                ($XY->$obj_name = $XY->loadClass($class, $hint, $params, $extras)) ||
                    $XY->showError('Could not load class "'.$resource.'"');
                break;
            } catch (UnsupportedException $ex) {
                // Check for alternate class
                if ($ex->alternate !== '') {
                    // Log substitution
                    $XY->logger->debug('Class "'.$resource.'" is unsupported, trying alternate "'.$ex->alternate.'"');

                    // Check for namespace
                    if (strpos($class, '\\') === false) {
                        // Replace class with alternate
                        $class = ucfirst($ex->alternate);
                    }
                    else {
                        // Break apart namespace, replace class, and reassemble
                        $class = explode('\\', $class);
                        array_pop($class);
                        $class[] = ucfirst($ex->alternate);
                        $class = implode('\\', $class);
                    }
                }
                else {
                    // Notify user and quit
                    $XY->showError('Class "'.$resource.'" is not supported in this environment');
                }
            }
        }
    }
}

