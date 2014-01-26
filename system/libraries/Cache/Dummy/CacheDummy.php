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
namespace Xylophone\libraries\Cache\Dummy;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone Dummy Caching Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Cache/Dummy
 * @link        http://xylophone.io/user_guide/libraries/caching.html
 */
class CacheDummy extends \Xylophone\libraries\Cache\Cache
{
    /**
     * Get Cache Item
     *
     * Since this is the dummy class, it's always going to return FALSE.
     *
     * @param   string  $id     Item ID
     * @return  bool    FALSE
     */
    public function get($id)
    {
        return false;
    }

    /**
     * Save Cache Item
     *
     * @param   string  $id     Item ID
     * @param   mixed   $data   Data to store
     * @param   int     $ttl    Cache TTL (in seconds)
     * @return  bool    TRUE, simulating success
     */
    public function save($id, $data, $ttl = 60)
    {
        return true;
    }

    /**
     * Delete Cache Item
     *
     * @param   string  $id     Item ID
     * @return  bool    TRUE, simulating success
     */
    public function delete($id)
    {
        return true;
    }

    /**
     * Clean Cache
     *
     * @return  bool    TRUE, simulating success
     */
    public function clean()
    {
        return true;
    }

    /**
     * Get Cache Info
     *
     * @param   string  $type   User/filehits
     * @return  bool    FALSE
     */
    public function cacheInfo($type = null)
    {
        return false;
    }

    /**
     * Get Cache Metadata
     *
     * @param   string  $id     Item ID
     * @return  bool    FALSE
     */
    public function getMetadata($id)
    {
        return false;
    }

    /**
     * Is Supported
     *
     * Always.
     *
     * @return  bool    TRUE
     */
    public function isSupported()
    {
        return true;
    }
}

