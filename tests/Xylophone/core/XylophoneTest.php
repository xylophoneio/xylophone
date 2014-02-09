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
 * Sets up a virtual filesystem for testing the Xylophone framework singleton
 * and its base services as follows:
 *  root
 *  |- xylophone        (base_path)
 *  | |- system         (system_path)
 *  | \- application    (app_path)
 *  |   |- config
 *  |   \- views
 *  |
 *  \- usr
 *    \- share
 *      \- php
 *        \- xylophone
 *          \- config
 *
 * Most tests rely (sometimes indirectly) on the Xylophone instance set up in
 * testTune(), which sets the following parameters:
 *  environment     development
 *  base_path       vfs_base_path/
 *  system_path     vfs_sys_path/
 *  ns_paths        [ '' => vfs_app_path/, share_ns => vfs_share_path/, Mocks => TESTPATH/Mocks/ ]
 *  app_ns          ''
 *  app_path        vfs_app_path/
 *  config_paths    [ share_path/, vfs_app_path/ ]
 *  view_paths      [ views/ => vfs_app_path/views/ ]
 *  override_core   FALSE
 *  library_search  TRUE
 *
 * @package     Xylophone
 */
class XylophoneTest extends XyTestCase
{
    /** @var    string  Include directory root */
    private $inc_dir;

    /** @var    string  Include directory path */
    private $inc_path;

    /** @var    object  Shared directory root */
    private $share_dir;

    /** @var    string  Shared directory path */
    private $share_path;

    /** @var    string  Shared namespace */
    private $share_ns;

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

        // Create an include dir with a shared module subdir having a config dir
        $this->inc_dir = $this->vfsMkdir('usr/share/php');
        $this->share_dir = $this->vfsMkdir('xylophone', $this->inc_dir);
        $this->vfsMkdir('config', $this->share_dir);

        // Set include and share paths
        $this->inc_path = $this->inc_dir->url();
        $this->share_path = $this->share_dir->url();
        $this->share_ns = 'XyShare';
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
     * Test instance() with a bad application path
     */
    public function testInstanceAppFail()
    {
        // Call instance with a bad app path
        $init = array('environment' => 'testing', 'ns_paths' => array('' => 'badpath/'));
        $this->setExpectedException('Xylophone\core\ExitException',
            'Your application folder path does not appear to be set correctly.'.
            ' Please fix it in the following file: '.basename($_SERVER['PHP_SELF']), EXIT_XY);
        $XY = Mocks\core\Xylophone::instance($init);
    }

    /**
     * Test instance() with a bad namespace
     */
    public function testInstanceNsFail()
    {
        // Call instance with a bad (missing) namespace
        $init = array('environment' => 'production', 'ns_paths' => array('' => $this->vfs_app_path, 'badpath/'));
        $this->setExpectedException('Xylophone\core\ExitException',
            'The global namespace is reserved for application classes. '.
            'Please specify a namespace for your additional path in the following file: '.
            basename($_SERVER['PHP_SELF']), EXIT_XY);
        $XY = Mocks\core\Xylophone::instance($init);
    }

    /**
     * Test instance() with a bad namespace path
     */
    public function testInstanceNsPathFail()
    {
        // Call instance with a bad ns path
        $ns = 'BadSpace';
        $init = array('environment' => 'production', 'ns_paths' => array('' => $this->vfs_app_path, $ns => 'badpath/'));
        $this->setExpectedException('Xylophone\core\ExitException',
            'The "'.$ns.'" namespace path does not appear to be set correctly.'.
            ' Please fix it in the following file: '.basename($_SERVER['PHP_SELF']), EXIT_XY);
        $XY = Mocks\core\Xylophone::instance($init);
    }

    /**
     * Test instance()
     *
     * @return  object  Mock Xylophone instance
     */
    public function testInstance()
    {
        // Call instance to get a new instance, skipping tune()
        // We pass the Mocks namespace so our mock overload gets used
        // This will be the instance for the rest of the tests below
        Mocks\core\Xylophone::$skip_init = true;
        $init = array('ns_paths' => array('Mocks' => TESTPATH.'Mocks/'));
        $XY = Mocks\core\Xylophone::instance($init);
        $this->assertInstanceOf('Xylophone\core\Xylophone', $XY);

        // Call again and check for same object
        $XY2 = Mocks\core\Xylophone::instance();
        $this->assertEquals($XY, $XY2);

        // Say we're not on 5.4 - some things are easier that way
        $XY->is_php['5.4'] = false;

        // Return our instance for use later
        return $XY;
    }

    /**
     * Test tune() defaults
     *
     * @depends testInstance
     * @return  object  Mock Xylophone instance
     */
    public function testInitDefault($XY)
    {
        // Call tune with empty parameters
        Mocks\core\Xylophone::$skip_init = false;
        $XY->tune(array());

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
     * Test tune()
     *
     * @depends testInitDefault
     * @return  object  Mock Xylophone instance
     */
    public function testTune($XY)
    {
        // Set up test parameter vars
        $env = 'development';
        $basedir = $this->vfs_base_path.'/';
        $sysdir = $this->vfs_sys_path.'/';
        $appdir = $this->vfs_app_path.'/';
        $shdir = $this->share_path.'/';
        $appns = '';
        $nspath = array($appns => $appdir, $this->share_ns => $shdir, 'Mocks' => TESTPATH.'Mocks/');
        $appvwnm = 'views/';

        // Call tune with parameters
        $init = array(
            'environment' => $env,
            'base_path' => $basedir,
            'system_path' => $sysdir,
            'resolve_bases' => array('', $this->inc_path.'/'),
            'ns_paths' => $nspath,
            'view_paths' => array($appvwnm),
            'override_core' => false,
            'library_search' => true
        );
        $XY->tune($init);

        // Check environment, and base and system paths
        $this->assertEquals($env, $XY->environment);
        $this->assertEquals($basedir, $XY->base_path);
        $this->assertEquals($sysdir, $XY->system_path);

        // Check namespace and app paths
        $this->assertEquals($nspath, $XY->ns_paths);
        $this->assertEquals($appns, $XY->app_ns);
        $this->assertEquals($appdir, $XY->app_path);

        // Check config and view paths
        $this->assertEquals($XY->config_paths, array($shdir, $appdir));
        $this->assertEquals(array($appvwnm => $appdir.$appvwnm), $XY->view_paths);

        // Check override flags
        $this->assertFalse($XY->override_core);
        $this->assertTrue($XY->library_search);

        // Return instance for post-init testing
        return $XY;
    }

    /**
     * Test autoloader() fail
     *
     * @depends testTune
     */
    public function testAutoloaderFail($XY)
    {
        // Try to load a non-existent class
        $class = 'Xylophone\core\BadClass';
        $this->setExpectedException('\Xylophone\core\AutoloadException', 'Could not find class "'.$class.'"');
        $XY->autoloader($class);
    }

    /**
     * Test autoloader()
     *
     * @depends testTune
     */
    public function testAutoloader($XY)
    {
        // Create dummy class file
        $dir = 'libraries';
        $file = 'TestLib';
        $output = 'Loaded test library';
        $this->vfsCreate($dir.'/'.$file.'.php', '<?php echo \''.$output.'\';', $this->share_dir);

        // Load the file
        $this->expectOutputString($output);
        $XY->autoloader($this->share_ns.'\\'.$dir.'\\'.$file);
    }

    /**
     * Test autoloader() with a hint
     *
     * @depends testTune
     */
    public function testAutoloaderHint($XY)
    {
        // Create dummy class file
        $dir = 'core';
        $file = 'Hint';
        $output = 'Loaded file with hint';
        $this->vfsCreate($dir.'/'.$file.'.php', '<?php echo \''.$output.'\';', $this->vfs_app_dir);

        // Set hint and load the file
        $this->expectOutputString($output);
        $XY->loader_hint = $dir;
        $XY->autoloader($file);
    }

    /**
     * Test loadClass() fail
     *
     * @depends testTune
     */
    public function testLoadClassFail($XY)
    {
        // Make a class that emulates the autoloader not-found behavior
        $hint = 'core';
        $class = 'MissingClass';
        $content = '<?php namespace '.$XY->system_ns.'\\'.$hint.'; class '.$class.' { '.
            'public function __construct() { throw new AutoloadException(); } }';
        include_once $this->vfsCreate($class.'.php', $content)->url();

        // Check for NULL and hint
        $this->assertNull($XY->loadClass($class, $hint));
    }

    /**
     * Test loadClass() with a global class
     *
     * @depends testTune
     */
    public function testLoadClassGlobal($XY)
    {
        // Make a global class that takes one param
        $hint = 'libraries';
        $class = 'GlobalClass';
        $member = 'passed';
        $param = 'token';
        $content = '<?php class '.$class.' { public $'.$member.'; '.
            'public function __construct($param) { $this->'.$member.' = $param; } }';
        include_once $this->vfsCreate($class.'.php', $content)->url();

        // Load class
        $obj = $XY->loadClass($class, $hint, $param);

        // Check class, member, parameter, and hint
        $this->assertInstanceOf($class, $obj);
        $this->assertObjectHasAttribute($member, $obj);
        $this->assertEquals($param, $obj->$member);
        $this->assertEquals($hint, $XY->loader_hint);
    }

    /**
     * Test loadClass() with a namespace
     *
     * @depends testTune
     */
    public function testLoadClassNs($XY)
    {
        // Make a class that takes two params
        $hint = 'models';
        $class = 'SharedClass';
        $member1 = 'passed1';
        $member2 = 'passed2';
        $param1 = 'token1';
        $param2 = 'token2';
        $content = '<?php namespace '.$this->share_ns.'\\'.$hint.'; class '.$class.' { '.
            'public $'.$member1.'; public $'.$member2.'; public function __construct($param1, $param2) { '.
            '$this->'.$member1.' = $param1; $this->'.$member2.' = $param2; } }';
        include_once $this->vfsCreate($class.'.php', $content)->url();

        // Load class
        $obj = $XY->loadClass($this->share_ns.'\\'.$hint.'\\'.$class, $hint, $param1, $param2);

        // Check class, members, and parameters
        $this->assertInstanceOf($this->share_ns.'\\'.$hint.'\\'.$class, $obj);
        $this->assertObjectHasAttribute($member1, $obj);
        $this->assertObjectHasAttribute($member2, $obj);
        $this->assertEquals($param1, $obj->$member1);
        $this->assertEquals($param2, $obj->$member2);
    }

    /**
     * Test adding a bad view path
     *
     * @depends testTune
     */
    public function testAddBadViewPath($XY)
    {
        // Check for bad view path add failure
        $this->assertFalse($XY->addViewPath('some/path'));
    }

    /**
     * Test adding a view path
     *
     * @depends testTune
     * @return  object  Mock Xylophone instance
     */
    public function testAddViewPath($XY)
    {
        // Check for view path add
        $viewdir = 'sharedviews';
        $shvwdir = $this->share_dir->getName().'/'.$viewdir;
        $this->vfsMkdir($viewdir, $this->share_dir);
        $this->assertTrue($XY->addViewPath($shvwdir));
        $this->assertArrayHasKey($shvwdir, $XY->view_paths);
        $this->assertEquals($this->inc_path.'/'.$shvwdir.'/', $XY->view_paths[$shvwdir]);

        // Return instance for path removal
        return $XY;
    }

    /**
     * Test removing a view path
     *
     * @depends testAddViewPath
     */
    public function testRemoveViewPath($XY)
    {
        // Get last view
        end($XY->view_paths);
        $name = key($XY->view_paths);

        // Check for view removal
        $XY->removeViewPath($name);
        $this->assertArrayNotHasKey($name, $XY->view_paths);
    }

    /**
     * Test adding an empty namespace
     *
     * @depends testTune
     */
    public function testAddGlobalNamespace($XY)
    {
        // Check for global namespace add failure
        $this->assertArrayHasKey('', $XY->ns_paths);
        $this->assertFalse($XY->addNamespace('', 'some/path'));
    }

    /**
     * Test adding a bad namespace path
     *
     * @depends testTune
     */
    public function testAddBadNamespace($XY)
    {
        // Check for bad namespace add failure
        $this->assertFalse($XY->addNamespace('NoSpace', 'some/path'));
    }

    /**
     * Test adding a namespace path
     *
     * @depends testTune
     * @return  object  Mock Xylophone instance
     */
    public function testAddNamespace($XY)
    {
        // Add a path in includes
        $ns = 'OtherSpace';
        $dir = 'xyothers/';
        $this->vfsMkdir($dir, $this->inc_dir);

        // Check for namespace add
        $this->assertTrue($XY->addNamespace($ns, $dir));
        $this->assertArrayHasKey($ns, $XY->ns_paths);
        $this->assertEquals($this->inc_path.'/'.$dir, $XY->ns_paths[$ns]);

        // Return instance for namespace removal
        return $XY;
    }

    /**
     * Test removing a namespace path
     *
     * @depends testAddNamespace
     */
    public function testRemoveNamespace($XY)
    {
        // Get last namespace
        end($XY->ns_paths);
        $ns = key($XY->ns_paths);

        // Check for namespace removal
        $XY->removeNamespace($ns);
        $this->assertArrayNotHasKey($ns, $XY->ns_paths);
    }

    /**
     * Test checking for HTTPS
     *
     * @depends testTune
     */
    public function testIsHttps($XY)
    {
        // Capture and unset any existing values
        $keys = array('HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_FRONT_END_HTTPS' => 'on');
        $values = array();
        foreach (array_keys($keys) as $key) {
            if (isset($_SERVER[$key])) {
                $values[$key] = $_SERVER[$key];
                unset($_SERVER[$key]);
            }
        }

        // Confirm not HTTPS
        $this->assertFalse($XY->isHttps());

        // Confirm HTTPS for each key/value
        foreach ($keys as $key => $value) {
            $_SERVER[$key] = $value;
            $this->assertTrue($XY->isHttps());
            unset($_SERVER[$key]);
        }

        // Restore any previous values
        foreach ($values as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Test checking for CLI
     *
     * @depends testTune
     */
    public function testIsCli($XY)
    {
        // Just confirm CLI - can't negative test defines
        $this->assertTrue($XY->isCli());
    }

    /**
     * Test isCallable()
     *
     * @depends testTune
     */
    public function testIsCallable($XY)
    {
        // Confirm tune is callable
        $this->assertTrue($XY->isCallable('Xylophone\core\Xylophone', 'tune'));

        // Confirm badmeth is not callable
        $this->assertFalse($XY->isCallable('Xylophone\core\Xylophone', 'badmeth'));
    }

    /**
     * Test isUsable()
     *
     * @depends testTune
     */
    public function testIsUsable($XY)
    {
        // Confirm a known function is usable and a fake one is not
        $this->assertTrue($XY->isUsable('function_exists'));
        $this->assertFalse($XY->isUsable('fake_function_exists'));
    }

    /**
     * Test isWritable() for a file
     *
     * @depends testTune
     */
    public function testIsWritableFile($XY)
    {
        // Create file and get path
        $file = $this->vfsCreate('writefile.txt', 'foobar', $this->vfs_app_dir, 0666);
        $path = $file->url();

        // Check writable
        $this->assertTrue($XY->isWritable($path));

        // Make read-only and check
        $file->chmod(0444);
        $this->assertFalse($XY->isWritable($path));
    }

    /**
     * Test isWritable() for a directory
     *
     * @depends testTune
     */
    public function testIsWritableDir($XY)
    {
        // Create dir and get path
        $dir = $this->vfsMkdir('writefile.txt', $this->vfs_app_dir, 0777);
        $path = $dir->url();

        // Check writable
        $this->assertTrue($XY->isWritable($path));

        // Make read-only and check
        $dir->chmod(0555);
        $this->assertFalse($XY->isWritable($path));
    }
}

