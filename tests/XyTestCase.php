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
    protected $vfs_root = null;

    /** @var    object  VFS base_path directory object */
    protected $vfs_base_dir = null;

    /** @var    object  VFS system_path directory object */
    protected $vfs_sys_dir = null;

    /** @var    object  VFS app_path directory object */
    protected $vfs_app_dir = null;

    /** @var    string  VFS base_path */
    protected $vfs_base_path = '';

    /** @var    string  VFS system_path */
    protected $vfs_sys_path = '';

    /** @var    string  VFS app_path */
    protected $vfs_app_path = '';

    /** @var    bool    Autoloader registered flag */
    protected $spl_registered = false;

    /**
     * Test Case Setup
     *
     * @return  void
     */
    public function setUp()
    {
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

        // Unregister autoloader as needed
        $this->spl_registered && spl_autoload_unregister(array($this, 'autoloader'));
    }

    /**
     * Initialize VFS
     *
     * Creates a virtual file system with vfsStream with the following structure:
     *  root
     *  \- xylophone        (base_path)
     *    |- system         (system_path)
     *    \- application    (app_path)
     *      |- config
     *      \- views
     *
     * vfsStream directory objects and file paths are stored in the vfs_*
     * member variables.
     *
     * @return  void
     */
    protected function vfsInit()
    {
        // Setup VFS with base directories
        $this->vfs_root = vfsStream::setup();
        $this->vfs_base_dir = $this->vfsMkdir('xylophone');
        $this->vfs_sys_dir = $this->vfsMkdir('system', $this->vfs_base_dir);
        $this->vfs_app_dir = $this->vfsMkdir('application', $this->vfs_base_dir);

        // Add app subdirs
        $this->vfsMkdir('config', $this->vfs_app_dir);
        $this->vfsMkdir('views', $this->vfs_app_dir);

        // Set root path strings
        $this->vfs_base_path = $this->vfs_base_dir->url();
        $this->vfs_sys_path = $this->vfs_sys_dir->url();
        $this->vfs_app_path = $this->vfs_app_dir->url();
    }

    /**
     * Create VFS directory
     *
     * Creates a new directory in the VFS and returns the new directory object.
     *
     * @param	mixed 	$path   Relative directory path or array of paths
     * @param	object	$dir    Base VFS directory object
     * @param	int  	$perms  Directory permissions
     * @return	object	New directory object
     */
    protected function vfsMkdir($path, $dir = null, $perms = null)
    {
        // Create empty directory
        return $this->vfsCreate($path, array(), $dir, $perms);
    }

    /**
     * Create VFS content
     *
     * Creates a new file in the VFS and returns the new file object.
     *
     * @param	mixed 	$file       File path or array of file paths
     * @param	string	$content    File content
     * @param	object	$dir        Base VFS directory object
     * @param	int  	$perms      File permissions
     * @return	mixed   File object or array of objects
     */
    protected function vfsCreate($file, $content = '', $dir = null, $perms = null)
    {
        // Check for array
        if (is_array($file)) {
            $retval = array();
            foreach ($file as $name => $content) {
                $retval[] = $this->vfsCreate($name, $content, $dir, $perms);
            }
            return $retval;
        }

        // Use base VFS dir if not provided
        $dir || $dir = $this->vfs_root;

        // Break out path
        $path = explode('/', trim($file, '/'));
        $file = array_pop($path);

        // Handle any subdirectories
        while (($dirnm = array_shift($path))) {
            // Get or create subdirectory
            $obj = $dir->getChild($dirnm);
            $dir = $obj ? $obj : vfsStream::newDirectory($dirnm)->at($dir);
        }

        // Create file
        if (is_string($content)) {
            return vfsStream::newFile($file, $perms)->withContent($content)->at($dir);
        }

        // Create directory
        $obj = vfsStream::newDirectory($file, $perms)->at($dir);
        is_array($content) && !empty($content) && vfsStream::create($content, $obj);
        return $obj;
    }

    /**
     * Clone a real file into VFS
     *
     * @param	mixed 	$path   File path string or array of paths
     * @return	mixed   File object or array of objects on success, otherwsie FALSE
     */
    protected function vfsClone($path)
    {
        // Check for array
        if (is_array($path)) {
            $retval = array();
            foreach ($path as $file) {
                $obj = $this->vfsClone($file);
                if ($obj === false) {
                    return false;
                }
                $retval[] = $obj;
            }
            return $retval;
        }

        // Check for directory
        if (is_dir(BASEPATH.$path)) {
            // Make empty directory
            $content = array();
        }
        else {
            // Get real file contents
            $content = file_get_contents(BASEPATH.$path);
            if ($content === false) {
                // Couldn't find file to clone
                return false;
            }
        }

        // Get permissions
        $perms = fileperms(BASEPATH.$path);

        // Create content
        return $this->vfsCreate($path, $content, null, $perms);
    }

    /**
     * Helper to get a VFS URL path
     *
     * @param	string	$path   Path
     * @param	string	$base   Base path
     * @return	string	Path URL
     */
    protected function vfsPath($path, $base = '')
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

    /**
     * Make a test class
     *
     * Declares a class with a single method for ease of testing. Method may
     * accept parameters - each of which will be set to an object member name;
     * echo a string as output; and/or throw an exception. The test function
     * can then test the member variables, output, and thrown exceptions.
     *
     * @param   string  $class      Class name
     * @param   string  $method     Method name
     * @param   array   $members    Array of members/parameters
     * @param   string  $ns         Namespace
     * @param   string  $echo       String to echo
     * @param   string  $throw      Exception name to throw
     * @return  void
     */
    protected function makeClass($class, $method, $members = array(), $ns = '', $echo = '', $throw = '')
    {
        // Wrap namespace, echo, and throw calls if present
        $ns && $ns = 'namespace '.$ns.'; ';
        $echo && $echo = ' echo "'.$echo.'";';
        $throw && $throw = ' throw new '.$throw.';';

        // Assemble member/parameters
        $ct = 0;
        $memdec = '';
        $memset = '';
        $params = array();
        empty($members) && !is_array($members) && $members = array();
        foreach((array)$members as $member) {
            $param = '$param'.(++$ct);
            $memdec .= ' public $'.$member.';';
            $memset .= ' $this->'.$member.' = '.$param.';';
            $params[] = $param;
        }
        $params = implode(', ', $params);

        // Run class definition
        eval($ns.'class '.$class.' {'.$memdec.
            ' public function '.$method.'('.$params.') {'.$memset.$echo.$throw.' } }');
    }

    /**
     * Register system class autoloader
     *
     * Registers the autoloader for getting Xylophone system classes.
     * Autoloader is automatically unregistered during teardown.
     *
     * @return  void
     */
    protected function registerAutoloader()
    {
        // Redundancy check
        if (!$this->spl_registered) {
            // Register the autoloader and flag for unregistering
            spl_autoload_register(array($this, 'autoloader'));
            $this->spl_registered = true;
        }
    }

    /**
     * Autoload Xylophone system classes
     *
     * @param   string  $class  Class name
     * @return  void
     */
    protected function autoloader($class)
    {
        // Break out namespace and class
        $parts = explode('\\', trim($class, '\\'));
        if ($parts[0] == 'Xylophone') {
            // Remove system namespace
            array_shift($parts);

            // Include reassembled namespace as path to source file
            include_once BASEPATH.'system/'.implode('/', $parts).'.php';
        }
    }
}

