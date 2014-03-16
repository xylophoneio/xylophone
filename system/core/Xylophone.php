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
 * Xylophone Framework Class
 *
 * The exit constants defined here are designed to avoid conflict with other
 * program exit codes, such as those defined at:
 *  Standard C/C++ Library (stdlibc):
 *      http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 *  BSD sysexits.h:
 *      http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 *  Bash scripting:
 *      http://tldp.org/LDP/abs/html/exitcodes.html
 *
 * @package     Xylophone
 * @subpackage  core
 */
class Xylophone
{
    /** @var    string  Xylophone version */
    public $version = '1.0-dev';

    /** @var    string  Environment name */
    public $environment = '';

    /** @var    array   Autoloader namespace/path list with trailing slashes */
    public $ns_paths = array();

    /** @var    array   Config paths with trailing slashes */
    public $config_paths = array();

    /** @var    array   View paths with trailing slashes */
    public $view_paths = array();

    /** @var    string  Application namespace */
    public $app_ns = '';

    /** @var    string  Application path with trailing slash */
    public $app_path = '';

    /** @var    string  System namespace */
    public $system_ns = 'Xylophone';

    /** @var    string  System path with trailing slash */
    public $system_path = '';

    /** @var    string  Application front controller base path with trailing slash */
    public $base_path = '';

    /** @var    bool    Whether to search for core class overrides */
    public $override_core = false;

    /** @var    bool    Whether to search for library overrides */
    public $library_search = false;

    /** @var    int     Initial output buffer level for reference */
    public $init_ob_level = 0;

    /** @var    array   Header status codes */
    public static $status_codes = array(
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /** @var    array   Relative path resolution bases */
    protected $resolve_bases = array();

    /** @var    array   PHP version comparison results */
    protected $is_php = array();

    /** @var    array   Suhosin function blacklist */
    protected $suhosin_blist = null;

    /** @var    string  Autoloader hint */
    protected $loader_hint = '';

    /** @var    object  Xylophone singleton instance */
    private static $instance = null;

    /** No errors */
    const EXIT_SUCCESS = 0;

    /** Generic error exit code */
    const EXIT_ERROR = 1;

    /** Configuration error exit code */
    const EXIT_CONFIG = 3;

    /** File not found exit code */
    const EXIT_UNKNOWN_FILE = 4;

    /** Unknown class exit code */
    const EXIT_UNKNOWN_CLASS = 5;

    /** Unknown class member exit code */
    const EXIT_UNKNOWN_METHOD = 6;

    /** Invalid user input exit code */
    const EXIT_USER_INPUT = 7;

    /** Database error exit code */
    const EXIT_DATABASE = 8;

    /** Lowest automatically-assigned exit code */
    const EXIT__AUTO_MIN = 9;

    /** Highest automatically-assigned exit code */
    const EXIT__AUTO_MAX = 125;

    /**
     * Constructor
     *
     * This constructor is private so it can only be called from instance(),
     * and final so it can not be overloaded with a public constructor in a
     * subclass. This enforces the singleton design pattern.
     *
     * Use tune() for class object initialization.
     */
    private final function __construct()
    {
        // Nothing to do here - see tune() below.
    }

    /**
     * Get instance
     *
     * Returns singleton instance of framework object
     *
     * @throws  ExitException   When passed an invalid namespace or path
     *
     * @param   array   $init   Initialization array (when first instantiating)
     * @return  object  Xylophone object
     */
    public static function instance($init = null)
    {
        // Check for existing instance
        if (self::$instance === null) {
            // Set error reporting for default environments
            isset($init['environment']) || $init['environment'] = 'development';
            switch ($init['environment']) {
                case 'development':
                    error_reporting(-1);
                    ini_set('display_errors', 1);
                    break;
                case 'testing':
                case 'production':
                    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
                    ini_set('display_errors', 0);
                    break;
            }

            // Set resolve base paths
            isset($init['resolve_bases']) || $init['resolve_bases'] = array('');

            // Resolve namespace paths and check for Xylophone subclasses
            $namespace = null;
            if (isset($init['ns_paths'])) {
                // Assemble relative file path and check for subclass
                // Since we don't have autoloader yet, we have to manually
                // search and include source files
                $app = true;
                $file = 'core'.DIRECTORY_SEPARATOR.'Xylophone.php';
                foreach ($init['ns_paths'] as $ns => &$path) {
                    // Check namespaces after application
                    if (!$app && !$ns) {
                        // Fail out
                        include_once('ExitException.php');
                        $msg = 'The global namespace is reserved for application classes. '.
                            'Please specify a namespace for your additional path in the following file: '.
                            basename($_SERVER['PHP_SELF']);
                        throw new ExitException($msg, EXIT_XY);
                    }

                    // Resolve the path
                    $resolved = false;
                    foreach ($init['resolve_bases'] as $base) {
                        // Check against base
                        if (is_dir($base.$path)) {
                            // Found - clean with realpath and add trailing slash
                            $path = realpath($base.$path).DIRECTORY_SEPARATOR;
                            $resolved = true;
                            break;
                        }
                    }
                    if (!$resolved) {
                        // Fail out
                        include_once('ExitException.php');
                        $msg = ($app ? 'Your application folder path does not appear to be set correctly.' :
                            'The "'.$ns.'" namespace path does not appear to be set correctly.').
                            ' Please fix it in the following file: '.basename($_SERVER['PHP_SELF']);
                        throw new ExitException($msg, EXIT_XY);
                    }

                    // Try to include the file
                    if (@include_once($path.$file)) {
                        // Found one - use first as namespace, appending sub-namespace if not global
                        $namespace === null && $namespace = $ns ? $ns.'\core\\' : $ns;
                    }

                    // No longer the application path
                    $app = false;
                }
            }

            // Use system namespace if no subclass found and assemble full class name
            $class = ($namespace === null ? 'Xylophone\core\\' : $namespace).'Xylophone';

            // Instantiate object as instance and tune
            self::$instance = new $class();
            self::$instance->tune($init);
        }

        // Return instance
        return self::$instance;
    }

    /**
     * Tune Xylophone
     *
     * @used-by Xylophone::instance()
     *
     * @param   array   $init   Initialization parameters
     * @return  void
     */
    public function tune($init)
    {
        // Kill magic quotes for older versions
        $this->isPhp('5.4') || @ini_set('magic_quotes_runtime', 0);

        // Get buffer level
        $this->init_ob_level = ob_get_level();

        // Set environment
        $this->environment = isset($init['environment']) ? $init['environment'] : '';

        // Set base and system paths and resolve bases with trailing slashes
        // These should all have been cleaned in instance()
        $this->base_path = isset($init['base_path']) ? $init['base_path'] : BASEPATH;
        $this->system_path = isset($init['system_path']) ? $init['system_path'] :
            dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR;
        $this->resolve_bases = isset($init['resolve_bases']) ? $init['resolve_bases'] : array('');

        // Check for initial namespace path list
        if (isset($init['ns_paths']) && is_array($init['ns_paths']) && $init['ns_paths']) {
            // Set initial paths
            $this->ns_paths = $init['ns_paths'];

            // Identify config paths
            $this->config_paths = array();
            foreach ($this->ns_paths as $path) {
                // Check for config folder
                is_dir($path.'config') && $this->config_paths[] = $path;
            }

            // Reverse config order so higher-priority items override lower ones in merge
            $this->config_paths = array_reverse($this->config_paths);
        }
        else {
            // Assume global namespace and standard path
            // This should never happen unless someone has monkeyed with index.php
            $path = $this->base_path.'application'.DIRECTORY_SEPARATOR;
            $this->ns_paths = array('' => $path);
            $this->config_paths = array($path);
        }

        // Set references to application namespace and path
        $this->app_ns = key($this->ns_paths);
        $this->app_path =& $this->ns_paths[$this->app_ns];

        // Set view paths
        $this->view_paths = array();
        isset($init['view_paths']) || $init['view_paths'] = array('');
        $this->addViewPath($init['view_paths']);

        // Set override flags
        isset($init['override_core']) && $this->override_core = $init['override_core'];
        isset($init['library_search']) && $this->library_search = $init['library_search'];

        // Register handlers
        $this->registerHandlers();
    }

    /**
     * Play the Xylophone application
     *
     * @param   mixed   $benchmark  Initial benchmark time or FALSE
     * @param   array   $config     Optional config overrides
     * @param   array   $routing    Optional routing overrides
     * @return  void
     */
    public function play($benchmark = false, $config = null, $routing = null)
    {
        // Play all the parts
        $autoload = $this->playIntro($benchmark, $config);
        $this->playBridge($routing);
        if ($this->playCoda()) {
            // Cache delivered - quit
            return;
        }
        $this->playChorus($benchmark, $autoload);
        $this->playVerse($benchmark);
    }

    /**
     * Play Introduction
     *
     * Prepares the basic foundation services by loading
     * Benchmark (if enabled), Config, and Logger.
     * Gets constants, mime types, and autoload config.
     * Autoloads namespaces, view paths, and config items.
     *
     * @used-by Xylophone::play()
     *
     * @param   mixed   $benchmark  Initial benchmark time or FALSE
     * @param   array   $config     Reference to bootstrap config items
     * @return  array   Reference to autoload config array
     */
    protected function &playIntro($benchmark, &$config)
    {
        // Check for benchmarking
        if ($benchmark) {
            // Load Benchmark and initiate timing
            $this->benchmark = $this->loadClass('Benchmark', 'core');
            $this->benchmark->marker['total_execution_time_start'] = $benchmark;
            $this->benchmark->marker['loading_time:_base_classes_start'] = $benchmark;
        }

        // Load Config, set overrides, load constants and get autoload config
        $this->config = $this->loadClass('Config', 'core');
        is_array($config) && $this->config->setItem($config);
        $this->config->get('constants.php', false);
        $autoload = $this->config->get('autoload.php', 'autoload');

        // Load Logger so we can log messages and errors
        $this->logger = $this->loadClass('Logger', 'core');

        // Auto-load namespaces, view paths, config files
        isset($autoload['namespaces']) && $this->addNamespace($autoload['namespaces']);
        isset($autoload['view_paths']) && $this->addViewPath($autoload['view_paths']);
        isset($autoload['config']) && $this->config->load($autoload['config']);

        // Load mime types
        $mimes = $this->config->get('mimes.php', 'mimes');
        is_array($mimes) && $this->config->setItem('mimes', $mimes);

        // Return autoload for chorus
        return $autoload;
    }

    /**
     * Play Bridge
     *
     * Builds on the intro foundation by loading
     * Loader, Hooks, Output, UTF-8, URI, and Router.
     * Sets routing overrides if provided.
     * Calls pre_system hook.
     *
     * @used-by Xylophone::play()
     *
     * @param   array   $routing    Routing overrides
     * @return  void
     */
    protected function playBridge(&$routing)
    {
        // Load Loader and Hooks (which depends on Loader), and call pre_system
        $this->load = $this->loadClass('Loader', 'core');
        $this->hooks = $this->loadClass('Hooks', 'core');
        $this->hooks->callHook('pre_system');

        // Load Output, UTF-8, URI, and Router, and set overrides
        $this->output = $this->loadClass('Output', 'core');
        $this->utf8 = $this->loadClass('Utf8', 'core');
        $this->uri = $this->loadClass('URI', 'core');
        $this->router = $this->loadClass('Router', 'core');
        isset($routing['directory']) && $this->router->route['path'] = $routing['directory'];
        isset($routing['controller']) && $this->router->route['class'] = $routing['controller'];
        isset($routing['function']) && $this->router->route['method'] = $routing['function'];
    }

    /**
     * Play Coda
     *
     * Finishes playing if cached output is found.
     * Calls cache_override hook.
     *
     * @used-by Xylophone::play()
     *
     * @return  bool    TRUE if cache output, otherwise FALSE
     */
    protected function playCoda()
    {
        // Check cache_override hook and cache display to see if we're done
        if ($this->hooks->callHook('cache_override') === false && $this->output->displayCache() === true) {
            // Cache displayed - quit
            return true;
        }

        // No cache
        return false;
    }

    /**
     * Play Chorus
     *
     * Rounds out the chorus of elements by loading Security, Input, and Lang.
     * Autoloads languages, drivers, libraries, and models.
     *
     * @used-by Xylophone::play()
     *
     * @param   mixed   $benchmark  Benchmark flag
     * @param   array   $autoload   Reference to autoload config array
     * @return  void
     */
    protected function playChorus($benchmark, &$autoload)
    {
        // Load remaining core classes and mark time
        $this->security = $this->loadClass('Security', 'core');
        $this->input = $this->loadClass('Input', 'core');
        $this->lang = $this->loadClass('Lang', 'core');
        $benchmark && $this->benchmark->mark('loading_time:_base_classes_end');

        // Load remaining autoload resources
        isset($autoload['language']) && $this->lang->load($autoload['language']);
        isset($autoload['drivers']) && $this->load->driver($autoload['drivers']);
        isset($autoload['libraries']) && $this->load->library($autoload['libraries']);
        isset($autoload['model']) && $this->load->model($autoload['model']);
    }

    /**
     * Play Verse
     *
     * Presents the main verse by loading the controller and
     * calling the routed method.
     * Calls pre_controller, post_controller_constructor, post_controller,
     * display_override, and post_system hooks.
     *
     * @used-by Xylophone::play();
     *
     * @param   mixed   $benchmark  Benchmark flag
     * @return  void
     */
    protected function playVerse($benchmark)
    {
        // Call pre_controller and mark controller start point
        $this->hooks->callHook('pre_controller');
        $benchmark && $this->benchmark->mark('controller_execution_time_start');

        // Load the controller, but don't call the method yet
        $parts = explode('\\', $this->router->route['class']);
        $class = end($parts);
        $name = strtolower($class);
        $method = $this->router->route['method'];
        $this->load->controller($this->router->route, $name, false) || $this->show404($class.'/'.$method);

        // Set special "routed" reference to routed Controller,
        // call post_controller_constructor, and call controller method
        $this->routed = $this->$name;
        $this->hooks->callHook('post_controller_constructor');
        try {
            $this->callController($this->router->route) || $this->show404($class.'/'.$method);
        } catch (AlFineException $ex) {
            // Finished early - nothing to do here but carry on to the end
        }

        // Mark end time, display output unless overridden, and call post_system
        $benchmark && $this->benchmark->mark('controller_execution_time_end');
        $this->hooks->callHook('post_controller');
        $this->hooks->callHook('display_override') || $this->output->display();
        $this->hooks->callHook('post_system');
    }

    /**
     * Call a controller method
     *
     * Requires that controller already be loaded, validates method name, and calls
     * remap if available.
     *
     * @param   mixed   $class  Class name string or route stack array
     * @param   string  $method Method name (unless $class is stack)
     * @param   array   $args   Arguments array (unless $class is stack)
     * @param   string  $name   Optional object name
     * @param   bool    $return Whether to return output
     * @return  mixed   Output if $return, TRUE on success, otherwise FALSE
     */
    public function callController($class, $method = '', array $args = array(), $name = '', $return = false)
    {
        // Check for route stack
        if (is_array($class) && isset($class['class'])) {
            $method = isset($class['method']) ? $class['method'] : 'index';
            $args = isset($class['args']) ? $class['args'] : array();
            $class = $class['class'];
        }

        // Now class name must be a string
        if (!is_string($class)) {
            return false;
        }

        // Default name if not provided
        if (empty($name)) {
            // Break apart namespace and get bare class name
            $parts = explode('\\', $class);
            $name = strtolower(end($parts));
        }

        // Class must be loaded and method cannot start with an underscore
        if (class_exists($class, false) && isset($this->$name) && is_a($this->$name, $class) && $method[0] != '_') {
            // Capture output if requested
            $return && $this->output->stackPush();

            // Check for remap
            if ($this->isCallable($class, 'xyRemap')) {
                // Call remap
                $this->$name->xyRemap($method, $args);
            }
            elseif ($this->isCallable($class, $method)) {
                // Call method
                call_user_func_array(array(&$this->$name, $method), $args);
            }
            else {
                // Remove output layer and fail
                $return && $this->output->stackPop();
                return false;
            }

            // Return captured output or success
            return $return ? $this->output->stackPop() : true;
        }

        // Neither remap nor method could be called
        return false;
    }

    /**
     * Load a class
     *
     * This is the framework class instantiator. It creates a class object,
     * searching registered namespaces as necessary. It relies on autoloader()
     * to include the class source file based on the namespace, passing a path
     * hint if the global namespace is used.
     *
     * @used-by Xylophone::play()
     * @used-by Loader::loadResource()
     *
     * @param   string  $name   Class name with optional namespace
     * @param   string  $hint   Namespace hint (if namespace not provided) - forward slashes
     * @param   mixed   $param  Optional constructor parameter
     * @param   mixed   $param2 Optional second constructor parameter
     * @return  object  Class object on success, otherwise NULL
     */
    public function loadClass($name, $hint, $param = null, $param2 = null)
    {
        // Check for namespace
        $pos = strrpos($name, '\\');
        if ($pos === false) {
            // Check for core or library class
            $core = ($hint == 'core');
            $libs = (strpos($hint, 'libraries') !== false);

            // Check for bare system class
            if (($core && !$this->override_core) || ($libs && !$this->library_search)) {
                // Search only system namespace
                $spaces = array($this->system_ns);
            }
            else {
                // Search all namespaces, plus system for core or libs
                $spaces = array_keys($this->ns_paths);
                ($core || $libs) && $spaces[] = $this->system_ns;
            }
        }
        else {
            // Break apart namespace and only search there
            $spaces = array(substr($name, 0, $pos));
            $name = substr($name, $pos + 1);
            $hint = '';
        }

        // Check each namespace for a subclass
        $module = null;
        foreach ($spaces as $ns) {
            try {
                // Assemble namespace and class
                $class = $ns;
                if ($ns === '') {
                    // Global namespace - set hint
                    $this->loader_hint = $hint;
                }
                else {
                    // Add namespace slash
                    $class .= '\\';

                    // Append any hint as sub-namespace
                    $hint === '' || $class .= ltrim(str_replace('/', '\\', $hint), '\\').'\\';
                }
                $class .= $name;

                // Instantiate class
                $module = ($param === null) ? new $class() :
                    ($param2 === null) ? new $class($param) : new $class($param, $param2);

                // If we got here, we're done
                break;
            } catch (AutoloadException $ex) {
                // Nothing to do here - just keep searching
            }
        }

        // Return module or NULL
        return $module;
    }

    /**
     * Add a namespace path
     *
     * Registers a class namespace with its base path for autoloader searching.
     *
     * @param   mixed   $namespace  Top-level namespace or array of namespace/path pairs
     * @param   string  $path       Path to source files
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function addNamespace($namespace, $path = '')
    {
        // Convert to array
        is_array($namespace) || $namespace = array($namespace => $path);

        // Iterate namespaces
        foreach ($namespace as $ns => $path) {
            // Check for namespace - only application can be global
            if (!$ns) {
                return false;
            }

            // Resolve the path
            foreach ($this->resolve_bases as $base) {
                // Check against base
                if (is_dir($base.$path)) {
                    // Add to namespaces and continue to next
                    $ns_path = $this->realpath($base.$path).DIRECTORY_SEPARATOR;
                    $this->ns_paths[$ns] = $ns_path;
                    is_dir($ns_path.'config') && array_unshift($this->config_paths, $ns_path);
                    continue 2;
                }
            }

            // Unresolved - fail
            return false;
        }

        // All namespaces added
        return true;
    }

    /**
     * Remove a namespace path
     *
     * Unregisters a namespace from the autoloader search path.
     *
     * @param   string  $namespace  Top-level namespace
     * @return  void
     */
    public function removeNamespace($namespace)
    {
        // Just unset namespace
        unset($this->ns_paths[$namespace]);
    }

    /**
     * Add a view path
     *
     * Adds a path where Loader can find view files.
     *
     * @param   mixed   $view_path  View path or array of paths
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function addViewPath($view_path)
    {
        // Get resolve bases with app_path first
        $bases = $this->resolve_bases;
        array_unshift($bases, $this->app_path);

        // Iterate paths
        foreach ((array)$view_path as $path) {
            // Default empty to standard views folder
            $vw_path = $path ? $path : 'views';

            // Resolve the path
            foreach ($bases as $base) {
                // Check against base
                if (is_dir($base.$vw_path)) {
                    // Add to paths and continue to next
                    $vw_path = $this->realpath($base.$vw_path).DIRECTORY_SEPARATOR;
                    $this->view_paths[$path] = $vw_path;
                    continue 2;
                }
            }

            // Unresolved - fail
            return false;
        }

        // All paths added
        return true;
    }

    /**
     * Remove a view path
     *
     * @param   string  $view_path  View path
     * @return  void
     */
    public function removeViewPath($view_path)
    {
        // Just unset view path
        unset($this->view_paths[$view_path]);
    }

    /**
     * Determine if the current version of PHP is greater then the supplied value
     *
     * Saves results to speed up repeated checks.
     *
     * @param   mixed   $ver    Version number or string
     * @return  bool    TRUE if PHP is the version or later, otherwise FALSE
     */
    public function isPhp($ver)
    {
        // Convert to string, get result if necessary, and return result
        $ver = (string)$ver;
        isset($this->is_php[$ver]) || $this->is_php[$ver] = (version_compare(PHP_VERSION, $ver) >= 0);
        return $this->is_php[$ver];
    }

    /**
     * Is HTTPS?
     *
     * Determines if the application is accessed via an encrypted (HTTPS) connection.
     *
     * @return  bool    TRUE if HTTPS, otherwise FALSE
     */
    public function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Is CLI?
     *
     * Test to see if a request was made from the command line.
     *
     * @return  bool    TRUE if CLI, otherwise FALSE
     */
    public function isCli()
    {
        // Check SAPI and STDIN
        return (PHP_SAPI === 'cli' || defined('STDIN'));
    }

    /**
     * Determine if a class method can actually be called (from outside the class)
     *
     * @param   mixed   $class  Class name or object
     * @param   string  $method Method
     * @return  bool    TRUE if publicly callable, otherwise FALSE
     */
    public function isCallable($class, $method)
    {
        // Just return whether the case-insensitive method is in the public methods
        return in_array(strtolower($method), array_map('strtolower', get_class_methods($class)));
    }

    /**
     * Function usable
     *
     * Executes a function_exists() check, and if the Suhosin PHP extension is
     * loaded - checks whether the function that is checked might be disabled in
     * there as well.
     *
     * This is useful as function_exists() will return FALSE for functions
     * disabled via the *disable_functions* php.ini setting, but not for
     * *suhosin.executor.func.blacklist* and *suhosin.executor.disable_eval*.
     * These settings will just terminate script execution if a disabled
     * function is executed.
     *
     * @link    http://www.hardened-php.net/suhosin/
     *
     * @param   string  $func   Function to check for
     * @return  bool    TRUE if the function is usable, otherwise FALSE
     */
    public function isUsable($func)
    {
        // Does the function exist?
        if (function_exists($func) || $func === 'eval') {
            // Have we loaded the suhosin blacklist?
            if (!isset($this->suhosin_blist)) {
                // Is suhosin loaded? - this is too unpredictable to test
                // @codeCoverageIgnoreStart
                if (extension_loaded('suhosin')) {
                    // Get blacklist
                    $this->suhosin_blist = explode(',', trim(@ini_get('suhosin.executor.func.blacklist')));

                    // Add eval if flagged
                    if (!in_array('eval', $this->suhosin_blist, true) && @ini_get('suhosin.executor.disable_eval')) {
                        $this->suhosin_blist[] = 'eval';
                    }
                }
                else {
                    // Set empty blacklist
                    $this->suhosin_blist = array();
                }
                // @codeCoverageIgnoreEnd
            }

            // Return whether function is not blacklisted
            return !in_array($func, $this->suhosin_blist, true);
        }

        // Can't use what doesn't exist
        return false;
    }

    /**
     * Test for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @param   string  $file   File path
     * @return  void
     */
    public function isWritable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && ($this->isPhp('5.4') || (bool)@ini_get('safe_mode') === false) &&
        !defined('TESTPATH')) {
            // @codeCoverageIgnoreStart
            return is_writable($file);
            // @codeCoverageIgnoreEnd
        }

        // In case constants are not defined, use defaults
        $cmode = defined('FOPEN_WRITE_CREATE') ? FOPEN_WRITE_CREATE : 'a+b';
        $wmode = defined('DIR_WRITE_MODE') ? DIR_WRITE_MODE : 0777;

        // For Windows servers and safe_mode "on" installations, write a file then read it
        if (is_dir($file)) {
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            if (($fp = @fopen($file, $cmode)) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, $wmode);
            @unlink($file);
            return true;
        }
        elseif (is_file($file) && ($fp = @fopen($file, $cmode)) !== false) {
            fclose($fp);
            return true;
        }

        return false;
    }

    /**
     * Show 404 error to user
     *
     * This function is similar to showError() above, but it displays a 404 error.
     *
     * @param   string  $page       Page URL
     * @param   bool    $log_error  Whether to log the error
     * @return  void
     */
    public function show404($page = '', $log_error = true)
    {
        // Call 404 on exceptions
        $this->loadClass('Exceptions', 'core')->show404($page, $log_error);
    }

    /**
     * Show error to user
     *
     * This function lets us invoke the exception class and display errors
     * using the standard error template located in application/views/errors/error_general.php
     * This function will send the error page directly to the browser and exit.
     *
     * @param   string  $message    Error message
     * @param   int     $status     Exit status code
     * @param   string  $heading    Page heading
     * @return  void
     */
    public function showError($message, $status = 500, $heading = 'An Error Was Encountered')
    {
        // Call error on exceptions
        $this->loadClass('Exceptions', 'core')->showError($heading, $message, 'error_general', $status);
    }

    /**
     * SPL Class Autoloader
     *
     * The autoloader, registered in tune(), includes a class source file
     * based on the namespace and a path hint if the global namespace is used.
     *
     * @throws  AutloadException    When source file is not found
     *
     * @param   string  $class  Class name with full namespace
     * @return  void
     */
    public function autoloader($class)
    {
        // Break out namespace and class
        $parts = explode('\\', trim($class, '\\'));

        // Get filename from class
        $file = ucfirst(array_pop($parts)).'.php';

        // Get top-level namespace or assume global
        $tln = count($parts) ? array_shift($parts) : '';

        // Translate top-level namespace
        if ($tln == $this->system_ns) {
            $path = $this->system_path;
        }
        elseif (isset($this->ns_paths[$tln])) {
            $path = $this->ns_paths[$tln];
        }

        if (isset($path)) {
            // Append sub-namespaces or hint as subdirectories
            $path .= (count($parts) ?  implode(DIRECTORY_SEPARATOR, $parts) :
                str_replace('/', DIRECTORY_SEPARATOR, $this->loader_hint)).DIRECTORY_SEPARATOR;

            // Include file
            if (@include($path.$file)) {
                return;
            }
        }

        // File not found - throw exception (unless it WAS the exception)
        if (trim($class, '\\') == 'Xylophone\core\AutoloadException') {
            return;
        }
        throw new AutoloadException('Could not find class "'.$class.'"');
    }

    /**
     * Exception Handler
     *
     * This is the custom exception handler that is registered in tune().
     * The main reason we use this is to permit PHP errors to be logged in our
     * own log files since the user may not have access to server logs. Since
     * this function effectively intercepts PHP errors, however, we also need
     * to display errors based on the current error_reporting level.
     * We do that with the use of a PHP error template.
     *
     * @param   int     $severity   Severity code
     * @param   string  $message    Error message
     * @param   string  $filepath   File path
     * @param   int     $line       Line number
     * @return  void
     */
    public function exceptionHandler($severity, $message, $filepath, $line)
    {
        // Should we ignore the error? We'll get the current error_reporting
        // level and add its bits with the severity bits to find out.
        if (($severity & error_reporting()) !== $severity) {
            return;
        }

        // Should we display the error?
        $error = $this->loadClass('Exceptions', 'core');
        ((bool)ini_get('display_errors') === true) && $error->showPhpError($severity, $message, $filepath, $line);

        $error->logException($severity, $message, $filepath, $line);
    }

    /**
     * Shutdown Handler
     *
     * This is the shutdown handler that is registered in tune().
     * We use this to simulate a complete custom exception handler.
     * E_STRICT is purposely ignored because such events may be caught.
     *
     * @codeCoverageIgnore
     *
     * @link    http://insomanic.me.uk/post/229851073/php-trick-catching-fatal-errors-e-error-with-a
     * @return  void
     */
    public function shutdownHandler()
    {
        // Get last error and hand off to exception handler
        $last = error_get_last();
        if (isset($last['type']) &&
        ($last['type'] & (E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING))) {
            $this->exceptionHandler($last['type'], $last['message'], $last['file'], $last['line']);
        }
    }

    /**
     * Register autoloader, error, and shutdown handlers
     *
     * This method allows us to bypass registration during testing.
     *
     * @codeCoverageIgnore
     *
     * @return  void
     */
    protected function registerHandlers()
    {
        // Register custom error and shutdown handlers so we can log PHP errors
        set_error_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));

        // Register our autoloader
        spl_autoload_register(array($this, 'autoloader'));
    }

    /**
     * Get real path
     *
     * This abstraction of the realpath call allows overriding for unit testing
     *
     * @codeCoverageIgnore
     *
     * @param   string  $path   Path to resolve
     * @return  string  Real path
     */
    protected function realpath($path)
    {
        // Normally, we just call realpath()
        return realpath($path);
    }
}

