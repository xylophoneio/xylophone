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
namespace Xylophone\libraries\Cache\Wincache;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Wincache Caching Class
 *
 * Read more about Wincache functions here:
 * http://www.php.net/manual/en/ref.wincache.php
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/Wincache
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheWincache extends \Xylophone\libraries\Cache\Cache
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
        $data = wincache_ucache_get($id, $success);

        // Success returned by reference from wincache_ucache_get()
        return $success ? $data : false;
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
        return wincache_ucache_set($id, $data, $ttl);
    }

    /**
     * Delete Cache Item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function delete($id)
    {
        return wincache_ucache_delete($id);
    }

    /**
     * Clean Cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function clean()
    {
        return wincache_ucache_clear();
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   Ignored
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    public function cacheInfo($type = null)
    {
        return wincache_ucache_info(true);
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    public function getMetadata($id)
    {
        if ($stored = wincache_ucache_info(false, $id)) {
            $age = $stored['ucache_entries'][1]['age_seconds'];
            $ttl = $stored['ucache_entries'][1]['ttl_seconds'];
            $hitcount = $stored['ucache_entries'][1]['hitcount'];

            return array(
                'expire' => $ttl - $age,
                'hitcount' => $hitcount,
                'age' => $age,
                'ttl' => $ttl
            );
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

        if (!extension_loaded('wincache')) {
            $XY->logger->debug('The Wincache PHP extension must be loaded to use Wincache Cache.');
            return false;
        }

        return true;
    }
}

