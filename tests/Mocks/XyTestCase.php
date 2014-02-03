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

/**
 * Xylophone Test Case Base Class
 *
 * @package     Xylophone
 */
class XyTestCase extends PHPUnit_Framework_TestCase
{
    /** @var    object  VFS root */
    protected $vfs_root;

    /** @var    object  VFS base_path root */
    protected $vfs_base_root;

    /** @var    object  VFS system_path root */
    protected $vfs_sys_root;

    /** @var    object  VFS app_path root */
    protected $vfs_app_root;

    /** @var    string  VFS base_path */
    protected $vfs_base_path;

    /** @var    string  VFS system_path */
    protected $vfs_sys_path;

    /** @var    string  VFS app_path */
    protected $vfs_app_path;

    /**
     * Test Case Setup
     *
     * @return  void
     */
    public function setUp()
    {
        // Setup VFS with base directories
        $this->vfs_root = vfsStream::setup();
        $this->vfs_base_root = $this->vfsMkdir('xylophone');
        $this->vfs_sys_root = $this->vfsMkdir('system', $this->vfs_base_root);
        $this->vfs_app_root = $this->vfsMkdir('application', $this->vfs_base_root);

        // Add app subdirs
        $this->vfsMkdir('config', $this->vfs_app_root);
        $this->vfsMkdir('views', $this->vfs_app_root);

        // Set root path strings
        $this->vfs_base_path = $this->vfsPath('xylophone');
        $this->vfs_sys_path = $this->vfsPath('xylophone/system');
        $this->vfs_app_path = $this->vfsPath('xylophone/application');

        // Run test setup if present
        method_exists($this, 'xySetUp') && $this->xySetUp();
    }

    /**
     * Test Case Teardown
     *
     * @return  void
     */
    public function tearDown()
    {
        // Run test teardown if present
        method_exists($this, 'xyTearDown') && $this->xyTearDown();
    }

    /**
     * Create VFS directory
     *
     * @param	string	$name   Directory name
     * @param	object	$root   Optional root to create in
     * @return	object	New directory object
     */
    public function vfsMkdir($name, $root = null)
    {
        // Check for root
        $root || $root = $this->vfs_root;

        // Break out path
        $path = explode('/', trim($name, '/'));
        $name = array_pop($path);

        // Handle any subdirectories
        while (($dir = array_shift($path))) {
            // See if subdir exists under current root
            $dir_root = $root->getChild($dir);
            if ($dir_root) {
                // Yes - recurse into subdir
                $root = $dir_root;
            }
            else {
                // No - recurse into new subdir
                $root = vfsStream::newDirectory($dir)->at($root);
            }
        }

        // Return final new directory object
        return vfsStream::newDirectory($name)->at($root);
    }

    /**
     * Create VFS content
     *
     * @param	string	$file       File name
     * @param	string	$content    File content
     * @param	object	$root       VFS directory object
     * @param	mixed	$path       Optional subdirectory path or array of subs
     * @return	void
     */
    public function vfsCreate($file, $content = '', $root = null, $path = null)
    {
        // Check for array
        if (is_array($file)) {
            foreach ($file as $name => $content) {
                $this->vfsCreate($name, $content, $root, $path);
            }
            return;
        }

        // Assert .php extension if none given
        if (pathinfo($file, PATHINFO_EXTENSION) == '') {
            $file .= '.php';
        }

        // Build content
        $tree = array($file => $content);

        // Check for path
        $subs = array();
        if ($path) {
            // Explode if not array
            $subs = is_array($path) ? $path : explode('/', trim($path, '/'));
        }

        // Use base VFS root if not provided
        $root || $root = $this->vfs_root;

        // Handle subdirectories
        while (($dir = array_shift($subs))) {
            // See if subdir exists under current root
            $dir_root = $root->getChild($dir);
            if ($dir_root) {
                // Yes - recurse into subdir
                $root = $dir_root;
            }
            else {
                // No - put subdirectory back and quit
                array_unshift($subs, $dir);
                break;
            }
        }

        // Create any remaining subdirectories
        if ($subs) {
            foreach (array_reverse($subs) as $dir) {
                // Wrap content in subdirectory for creation
                $tree = array($dir => $tree);
            }
        }

        // Create tree
        vfsStream::create($tree, $root);
    }

    /**
     * Clone a real file into VFS
     *
     * @param	string	$path   Path from base directory
     * @return	bool	TRUE on success, otherwise FALSE
     */
    public function vfsClone($path)
    {
        // Check for array
        if (is_array($path)) {
            foreach ($path as $file) {
                $this->vfsClone($file);
            }
            return;
        }

        // Get real file contents
        $content = file_get_contents(PROJECT_BASE.$path);
        if ($content === false) {
            // Couldn't find file to clone
            return false;
        }

        $this->vfsCreate(basename($path), $content, null, dirname($path));
        return true;
    }

    /**
     * Helper to get a VFS URL path
     *
     * @param	string	$path   Path
     * @param	string	$bae    Optional base path
     * @return	string	Path URL
     */
    public function vfsPath($path, $base = '')
    {
        // Check for base path
        if ($base) {
            // Prepend to path
            $path = rtrim($base, '/').'/'.ltrim($path, '/');

            // Is it already in URL form?
            if (strpos($path, '://') !== false) {
                // Done - return path
                return $path;
            }
        }

        // Trim leading slash and return URL
        return vfsStream::url(ltrim($path, '/'));
    }
}

