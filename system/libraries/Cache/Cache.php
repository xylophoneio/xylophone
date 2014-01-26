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
namespace Xylophone\libraries\Cache;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Session
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
abstract class Cache
{
    /** @var    string  Loaded driver name */
    public $driver = '';

    /** @var    string  Cache key prefix */
    public $key_prefix = '';

    /** @var    string  Path of cache files (if file-based cache) */
    protected $cache_path = null;

    /**
     * Constructor
     *
     * Initialize class properties based on the configuration array.
     *
     * @param   array   $config Configuration parameters
     * @param   array   $extras Extra config params
     * @return  void
     */
    public function __construct(array $config = array(), array $extras = array())
    {
        global $XY;

        // Check driver support
        if (!$this->isSupported()) {
            // Try backup if configured, or fall back to dummy driver
            $alternate = isset($extras['backup']) && $extras['backup'] !== $this->driver ? $extras['backup'] : 'dummy';
            throw new \Xylophone\core\UnsupportedException($alternate);
        }

        // Load config values
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }

        // Save driver name
        isset($extras['driver']) && $this->driver = $extras['driver'];

        // Call initialize
        $this->initialize();
    }

    /**
     * Initialize Driver
     *
     * Overload this method to initialize the driver.
     * This method is not abstract so drivers don't have to implement it
     * if they don't need it.
     *
     * @return  void
     */
    protected function initialize()
    {
        // Nothing to do here by default
    }

    /**
     * Get Cache Item
     *
     * @param   string  $id     Item ID
     * @return  mixed   Value on success, otherwise FALSE
     */
    abstract public function get($id);

    /**
     * Save Cache Item
     *
     * @param   string  $id     Item ID
     * @param   mixed   $data   Data to store
     * @param   int     $ttl    Cache TTL (in seconds)
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function save($id, $data, $ttl);

    /**
     * Delete Cache item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function delete($id);

    /**
     * Clean Cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function clean();

    /**
     * Get Cache Info
     *
     * @param   string  $type   User/filehits
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    abstract public function cacheInfo($type);

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    abstract public function getMetadata($id);

    /**
     * Is Supported
     *
     * @return  bool    TRUE if supported, otherwise FALSE
     */
    abstract public function isSupported();
}

