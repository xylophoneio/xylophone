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
namespace Xylophone\libraries\Cache\Memcached;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Memcached Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/Memcached
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheMemcached extends \Xylophone\libraries\Cache\Cache
{
    /** @var    object  Memcached object */
    protected $memcached;

    /** @var    array   Memcached configuration */
    protected $memcache_conf = array(
        'default' => array(
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 1
        )
    );

    /**
     * Initialize
     *
     * @return  void
     */
    protected function initialize()
    {
        global $XY;

        // Try to load memcached server info from the config file.
        $config = $XY->config->get('memcached', 'config');
        if (is_array($config)) {
            $defaults = $this->memcache_conf['default'];
            $this->memcache_conf = $config;
        }

        if (class_exists('Memcached', false)) {
            $this->memcached = new Memcached();
        }
        elseif (class_exists('Memcache', false)) {
            $this->memcached = new Memcache();
        }
        else {
            $XY->logger->error('Failed to create object for Memcached Cache; extension not loaded?');
            return;
        }

        foreach ($this->memcache_conf as $server) {
            isset($server['hostname']) || $server['hostname'] = $defaults['host'];
            isset($server['port']) || $server['port'] = $defaults['host'];
            isset($server['weight']) || $server['weight'] = $defaults['weight'];

            if (get_class($this->memcached) === 'Memcache') {
                // Third parameter is persistance and defaults to TRUE.
                $this->memcached->addServer($server['hostname'], $server['port'], true, $server['weight']);
            }
            else {
                $this->memcached->addServer($server['hostname'], $server['port'], $server['weight']);
            }
        }
    }

    /**
     * Get Cache Item
     *
     * @param   string  $id     Item ID
     * @return  mixed   Value on success, otherwise FALSE
     */
    public function get($id)
    {
        $data = $this->memcached->get($id);
        return is_array($data) ? $data[0] : false;
    }

    /**
     * Save Cache Item
     *
     * @param   string  $id     Item ID
     * @param   mixed   $data   Data to store
     * @param   int     $ttl    Cache TTL (in seconds)
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function save($id, $data, $ttl = 60)
    {
        if (get_class($this->memcached) === 'Memcached') {
            return $this->memcached->set($id, array($data, time(), $ttl), $ttl);
        }
        elseif (get_class($this->memcached) === 'Memcache') {
            return $this->memcached->set($id, array($data, time(), $ttl), 0, $ttl);
        }

        return false;
    }

    /**
     * Delete Cache Item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function delete($id)
    {
        return $this->memcached->delete($id);
    }

    /**
     * Clean Cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function clean()
    {
        return $this->memcached->flush();
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   Ignored
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    public function cacheInfo($type = null)
    {
        return $this->memcached->getStats();
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    public function getMetadata($id)
    {
        $stored = $this->memcached->get($id);

        if (count($stored) !== 3) {
            return false;
        }

        list($data, $time, $ttl) = $stored;

        return array(
            'expire' => $time + $ttl,
            'mtime' => $time,
            'data' => $data
        );
    }

    /**
     * Is Supported
     *
     * @return  bool    TRUE if supported, otherwise FALSE
     */
    public function isSupported()
    {
        global $XY;

        if (!extension_loaded('memcached') && !extension_loaded('memcache')) {
            $XY->logger->debug('The Memcached Extension must be loaded to use Memcached Cache.');
            return false;
        }

        return true;
    }
}

