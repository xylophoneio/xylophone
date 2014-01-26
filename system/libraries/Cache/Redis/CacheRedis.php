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
namespace Xylophone\libraries\Cache\Redis;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Redis Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/Redis
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheRedis extends \Xylophone\libraries\Cache\Cache
{
    /** @var    array   Configuration items */
    protected $config = array(
        'socket_type' => 'tcp',
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 0
    );

    /** @var    Redis   Redis connection */
    protected $redis;

    /**
     * Initialize
     *
     * Loads Redis config file if present. Will halt execution
     * if a Redis connection can't be established.
     *
     * @return  void
     */
    protected function initialize()
    {
        global $XY;

        $config = $XY->config->get('redis', 'config');
        is_array($config) && $this->config = array_merge($this->config, $config);

        $this->redis = new Redis();

        try {
            if ($config['socket_type'] === 'unix') {
                // Unix socket
                $success = $this->redis->connect($config['socket']);
            }
            else {
                // TCP socket
                $success = $this->redis->connect($config['host'], $config['port'], $config['timeout']);
            }

            if (!$success) {
                $XY->logger->debug('Cache: Redis connection refused. Check the config.');
                return;
            }
        }
        catch (RedisException $e) {
            $XY->logger->debug('Cache: Redis connection refused ('.$e->getMessage().')');
            return;
        }

        isset($config['password']) && $this->redis->auth($config['password']);
    }

    /**
     * Destructor
     *
     * Closes the connection to Redis if present.
     *
     * @return  void
     */
    public function __destruct()
    {
        $this->redis && $this->redis->close();
    }

    /**
     * Get Cache Item
     *
     * @param   string  $id     Item ID
     * @return  mixed   Value on success, otherwise FALSE
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * Save Cache Item
     *
     * @param   string  $id     Item ID
     * @param   mixed   $data   Data to store
     * @param   int     $ttl    Cache TTL (in seconds)
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function save($key, $value, $ttl = null)
    {
        return $ttl ? $this->redis->setex($key, $ttl, $value) : $this->redis->set($key, $value);
    }

    /**
     * Delete Cache Item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function delete($key)
    {
        return ($this->redis->delete($key) === 1);
    }

    /**
     * Clean cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function clean()
    {
        return $this->redis->flushDB();
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   Ignored
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    public function cacheInfo($type = null)
    {
        return $this->redis->info();
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    public function getMetadata($key)
    {
        $value = $this->get($key);
        if ($value) {
            return array('expire' => time() + $this->redis->ttl($key), 'data' => $value);
        }

        return false;
    }

    /**
     * Is Supported
     *
     * @return  bool    TRUE if supported, otherwise FALSE
     */
    public function isSupported()
    {
        global $XY;

        if (!extension_loaded('redis')) { {
            $XY->logger->debug('The Redis extension must be loaded to use Redis cache.');
            return false;
        }

        return true;
    }
}

