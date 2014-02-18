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
 * Config Class
 *
 * This class contains functions that enable config files to be managed
 *
 * @package     Xylophone
 * @subpackage  core
 * @author      Xylophone Dev Team
 * @link        http://xylophone.io/user_guide/libraries/config.html
 */
class Config implements \ArrayAccess
{
    /** @var    array   List of all loaded config values */
    public $config = array();

    /** @var    array   List of all loaded config files */
    public $is_loaded = array();

    /**
     * Class constructor
     *
     * Sets the $config data from the primary config.php file as a class variable.
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // Read the config file
        $this->config = $this->get('config.php', 'config');
        if (!is_array($this->config)) {
            $msg = ($this->config === false) ? 'The configuration file does not exist.' :
                'The configuration file is invalid.';
            throw new ExitException($msg);
        }

        // Set the base_url automatically if none was provided
        if (empty($this->config['base_url'])) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $base_url = ($XY->isHttps() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].
                    dirname($_SERVER['SCRIPT_NAME']).'/';
            }
            else {
                $base_url = 'http://localhost/';
            }

            $this->setItem('base_url', $base_url);
        }

        // NOTE: Do not log messages from this constructor
    }

    /**
     * Load Config File
     *
     * @param   mixed   $file       Configuration file name or array of file names
     * @param   bool    $sections   Whether configuration values should be loaded into their own section
     * @param   bool    $graceful   Whether to just return FALSE or display an error message
     * @return  bool    TRUE if the file was loaded correctly or FALSE on failure
     */
    public function load($file = '', $sections = false, $graceful = false)
    {
        global $XY;

        foreach ((array)$file as $name) {
            // Strip .php from file
            $name = ($name === '') ? 'config' : str_replace('.php', '', $name);

            // Make sure file isn't already loaded
            if (in_array($name, $this->is_loaded)) {
                continue;
            }

            // Get config array and check result
            $config = $this->get($name.'.php', 'config');
            if ($config === false) {
                if ($graceful) {
                    return false;
                }
                return $XY->showError('The configuration file '.$name.'.php does not exist.');
            }
            else if (is_string($config)) {
                if ($graceful) {
                    return false;
                }
                return $XY->showError('Your '.$name.'.php file does not appear to contain a valid configuration array.');
            }

            // Check for sections
            if ($sections === true) {
                // Merge or set section
                $this->config[$name] = isset($this->config[$name]) ?
                    array_replace_recursive($this->config[$name], $config) : $this->config[$name] = $config;
            }
            else {
                // Merge config
                $this->config = array_replace_recursive($this->config, $config);
            }

            // Mark file as loaded
            $this->is_loaded[] = $name;
            $XY->logger->debug('Config file loaded: '.$name.'.php');
        }

        return true;
    }

    /**
     * Get config file contents
     *
     * Reads and merges config arrays from named config files
     *
     * @uses    Config::getExtra()
     *
     * @param   string  $file   Config file name
     * @param   string  $name   Array name to look for
     * @return  mixed   Merged config if found, TRUE if no array requested,
     *                  file path if array is bad, otherwise FALSE
     */
    public function get($file, $name)
    {
        $extras = false;
        return $this->getExtra($file, $name, $extras);
    }

    /**
     * Get config file contents with extra vars
     *
     * Reads and merges config arrays from named config files.
     * Any standalone variables not starting with an underscore are gathered
     * and returned via $_extras. For this reason, all local variables start
     * with an underscore.
     *
     * @used-by Config::get()
     *
     * @param   string  $_file      Config file name
     * @param   string  $_name      Array name to look for
     * @param   array   $_extras    Reference to extras array
     * @return  mixed   Merged config if found, TRUE if no array requested,
     *                  file path if array is bad, otherwise FALSE
     */
    public function getExtra($_file, $_name, &$_extras)
    {
        global $XY;

        // Ensure file ends with .php
        preg_match('/\.php$/', $_file) || $_file .= '.php';

        // Merge arrays from all viable config paths
        $_merged = array();
        $_versions = array($_file);
        $XY->environment && array_unshift($_versions, $XY->environment.DIRECTORY_SEPARATOR.$_file);
        foreach ($XY->config_paths as $_path) {
            // Check with/without environment
            foreach ($_versions as $_version) {
                // Determine if file exists here
                $_file_path = $_path.'config'.DIRECTORY_SEPARATOR.$_version;
                if (@include($_file_path)) {
                    // See if we're gathering extra variables
                    if ($_extras !== false) {
                        // Get associative array of public vars
                        foreach (get_defined_vars() as $_key => $_var) {
                            $_key[0] !== '_' && $_key !== $_name && $_key !== 'this' && $_key != 'XY' &&
                                $_extras[$_key] = $_var;
                        }
                    }

                    // See if we have an array name to check for
                    if (empty($_name)) {
                        // Nope - just note we found something
                        $_merged = true;
                        continue;
                    }

                    // Return bad filename if no array
                    if (!isset($$_name) || !is_array($$_name)) {
                        return $_file_path;
                    }

                    // Merge config and unset temporary copy
                    $_merged = $_merged === true ? $$_name : array_replace_recursive($_merged, $$_name);
                    unset($$_name);

                    // Go to next path
                    continue 2;
                }
            }
        }

        // Return merged config or FALSE
        return empty($_merged) ? false : $_merged;
    }

    /**
     * Fetch a config file item
     *
     * @param   string  $item   Config item name
     * @param   string  $index  Index name
     * @return  mixed   The configuration item or NULL if the item doesn't exist
     */
    public function item($item, $index = '')
    {
        if ($index === '') {
            return isset($this->config[$item]) ? $this->config[$item] : null;
        }
        return isset($this->config[$index][$item]) ? $this->config[$index][$item] : null;
    }

    /**
     * Fetch a config file item with slash appended (if not empty)
     *
     * @param   string  $item   Config item name
     * @return  mixed   The configuration item or NULL if the item doesn't exist
     */
    public function slashItem($item)
    {
        if (!isset($this->config[$item])) {
            return null;
        }
        elseif (trim($this->config[$item]) === '') {
            return '';
        }

        return rtrim($this->config[$item], '/').'/';
    }

    /**
     * Site URL
     *
     * Returns base_url . index_page [. uri_string]
     *
     * @uses    Config::uriString()
     *
     * @param   mixed   $uri        URI string or an array of segments
     * @param   string  $protocol   Protocol
     * @return  string  Site URL
     */
    public function siteUrl($uri = '', $protocol = null)
    {
        // Get base URL and replace protocol if specified
        $base_url = $this->slashItem('base_url');
        isset($protocol) && $base_url = $protocol.substr($base_url, strpos($base_url, '://'));

        // Check for URI
        if (empty($uri)) {
            // Done here
            return $base_url.$this->item('index_page');
        }

        // Clean URI string and check for query strings
        $uri = $this->uriString($uri);
        if ($this->item('enable_query_strings')) {
            // Assemble base URL with URI string
            return $base_url.$this->item('index_page').$uri;
        }

        // Check for URL suffix
        $suffix = isset($this->config['url_suffix']) ? $this->config['url_suffix'] : '';
        if ($suffix !== '') {
            // Add suffix before any query string
            if (($offset = strpos($uri, '?')) === false) {
                $uri .= $suffix;
            }
            else {
                $uri = substr($uri, 0, $offset).$suffix.substr($uri, $offset);
            }
        }

        // Return base URL with URI string
        return $base_url.$this->slashItem('index_page').$uri;
    }

    /**
     * Base URL
     *
     * Returns base_url [. uri_string]
     *
     * @uses    Config::uriString()
     *
     * @param   mixed   $uri        URI string or an array of segments
     * @param   string  $protocol   Protocol
     * @return  string  Base URl
     */
    public function baseUrl($uri = '', $protocol = null)
    {
        $base_url = $this->slashItem('base_url');
        isset($protocol) && $base_url = $protocol.substr($base_url, strpos($base_url, '://'));
        return $base_url.ltrim($this->uriString($uri), '/');
    }

    /**
     * Build URI string
     *
     * @used-by Config::siteUrl()
     * @used-by Config::baseUrl()
     *
     * @param   mixed   $uri        URI string or an array of segments
     * @return  string  URI string
     */
    protected function uriString($uri)
    {
        if (!$this->item('enable_query_strings')) {
            is_array($uri) && $uri = implode('/', $uri);
            return trim($uri, '/');
        }
        elseif (is_array($uri)) {
            $uri = http_build_query($uri);
        }

        strpos($uri, '?') === false && $uri = '?'.$uri;
        return $uri;
    }

    /**
     * Set a config file item
     *
     * @param   mixed   $item   Config item key or array of config items
     * @param   string  $value  Config item value
     * @return  void
     */
    public function setItem($item, $value = '')
    {
        // Check for multiple items
        if (is_array($item)) {
            // Merge into config
            $this->config = array_replace_recursive($this->config, $item);
        }
        else {
            // Set single item
            $this->config[$item] = $value;
        }
    }

    /**
     * Check item existence by offset
     *
     * @interface   ArrayAccess
     *
     * @param   string  $offset Config item key
     * @return  bool    TRUE if item exists, otherwise FALSE
     */
    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    /**
     * Get config item by offset
     *
     * @interface   ArrayAccess
     *
     * @param   string  $offset Config item key
     * @return  mixed   Config item value
     */
    public function offsetGet($offset)
    {
        return isset($this->config[$offset]) ? $this->config[$offset] : null;
    }

    /**
     * Set config item by offset
     *
     * @interface   ArrayAccess
     *
     * @param   string  $offset Config item key
     * @param   mixed   $value  Config item value
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        // Only string keys, please
        is_string($offset) && $this->config[$offset] = $value;
    }

    /**
     * Unset config item by offset
     *
     * @interface   ArrayAccess
     *
     * @param   string  $offset Config item key
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }
}

