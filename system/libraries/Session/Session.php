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
namespace Xylophone\libraries\Session;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Session Class
 *
 * A Session driver basically manages an array of name/value pairs with some sort of storage mechanism.
 * To make a new driver, extend the Session class. Overload the initialize method and read or create
 * session data. Then implement a save handler to write changed data to storage, a destroy handler
 * to remove deleted data, and an access handler to expose the data.
 * Put your driver in the libraries/Session/drivers folder anywhere in the loader paths.
 * Already provided are the Native driver, which manages the native PHP $_SESSION array, and
 * the Cookie driver, which manages the data in a browser cookie, with optional extra storage in a database table.
 *
 * @package     Xylophone
 * @subpackage  libraries/Session
 * @link        http://xylophone.io/user_guide/libraries/sessions.html
 */
abstract class Session
{
    /** @var    string  Loaded driver name */
    public $driver = '';

    /** @var    string  Name of session cookie */
    public $cookie_name = 'ci_session';

    /** @var    int     Length of time (in seconds) for sessions to expire */
    public $expiration = 7200;

    /** @var    bool    Whether to kill session on close of browser window */
    public $expire_on_close = false;

    /** @var    bool    Whether to match session on ip address */
    public $match_ip = false;

    /** @var    bool    Whether to match session on user-agent */
    public $match_useragent = true;

    /** @var    int     Interval at which to update session */
    public $time_to_update = 300;

    /** @var    bool    Whether to encrypt the session cookie */
    public $encrypt_cookie = false;

    /** @var    bool    Whether to use to the database for session storage */
    public $use_database = false;

    /** @var    string  Name of the database table in which to store sessions */
    public $table_name = '';

    /** @var    string  Session cookie prefix */
    public $cookie_prefix = '';

    /** @var    string  Session cookie domain */
    public $cookie_domain = '';

    /** @var    string  Session cookie path */
    public $cookie_path = '';

    /** @var    bool    Whether to set the cookie only on HTTPS connections */
    public $cookie_secure = false;

    /** @var    bool    Whether cookie should be allowed only to be sent by the server */
    public $cookie_httponly = false;

    /** @var    string  Timezone to use for the current time */
    public $time_reference = 'local';

    /** @var    string  Key with which to encrypt the session cookie */
    public $encryption_key = '';

    /** @var    array   User data */
    protected $userdata = array();

    const FLASHDATA_KEY = 'flash';
    const FLASHDATA_NEW = ':new:';
    const FLASHDATA_OLD = ':old:';
    const FLASHDATA_EXP = ':exp:';
    const EXPIRATION_KEY = '__expirations';
    const TEMP_EXP_DEF = 300;

    /**
     * Constructor
     *
     * The constructor loads the configured driver ('sess_driver' in config.php or as a parameter), running
     * routines in its constructor, and manages flashdata aging.
     *
     * @param   array   $config Configuration parameters
     * @param   array   $extras Extra config params
     * @return  void
     */
    public function __construct(array $config, array $extras)
    {
        global $XY;

        // No sessions under CLI
        if ($XY->isCli()) {
            return;
        }

        // Save driver name
        isset($extras['driver']) && $this->driver = $extras['driver'];

        // Save config data
        $this->config = $config;

        // Get session config values
        $keys = array(
            'cookie_name',
            'expiration',
            'expire_on_close',
            'match_ip',
            'match_useragent',
            'time_to_update',
            'encrypt_cookie',
            'use_database',
            'table_name'
        );
        foreach ($keys as $key) {
            isset($config[$key]) && $this->$key = $config[$key];
        }

        // Get main config values
        $keys = array(
            'cookie_prefix',
            'cookie_domain',
            'cookie_path',
            'cookie_secure',
            'cookie_httponly',
            'time_reference',
            'encryption_key'
        );
        foreach ($keys as $key) {
            isset($XY->config[$key]) && $this->$key = $XY->config[$key];
        }

        // Initialize session data
        $this->initialize();

        // Delete 'old' flashdata (from last request)
        $this->flashdataSweep();

        // Mark all new flashdata as old (data will be deleted before next request)
        $this->flashdataMark();

        // Delete expired tempdata
        $this->tempdataSweep();

        $XY->logger->debug('Session Class Initialized');
    }

    /**
     * Fetch a specific item from the session array
     *
     * @param   string  $item   Item key
     * @return  string  Item value or NULL if not found
     */
    public function userdata($item)
    {
        return isset($this->userdata[$item]) ? $this->userdata[$item] : null;
    }

    /**
     * Fetch all session data
     *
     * @return  array   User data array
     */
    public function allUserdata()
    {
        return isset($this->userdata) ? $this->userdata : null;
    }

    /**
     * Fetch all flashdata
     *
     * @return  array   Flash data array
     */
    public function allFlashdata()
    {
        $out = array();

        // Loop through all userdata
        foreach ($this->userdata as $key => $val) {
            // if it contains flashdata, add it
            if (strpos($key, self::FLASHDATA_KEY.self::FLASHDATA_OLD) !== false) {
                $key = str_replace(self::FLASHDATA_KEY.self::FLASHDATA_OLD, '', $key);
                $out[$key] = $val;
            }
        }
        return $out;
    }

    /**
     * Add or change data in the "userdata" array
     *
     * @param   mixed   $newdata    Item name or array of items
     * @param   string  $newval     Item value or empty string
     * @return  void
     */
    public function setUserdata($newdata, $newval = '')
    {
        // Wrap config as array if singular
        is_string($newdata) && ($newdata = array($newdata => $newval));

        // Set each name/value pair
        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                $this->userdata[$key] = $val;
            }
        }

        // Tell driver data changed
        $this->save();
    }

    /**
     * Delete a session variable from the "userdata" array
     *
     * @param   mixed   $newdata    Item name or array of item names
     * @return  void
     */
    public function unsetUserdata($newdata)
    {
        // Wrap single name as array
        is_string($newdata) && ($newdata = array($newdata => ''));

        // Unset each item name
        if (count($newdata) > 0) {
            foreach (array_keys($newdata) as $key) {
                unset($this->userdata[$key]);
            }
        }

        // Tell driver data changed
        $this->save();
    }

    /**
     * Determine if an item exists
     *
     * @param   string  $item   Item name
     * @return  bool
     */
    public function hasUserdata($item)
    {
        return isset($this->userdata[$item]);
    }

    /**
     * Add or change flashdata, only available until the next request
     *
     * @param   mixed   $newdata    Item name or array of items
     * @param   string  $newval     Item value or empty string
     * @return  void
     */
    public function setFlashdata($newdata, $newval = '')
    {
        // Wrap item as array if singular
        is_string($newdata) && ($newdata = array($newdata => $newval));

        // Prepend each key name and set value
        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                $flashdata_key = self::FLASHDATA_KEY.self::FLASHDATA_NEW.$key;
                $this->setUserdata($flashdata_key, $val);
            }
        }
    }

    /**
     * Keeps existing flashdata available to next request.
     *
     * @param   mixed   $key    Item key(s)
     * @return  void
     */
    public function keepFlashdata($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $this->keepFlashdata($k);
            }

            return;
        }

        // 'old' flashdata gets removed. Here we mark all flashdata as 'new' to preserve it from _flashdata_sweep()
        // Note the function will return NULL if the $key provided cannot be found
        $old_flashdata_key = self::FLASHDATA_KEY.self::FLASHDATA_OLD.$key;
        $value = $this->userdata($old_flashdata_key);

        $new_flashdata_key = self::FLASHDATA_KEY.self::FLASHDATA_NEW.$key;
        $this->setUserdata($new_flashdata_key, $value);
    }

    /**
     * Fetch a specific flashdata item from the session array
     *
     * @param   string  $key    Item key
     * @return  string
     */
    public function flashdata($key)
    {
        // Prepend key and retrieve value
        $flashdata_key = self::FLASHDATA_KEY.self::FLASHDATA_OLD.$key;
        return $this->userdata($flashdata_key);
    }

    /**
     * Add or change tempdata, only available until expiration
     *
     * @param   mixed   $newdata    Item name or array of items
     * @param   string  $newval     Item value or empty string
     * @param   int     $expire     Item lifetime in seconds or 0 for default
     * @return  void
     */
    public function setTempdata($newdata, $newval = '', $expire = 0)
    {
        // Set expiration time
        $expire = time() + ($expire ? $expire : self::TEMP_EXP_DEF);

        // Wrap item as array if singular
        is_string($newdata) && ($newdata = array($newdata => $newval));

        // Get or create expiration list
        $expirations = $this->userdata(self::EXPIRATION_KEY);
        $expirations || ($expirations = array());

        // Prepend each key name and set value
        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                $tempdata_key = self::FLASHDATA_KEY.self::FLASHDATA_EXP.$key;
                $expirations[$tempdata_key] = $expire;
                $this->setUserdata($tempdata_key, $val);
            }
        }

        // Update expiration list
        $this->setUserdata(self::EXPIRATION_KEY, $expirations);
    }

    /**
     * Delete a temporary session variable from the "userdata" array
     *
     * @param   mixed   $newdata    Item name or array of item names
     * @return  void
     */
    public function unsetTempdata($newdata)
    {
        // Get expirations list
        $expirations = $this->userdata(self::EXPIRATION_KEY);
        if (empty($expirations)) {
            // Nothing to do
            return;
        }

        // Wrap single name as array
        is_string($newdata) && ($newdata = array($newdata => ''));

        // Prepend each item name and unset
        if (count($newdata) > 0) {
            foreach (array_keys($newdata) as $key) {
                $tempdata_key = self::FLASHDATA_KEY.self::FLASHDATA_EXP.$key;
                unset($expirations[$tempdata_key]);
                $this->unsetUserdata($tempdata_key);
            }
        }

        // Update expiration list
        $this->setUserdata(self::EXPIRATION_KEY, $expirations);
    }

    /**
     * Fetch a specific tempdata item from the session array
     *
     * @param   string  $key    Item key
     * @return  string  Tempdata item
     */
    public function tempdata($key)
    {
        // Prepend key and return value
        $tempdata_key = self::FLASHDATA_KEY.self::FLASHDATA_EXP.$key;
        return $this->userdata($tempdata_key);
    }

    /**
     * Identifies flashdata as 'old' for removal
     * when _flashdata_sweep() runs.
     *
     * @return  void
     */
    protected function flashdataMark()
    {
        foreach ($this->all_userdata() as $name => $value) {
            $parts = explode(self::FLASHDATA_NEW, $name);
            if (count($parts) === 2) {
                $new_name = self::FLASHDATA_KEY.self::FLASHDATA_OLD.$parts[1];
                $this->setUserdata($new_name, $value);
                $this->unsetUserdata($name);
            }
        }
    }

    /**
     * Removes all flashdata marked as 'old'
     *
     * @return  void
     */
    protected function flashdataSweep()
    {
        $userdata = $this->allUserdata();
        foreach (array_keys($userdata) as $key) {
            if (strpos($key, self::FLASHDATA_OLD)) {
                $this->unsetUserdata($key);
            }
        }
    }

    /**
     * Removes all expired tempdata
     *
     * @return  void
     */
    protected function tempdataSweep()
    {
        // Get expirations list
        $expirations = $this->userdata(self::EXPIRATION_KEY);
        if (empty($expirations)) {
            // Nothing to do
            return;
        }

        // Unset expired elements
        $now = time();
        $userdata = $this->allUserdata();
        foreach (array_keys($userdata) as $key) {
            if (strpos($key, self::FLASHDATA_EXP) && $expirations[$key] < $now) {
                unset($expirations[$key]);
                $this->unsetUserdata($key);
            }
        }

        // Update expiration list
        $this->setUserdata(self::EXPIRATION_KEY, $expirations);
    }

    /**
     * Set a cookie with the system
     *
     * This abstraction of the setcookie call allows overriding for unit testing
     *
     * @param   string  $name   Cookie name
     * @param   string  $value  Cookie value
     * @param   int     $expire Expiration time
     * @param   string  $path   Cookie path
     * @param   string  $domain Cookie domain
     * @param   bool    $secure Secure connection flag
     * @param   bool    $http   HTTP protocol only flag
     * @return  void
     */
    protected function doSetCookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false,
    $http = false)
    {
        // Normally, we just call setcookie()
        setcookie($name, $value, $expire, $path, $domain, $secure, $http);
    }

    /**
     * Initialize the session data
     *
     * @return  void
     */
    protected function initialize() {
        // Overload this method to initialzie the session
    }

    /**
     * Save the session data
     *
     * Data in the array has changed - perform any storage synchronization
     * necessary. The child class MUST implement this abstract method!
     *
     * @return  void
     */
    abstract public function save();

    /**
     * Destroy the current session
     *
     * Clean up storage for this session - it has been terminated.
     * The child class MUST implement this abstract method!
     *
     * @return  void
     */
    abstract public function destroy();

    /**
     * Regenerate the current session
     *
     * Regenerate the session ID.
     * The child class MUST implement this abstract method!
     *
     * @param   bool    $destroy    Destroy session data flag (default: false)
     * @return  void
     */
    abstract public function regenerate($destroy = false);
}

