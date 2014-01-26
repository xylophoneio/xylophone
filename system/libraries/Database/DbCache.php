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
namespace Xylophone\libraries\Database;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Cache Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database
 * @link        http://xylophone.io/user_guide/database/
 */
class DBCache
{
    /** @var    object  Database object to support multiple databases */
    public $db;

    /**
     * Constructor
     *
     * @param   object  $db     Database object
     * @return  void
     */
    public function __construct($db)
    {
        global $XY;

        // Load the file library since we use it a lot
        $this->db = $db;
        $XY->load->library('file');
        $this->checkPath();
    }

    /**
     * Set Cache Directory Path
     *
     * @param   string  $path   Path to the cache directory
     * @return  bool
     */
    public function checkPath($path = '')
    {
        global $XY;

        if ($path === '') {
            if ($this->db->cachedir === '') {
                return $this->db->cacheOff();
            }

            $path = $this->db->cachedir;
        }

        // Add a trailing slash to the path if needed
        $rpath = realpath($path);
        $path = rtrim($rpath ? $rpath : $path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            // If the path is wrong we'll turn off caching
            $XY->logger->debug('DB cache path error: '.$path);
            return $this->db->cacheOff();
        }

        if (!$XY->isWritable($path)) {
            // If the path is not really writable we'll turn off caching
            $XY->logger->debug('DB cache dir not writable: '.$path);
            return $this->db->cacheOff();
        }

        $this->db->cachedir = $path;
        return true;
    }

    /**
     * Retrieve a cached query
     *
     * The URI being requested will become the name of the cache sub-folder.
     * An MD5 hash of the SQL statement will become the cache file name.
     *
     * @param   string  $sql    Query string
     * @return  string
     */
    public function read($sql)
    {
        global $XY;

        $segment_one = $XY->uri->segment(1);
        $segment_one === false && $segment_one = 'default';
        $segment_two = $XY->uri->segment(2);
        $segment_two === false && $segment_two = 'index';
        $filepath = $this->db->cachedir.$segment_one.'+'.$segment_two.'/'.md5($sql);

        $cachedata = @file_get_contents($filepath);
        if ($cachedata === false) {
            return false;
        }

        return unserialize($cachedata);
    }

    /**
     * Write a query to a cache file
     *
     * @param   string  $sql    Query string
     * @param   object  $object Query data
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function write($sql, $object)
    {
        global $XY;

        $segment_one = $XY->uri->segment(1);
        $segment_one === false && $segment_one = 'default';
        $segment_two = $XY->uri->segment(2);
        $segment_two === false && $segment_two = 'index';
        $dir_path = $this->db->cachedir.$segment_one.'+'.$segment_two.'/';
        $filename = md5($sql);

        if (!@is_dir($dir_path)) {
            if (!@mkdir($dir_path, DIR_WRITE_MODE)) {
                return false;
            }

            @chmod($dir_path, DIR_WRITE_MODE);
        }

        if ($XY->file->writeFile($dir_path.$filename, serialize($object)) === false) {
            return false;
        }

        @chmod($dir_path.$filename, FILE_WRITE_MODE);
        return true;
    }

    /**
     * Delete cache files within a particular directory
     *
     * @param   string  $segment_one    First URL segment
     * @param   string  $segment_two    Second URL segment
     * @return  void
     */
    public function delete($segment_one = '', $segment_two = '')
    {
        global $XY;

        $segment_one === '' && $segment_one = $XY->uri->segment(1);
        $segment_one === false && $segment_one = 'default';
        $segment_two == '' && $segment_two = $XY->uri->segment(2);
        $segment_two === false && $segment_two = 'index';
        $dir_path = $this->db->cachedir.$segment_one.'+'.$segment_two.'/';
        $XY->file->deleteFiles($dir_path, true);
    }

    /**
     * Delete all existing cache files
     *
     * @return  void
     */
    public function deleteAll()
    {
        global $XY;
        $XY->file->deleteFiles($this->db->cachedir, true, true);
    }
}

