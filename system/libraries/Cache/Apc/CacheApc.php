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
namespace Xylophone\libraries\Cache\Apc;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone APC Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/Apc
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheApc extends \Xylophone\libraries\Cache\Cache
{
    /**
     * Get Cache Item
     *
     * @param   string  $id     Item ID
     * @return  mixed   Value on success, otherwise FALSE
     */
    public function get($id)
    {
        $success = false;
        $data = apc_fetch($id, $success);
        return ($success === true && is_array($data)) ? unserialize($data[0]) : false;
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
        $ttl = (int)$ttl;
        return apc_store($id, array(serialize($data), time(), $ttl), $ttl);
    }

    /**
     * Delete Cache Item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function delete($id)
    {
        return apc_delete($id);
    }

    /**
     * Clean cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function clean()
    {
        return apc_clear_cache('user');
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   User/filehits
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    public function cacheInfo($type = null)
    {
        return apc_cache_info($type);
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    public function getMetadata($id)
    {
        $success = FALSE;
        $stored = apc_fetch($id, $success);

        if ($success === FALSE OR count($stored) !== 3)
        {
            return FALSE;
        }

        list($data, $time, $ttl) = $stored;

        return array(
                'expire'	=> $time + $ttl,
                'mtime'		=> $time,
                'data'		=> unserialize($data)
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

        if (!extension_loaded('apc') || !(bool)@ini_get('apc.enabled')) {
            $XY->logger->debug('The APC PHP extension must be loaded to use APC Cache.');
            return false;
        }

        return true;
    }
}

