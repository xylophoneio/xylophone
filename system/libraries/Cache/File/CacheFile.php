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
namespace Xylophone\libraries\Cache\File;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone File Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/File
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheFile extends \Xylophone\libraries\Cache\Cache
{
    /**
     * Initialize file-based cache
     *
     * @return  void
     */
    protected function initialize()
    {
        global $XY;
        $XY->load->library('file');
        $this->cache_path || $this->cache_path = $XY->app_path.'cache/';
    }

    /**
     * Get Cache Item
     *
     * @param   string  $id     Item ID
     * @return  mixed   Value on success, otherwise FALSE
     */
    public function get($id)
    {
        if (!file_exists($this->cache_path.$id)) {
            return false;
        }

        $data = unserialize(file_get_contents($this->cache_path.$id));

        if ($data['ttl'] > 0 && time() > $data['time'] + $data['ttl']) {
            unlink($this->cache_path.$id);
            return false;
        }

        return $data['data'];
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
        $contents = array(
            'time' => time(),
            'ttl' => $ttl,
            'data' => $data
        );

        if (write_file($this->cache_path.$id, serialize($contents))) {
            @chmod($this->cache_path.$id, 0660);
            return true;
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
        return file_exists($this->cache_path.$id) ? unlink($this->cache_path.$id) : false;
    }

    /**
     * Clean Cache
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function clean()
    {
        return delete_files($this->cache_path, false, true);
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   User/filehits
     * @return  mixed   Cache info array on success, otherwise FALSE
     */
    public function cacheInfo($type = null)
    {
        return get_dir_file_info($this->cache_path);
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  mixed   Cache item metadata
     */
    public function getMetadata($id)
    {
        if (!file_exists($this->cache_path.$id)) {
            return false;
        }

        $data = unserialize(file_get_contents($this->cache_path.$id));

        if (is_array($data)) {
            $mtime = filemtime($this->cache_path.$id);

            if (!isset($data['ttl'])) {
                return false;
            }

            return array('expire' => $mtime + $data['ttl'], 'mtime'	 => $mtime);
        }

        return false;
    }

    /**
     * Is supported
     *
     * In the file driver, check to see that the cache directory is indeed writable
     *
     * @return  bool    TRUE if supported, otherwise FALSE
     */
    public function isSupported()
    {
        return is_really_writable($this->cache_path);
    }
}

