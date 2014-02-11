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
namespace Mocks\core;

/**
 * Mock Xylophone Class
 *
 * Facilitates Unit Testing the Xylophone Core Class.
 */
class Xylophone extends \Xylophone\core\Xylophone
{
    /** @var    array   Relative path resolution bases (made public) */
    public $resolve_bases = array();

    /** @var    array   PHP version comparison results (made public) */
    public $is_php = array();

    /** @var    string  Autoloader hint (made public) */
    public $loader_hint = '';

    /** @var    array   Pre-loaded classes to return from loadClass() */
    public $load_class = array();

    /** @var    bool    Return value for callController() */
    public $call_controller = null;

    /** @var    array   Test arguments for play() */
    public $play_args = array();

    /** @var    array   Arguments passed to show404() */
    public $show_404 = null;

    /** @var    bool    Skip initialize() flag */
    public static $skip_init = false;

    /** @var    bool    Skip setHandlers() flag */
    public static $skip_handlers = false;

    /**
     * Initialize framework
     *
     * This overload allows us to conditionally run the real initialize method.
     *
     * @param   array   $init   Initialization parameters
     * @return  void
     */
    public function initialize($init)
    {
        // Call the real method unless we're skipping
        self::$skip_init || parent::initialize($init);
    }

    /**
     * Load a class
     *
     * This overalod allows us to return mock objects instead of running the real method.
     *
     * @param   string  $name   Class name with optional namespace
     * @param   string  $hint   Namespace hint (if namespace not provided) - forward slashes
     * @param   mixed   $param  Optional constructor parameter
     * @param   mixed   $param2 Optional second constructor parameter
     * @return  object  Class object on success, otherwise NULL
     */
    public function loadClass($name, $hint, $param = null, $param2 = null)
    {
        // Intercept pre-loaded classes
        $class = $hint.'\\'.$name;
        if (isset($this->load_class[$class])) {
            return $this->load_class[$class];
        }

        // Otherwise call the real method
        return parent::loadClass($name, $hint, $param, $param2);
    }

    /**
     * Call a controller method
     *
     * This overload allows us to conditionally run the real callController method.
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
        // Check for return value
        if ($this->call_controller !== null) {
            // Check for exception to throw
            if (is_string($this->call_controller)) {
                throw new $this->call_controller();
            }

            // Swap return value with $class arg and return
            $ret = $this->call_controller;
            $this->call_controller = $class;
            return $ret;
        }

        // Otherwise call the real method
        return parent::callController($class, $method, $args, $name, $return);
    }

    /**
     * Play Introduction
     *
     * This overload allows us to conditionally run the real playIntro method.
     *
     * @param   mixed   $benchmark  Initial benchmark time or FALSE
     * @param   array   $config     Reference to bootstrap config items
     * @return  array   Reference to autoload config array
     */
    public function &playIntro($benchmark, &$config)
    {
        // Check for test args
        if (isset($this->play_args['intro_atl'])) {
            $this->play_args['intro_bmk'] = $benchmark;
            $this->play_args['intro_cfg'] =& $config;
            return $this->play_args['intro_atl'];
        }

        // Otherwise call the real method
        return parent::playIntro($benchmark, $config);
    }

    /**
     * Play Bridge
     *
     * This overload allows us to conditionally run the real playBridge method.
     *
     * @param   array   $routing    Routing overrides
     * @return  void
     */
    public function playBridge(&$routing)
    {
        // Check for test args
        if (!empty($this->play_args)) {
            $this->play_args['bridge_rtg'] =& $routing;
            return;
        }

        // Otherwise call the real method
        return parent::playBridge($routing);
    }

    /**
     * Play Coda
     *
     * This overload allows us to conditionally run the real playCoda method.
     *
     * @return  bool    TRUE if cache output, otherwise FALSE
     */
    public function playCoda()
    {
        // Check for test args
        if (isset($this->play_args['coda_ret'])) {
            return $this->play_args['coda_ret'];
        }

        // Otherwise call the real method
        return parent::playCoda();
    }

    /**
     * Play Chorus
     *
     * This overload allows us to conditionally run the real playChorus method.
     *
     * @param   mixed   $benchmark  Benchmark flag
     * @param   array   $autoload   Reference to autoload config array
     * @return  void
     */
    public function playChorus($benchmark, &$autoload)
    {
        // Check for test args
        if (!empty($this->play_args)) {
            $this->play_args['chorus_bmk'] = $benchmark;
            $this->play_args['chorus_atl'] =& $autoload;
            return;
        }

        // Otherwise call the real method
        return parent::playChorus($benchmark, $autoload);
    }

    /**
     * Play Verse
     *
     * This overload allows us to conditionally run the real playVerse method.
     *
     * @param   mixed   $benchmark  Benchmark flag
     * @return  void
     */
    public function playVerse($benchmark)
    {
        // Check for test args
        if (!empty($this->play_args)) {
            $this->play_args['verse_bmk'] = $benchmark;
            return;
        }

        // Otherwise call the real method
        return parent::playVerse($benchmark);
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
        // Check for arguments array
        if (is_array($this->show_404)) {
            // Add page argument to array
            $this->show_404[] = $page;
            return;
        }

        // Otherwise call the real method
        return parent::show404($page, $log_error);
    }

    /**
     * Register autoloader, error, and shutdown handlers
     *
     * This overload allows us to conditionally run the real registerHandlers method.
     *
     * @return  void
     */
    public function registerHandlers()
    {
        // Call the real method unless we're skipping
        self::$skip_handlers || parent::registerHandlers();
    }

    /**
     * Get real path
     *
     * This abstraction of the realpath call allows overriding for unit testing
     *
     * @param   string  $path   Path to resolve
     * @return  string  Real path
     */
    protected function realpath($path)
    {
        // Just trim trailing slash since realpath() fails on VFS urls
        return rtrim($path, '\/');
    }
}

