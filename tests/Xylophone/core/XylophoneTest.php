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
 * Xylophone Unit Test
 *
 * @package     Xylophone
 */
class XylophoneTest extends XyTestCase
{
    /** @var    string  Include directory path */
    private $inc_path;

    /** @var    object  Shared directory root */
    private $share_root;

    /** @var    string  Shared directory path */
    private $share_path;

    /** @var    string  Shared directory name */
    private $share_dir;

    /** @var    string  Shared views directory name */
    private $sh_view_dir;

    /**
     * Per-test setup
     *
     * @return  void
     */
    public function xySetUp()
    {
        // We can't instantiate externally to trigger autoloader, so
        // manually include source file and mock source
        include_once BASEPATH.'system/core/Xylophone.php';
        include_once TESTPATH.'Mocks/core/Xylophone.php';

        // Default to skipping handlers
        Mocks\core\Xylophone::$skip_handlers = true;

        // Create an include dir with a shared module subdir
        $incdir = 'usr/share/php/';
        $this->share_dir = 'xylophone/';
        $this->share_root = $this->vfsMkdir($incdir.$this->share_dir);

        // Make shared config and view subdirs
        $this->vfsMkdir('config', $this->share_root);
        $viewdir = 'sharedviews/';
        $this->sh_view_dir = $this->share_dir.$viewdir;
        $this->vfsMkdir($viewdir, $this->share_root);

        // Set include and share paths
        $this->inc_path = $this->vfsPath($incdir);
        $this->share_path = $this->vfsPath($incdir.$this->share_dir);
    }

    /**
     * Test __construct()
     *
     * @return  void
     */
    public function testConstruct()
    {
        // Make sure __construct is not callable
        $this->assertFalse(is_callable(array('Xylophone\core\Xylophone', '__construct')));
    }

    /**
     * Test instance()
     *
     * @return  object  Mock Xylophone instance
     */
    public function testInstance()
    {
        // Call instance to get a new instance, skipping initialize()
        // We pass the Mocks namespace so our mock overload gets used
        // This will be the instance for the rest of the tests below
        Mocks\core\Xylophone::$skip_init = true;
        $init = array('ns_paths' => array('Mocks' => TESTPATH.'Mocks/'));
        $XY = Mocks\core\Xylophone::instance($init);
        $this->assertInstanceOf('Xylophone\core\Xylophone', $XY);

        // Call again and check for same object
        $XY2 = Mocks\core\Xylophone::instance();
        $this->assertEquals($XY, $XY2);

        // Return our instance for use later
        return $XY;
    }

    /**
     * Test initialize() defaults
     *
     * @depends testInstance
     * @return  object  Mock Xylophone instance
     */
    public function testInitDefault($XY)
    {
        // Call initialize with empty parameters
        Mocks\core\Xylophone::$skip_init = false;
        $XY->initialize(array());

        // Check environment and base and system paths
        $this->assertEquals('', $XY->environment);
        $this->assertEquals(BASEPATH, $XY->base_path);
        $this->assertEquals(BASEPATH.'system/', $XY->system_path);

        // Check namespace and app paths
        $app_path = $XY->base_path.'application'.DIRECTORY_SEPARATOR;
        $this->assertEquals(array('' => $app_path), $XY->ns_paths);
        $this->assertEquals('', $XY->app_ns);
        $this->assertEquals($app_path, $XY->app_path);

        // Check config and view paths
        $this->assertEquals(array($app_path), $XY->config_paths);
        $this->assertEquals(array('' => $app_path.'views/'), $XY->view_paths);

        // Check override flags
        $this->assertFalse($XY->override_core);
        $this->assertFalse($XY->library_search);

        // Return instance for reinitialization
        return $XY;
    }

    /**
     * Test initialize()
     *
     * @depends testInitDefault
     * @return  object  Mock Xylophone instance
     */
    public function testInitialize($XY)
    {
        // Set up test parameter vars
        $env = 'development';
        $sysdir = $this->vfs_sys_path.'/';
        $appdir = $this->vfs_app_path.'/';
        $appns = '';
        $nspath = array($appns => $appdir, 'XyShare' => $this->share_path, 'Mocks' => TESTPATH.'Mocks/');
        $appvwnm = '';

        // Call initialize with parameters
        $init = array(
            'environment' => $env,
            'system_path' => $sysdir,
            'resolve_bases' => array('', $this->inc_path),
            'ns_paths' => $nspath,
            'view_paths' => array($appvwnm, $this->sh_view_dir),
            'override_core' => true,
            'library_search' => true
        );
        $XY->initialize($init);

        // Check environment and system paths
        $this->assertEquals($env, $XY->environment);
        $this->assertEquals($sysdir, $XY->system_path);

        // Check namespace and app paths
        $this->assertEquals($nspath, $XY->ns_paths);
        $this->assertEquals($appns, $XY->app_ns);
        $this->assertEquals($appdir, $XY->app_path);

        // Check config and view paths
        $this->assertEquals($XY->config_paths, array($this->share_path, $appdir));
        $this->assertEquals(array(
            $appvwnm => $appdir.'views/',
            $this->sh_view_dir => $this->inc_path.$this->sh_view_dir
        ), $XY->view_paths);

        // Check override flags
        $this->assertTrue($XY->override_core);
        $this->assertTrue($XY->library_search);

        // Return instance for post-init testing
        return $XY;
    }

    /**
     * Test adding an empty namespace
     *
     * @depends testInitialize
     */
    public function testAddGlobalNamespace($XY)
    {
        // Check for global namespace add failure
        $this->assertArrayHasKey('', $XY->ns_paths);
        $this->assertFalse($XY->addNamespace('', 'some/path'));
    }
}

