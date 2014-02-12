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
 * - environment    development
 * - base_path      vfs_base_path/
 * - system_path    vfs_sys_path/
 * - ns_paths       [ '' => vfs_app_path/, share_ns => vfs_share_path/, Mocks => TESTPATH/Mocks/ ]
 * - app_ns         ''
 * - app_path       vfs_app_path/
 * - config_paths   [ share_path/, vfs_app_path/ ]
 * - view_paths     [ views/ => vfs_app_path/views/ ]
 * - override_core  FALSE
 * - library_search TRUE
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
    public function setUp()
    {
        // We can't instantiate externally to trigger autoloader, so
        // manually include source file and mock source
        include_once BASEPATH.'system/core/Xylophone.php';
        include_once TESTPATH.'Mocks/core/Xylophone.php';

        // Default to skipping handlers
        Mocks\core\Xylophone::$skip_handlers = true;

        // Initialize our VFS
        $this->vfsInit();

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
    public function testTuneDefault($XY)
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
     * @depends testTuneDefault
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

        // Clean up
        $XY->loader_hint = '';
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
        $this->makeClass($class, '__construct', null, $XY->system_ns.'\\'.$hint, '', 'AutoloadException');

        // Check for NULL
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
        $class = 'GlobalClass';
        $member = 'passed';
        $this->makeClass($class, '__construct', array($member));

        // Load class
        $hint = 'libraries';
        $param = 'token';
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
        // Make a class that takes two ctor params
        $hint = 'models';
        $class = 'SharedClass';
        $member1 = 'passed1';
        $member2 = 'passed2';
        $this->makeClass($class, '__construct', array($member1, $member2), $this->share_ns.'\\'.$hint);

        // Load class
        $param1 = 'token1';
        $param2 = 'token2';
        $obj = $XY->loadClass($this->share_ns.'\\'.$hint.'\\'.$class, $hint, $param1, $param2);

        // Check class, members, and parameters
        $this->assertInstanceOf($this->share_ns.'\\'.$hint.'\\'.$class, $obj);
        $this->assertObjectHasAttribute($member1, $obj);
        $this->assertObjectHasAttribute($member2, $obj);
        $this->assertEquals($param1, $obj->$member1);
        $this->assertEquals($param2, $obj->$member2);
    }

    /**
     * Test callController() with a bad class
     *
     * @depends testTune
     */
    public function testCallControllerFail($XY)
    {
        // Pass a non-existent class and an invalid route stack and confirm failure
        $this->assertFalse($XY->callController('BadClass'));
        $this->assertFalse($XY->callController(array('method' => 'index')));
    }

    /**
     * Test callController() with a bad method
     *
     * @depends testTune
     */
    public function testCallControllerBadMethod($XY)
    {
        // Create dummy class with no index method
        $class = 'EmptyClass';
        $this->makeClass($class, 'none');

        // Attach instance
        $name = strtolower($class);
        $XY->$name = new $class();

        // Call class (with default 'index') and confirm failure
        $this->assertFalse($XY->callController(array('class' => $class)));

        // Clean up
        unset($XY->$name);
    }

    /**
     * Test callController() with a remap method
     *
     * @depends testTune
     */
    public function testCallControllerRemap($XY)
    {
        // Create controller with remap method
        $class = 'RemapCtlr';
        $member1 = 'passed1';
        $member2 = 'passed2';
        $this->makeClass($class, 'xyRemap', array($member1, $member2));

        // Attach instance
        $name = strtolower($class);
        $XY->$name = new $class();

        // Call class and confirm success
        $method = 'foobar';
        $args = array('baz');
        $stack = array('class' => $class, 'method' => $method, 'args' => $args);
        $this->assertTrue($XY->callController($stack));

        // Verify passed arguments
        $this->assertObjectHasAttribute($member1, $XY->$name);
        $this->assertEquals($method, $XY->$name->$member1);
        $this->assertObjectHasAttribute($member2, $XY->$name);
        $this->assertEquals($args, $XY->$name->$member2);

        // Clean up
        unset($XY->$name);
    }

    /**
     * Test callController() with output capture
     *
     * @depends testTune
     */
    public function testCallControllerOutput($XY)
    {
        // Create controller with remap method
        $class = 'TestOutCtlr';
        $method = 'makeOut';
        $member = 'passed';
        $this->makeClass($class, $method, array($member));

        // Attach instance
        $name = strtolower($class);
        $XY->$name = new $class();

        // Create mock Output class with stack calls
        $output = 'My Controller Made This';
        $XY->output = $this->getMock('Xylophone\core\Output', array(), array(), '', false);
        $XY->output->expects($this->any())->method('stackPop')->will($this->returnValue($output));

        // Call class and confirm output
        $param = 'session';
        $this->assertEquals($output, $XY->callController($class, $method, array($param), '', true));

        // Verify passed argument
        $this->assertObjectHasAttribute($member, $XY->$name);
        $this->assertEquals($param, $XY->$name->$member);

        // Clean up
        unset($XY->$name);
        unset($XY->output);
    }

    /**
     * Test play()
     *
     * @depends testTune
     */
    public function testPlay($XY)
    {
        // Set up play test arguments
        $XY->play_args['intro_atl'] = array('foo' => 'bar', 'bar' => 'baz');
        $XY->play_args['coda_ret'] = false;
        $benchmark = 'time';
        $config = array('name' => 'value');
        $routing = array('dir' => 'empty', 'ctlr' => 'none', 'meth' => 'blank');

        // Call play and check args
        $XY->play($benchmark, $config, $routing);
        $this->assertArrayHasKey('intro_bmk', $XY->play_args);
        $this->assertEquals($benchmark, $XY->play_args['intro_bmk']);
        $this->assertArrayHasKey('intro_cfg', $XY->play_args);
        $this->assertEquals($config, $XY->play_args['intro_cfg']);
        $this->assertArrayHasKey('bridge_rtg', $XY->play_args);
        $this->assertEquals($routing, $XY->play_args['bridge_rtg']);
        $this->assertArrayHasKey('chorus_bmk', $XY->play_args);
        $this->assertEquals($benchmark, $XY->play_args['chorus_bmk']);
        $this->assertArrayHasKey('chorus_atl', $XY->play_args);
        $this->assertEquals($XY->play_args['intro_atl'], $XY->play_args['chorus_atl']);
        $this->assertArrayHasKey('verse_bmk', $XY->play_args);
        $this->assertEquals($benchmark, $XY->play_args['verse_bmk']);

        // Clean up
        $XY->play_args = array();
    }

    /**
     * Test play() with a cache
     *
     * @depends testTune
     */
    public function testPlayCache($XY)
    {
        // Set up play test arguments
        $XY->play_args['intro_atl'] = 'foo';
        $XY->play_args['coda_ret'] = true;
        $benchmark = false;
        $config = array('name' => 'value');
        $routing = array('dir' => 'empty', 'ctlr' => 'none', 'meth' => 'blank');

        // Call play and check args
        $XY->play($benchmark, $config, $routing);
        $this->assertArrayHasKey('intro_bmk', $XY->play_args);
        $this->assertEquals($benchmark, $XY->play_args['intro_bmk']);
        $this->assertArrayHasKey('intro_cfg', $XY->play_args);
        $this->assertEquals($config, $XY->play_args['intro_cfg']);
        $this->assertArrayHasKey('bridge_rtg', $XY->play_args);
        $this->assertEquals($routing, $XY->play_args['bridge_rtg']);

        // These should not be set because of cache
        $this->assertArrayNotHasKey('chorus_bmk', $XY->play_args);
        $this->assertArrayNotHasKey('chorus_atl', $XY->play_args);
        $this->assertArrayNotHasKey('verse_bmk', $XY->play_args);

        // Clean up
        $XY->play_args = array();
    }

    /**
     * Test playIntro()
     *
     * @depends testTune
     */
    public function testPlayIntro($XY)
    {
        // Set up arguments
        $benchmark = false;
        $config = array('item1' => 'one', 'item2' => 'two');
        $autocfg = array('item3' => 'three', 'item4' => 'four');
        $autoload = array('config' => $autocfg, 'other' => 'nothing');
        $mimes = array('face' => 'white', 'speech' => false);

        // Get mock Config, Logger, and Output
        $cfg = $this->getMock('Xylophone\core\Config', array('setItem', 'get', 'load'), array(), '', false);
        $lgr = $this->getMock('Xylophone\core\Logger', null, array(), '', false);
        $out = $this->getMock('Xylophone\core\Output', null, array(), '', false);

        // Set up Config calls
        // The at() indexes represent the sequenced calls to Config methods
        // In order to verify the various parameters, we have to specify the sequence
        $cfg->expects($this->at(0))->method('setItem')->with($this->equalTo($config));
        $cfg->expects($this->at(1))->method('get')->with($this->equalTo('constants.php'), $this->isFalse());
        $cfg->expects($this->at(2))->method('get')->with($this->equalTo('autoload.php'), $this->equalTo('autoload'))->
            will($this->returnValue($autoload));
        $cfg->expects($this->at(3))->method('load')->with($this->equalTo($autocfg));
        $cfg->expects($this->at(4))->method('get')->with($this->equalTo('mimes.php'), $this->equalTo('mimes'))->
            will($this->returnValue($mimes));
        $cfg->expects($this->at(5))->method('setItem')->with($this->equalTo('mimes'), $this->equalTo($mimes));

        // Backstop loadClass() with objects
        $XY->load_class['core\Config'] = $cfg;
        $XY->load_class['core\Logger'] = $lgr;
        $XY->load_class['core\Output'] = $out;

        // Call playIntro() and confirm autoload return and loaded objects
        $this->assertEquals($autoload, $XY->playIntro($benchmark, $config));
        $this->assertObjectNotHasAttribute('benchmark', $XY);
        $this->assertObjectHasAttribute('config', $XY);
        $this->assertEquals($cfg, $XY->config);
        $this->assertObjectHasAttribute('logger', $XY);
        $this->assertEquals($lgr, $XY->logger);
        $this->assertObjectHasAttribute('output', $XY);
        $this->assertEquals($out, $XY->output);

        // Clean up
        unset($XY->config);
        unset($XY->logger);
        unset($XY->output);
        $XY->load_class = array();
    }

    /**
     * Test playIntro() with Benchmark
     *
     * @depends testTune
     */
    public function testPlayIntroBenchmark($XY)
    {
        // Set up arguments
        $benchmark = 'time';
        $config = false;

        // Get mock Benchmark, Config, Logger, and Output
        $bmk = $this->getMock('Xylophone\core\Benchmark', null, array(), '', false);
        $cfg = $this->getMock('Xylophone\core\Config', array('setItem', 'get', 'load'), array(), '', false);
        $lgr = $this->getMock('Xylophone\core\Logger', null, array(), '', false);
        $out = $this->getMock('Xylophone\core\Output', null, array(), '', false);

        // Backstop loadClass() with objects
        $XY->load_class['core\Benchmark'] = $bmk;
        $XY->load_class['core\Config'] = $cfg;
        $XY->load_class['core\Logger'] = $lgr;
        $XY->load_class['core\Output'] = $out;

        // Call playIntro() and confirm loaded objects
        $this->assertNull($XY->playIntro($benchmark, $config));
        $this->assertObjectHasAttribute('benchmark', $XY);
        $this->assertEquals($bmk, $XY->benchmark);
        $this->assertObjectHasAttribute('config', $XY);
        $this->assertEquals($cfg, $XY->config);
        $this->assertObjectHasAttribute('logger', $XY);
        $this->assertEquals($lgr, $XY->logger);
        $this->assertObjectHasAttribute('output', $XY);
        $this->assertEquals($out, $XY->output);

        // Verify markers
        $this->assertObjectHasAttribute('marker', $XY->benchmark);
        $this->assertEquals(array(
            'total_execution_time_start' => $benchmark,
            'loading_time:_base_classes_start' => $benchmark
        ), $XY->benchmark->marker);

        // Clean up
        unset($XY->benchmark);
        unset($XY->config);
        unset($XY->logger);
        unset($XY->output);
        $XY->load_class = array();
    }

    /**
     * Test playBridge()
     *
     * @depends testTune
     */
    public function testPlayBridge($XY)
    {
        // Set up arguments
        $routing = array('directory' => '', 'controller' => '', 'function' => '');

        // Get mock Loader, Hooks, Utf8, URI, and Router
        $ldr = $this->getMock('Xylophone\core\Loader', null, array(), '', false);
        $hks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $utf = $this->getMock('Xylophone\core\Utf8', null, array(), '', false);
        $uri = $this->getMock('Xylophone\core\URI', null, array(), '', false);
        $rtr = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up callHook() call
        $hks->expects($this->once())->method('callHook')->with($this->equalTo('pre_system'));

        // Backstop loadClass() with objects
        $XY->load_class['core\Loader'] = $ldr;
        $XY->load_class['core\Hooks'] = $hks;
        $XY->load_class['core\Utf8'] = $utf;
        $XY->load_class['core\URI'] = $uri;
        $XY->load_class['core\Router'] = $rtr;

        // Call playBridge() and confirm loaded objects
        $XY->playBridge($routing);
        $this->assertObjectHasAttribute('load', $XY);
        $this->assertEquals($ldr, $XY->load);
        $this->assertObjectHasAttribute('hooks', $XY);
        $this->assertEquals($hks, $XY->hooks);
        $this->assertObjectHasAttribute('utf8', $XY);
        $this->assertEquals($utf, $XY->utf8);
        $this->assertObjectHasAttribute('uri', $XY);
        $this->assertEquals($uri, $XY->uri);
        $this->assertObjectHasAttribute('router', $XY);
        $this->assertEquals($rtr, $XY->router);

        // Verify routing
        $this->assertObjectHasAttribute('route', $XY->router);
        $this->assertEquals(array(
            'path' => $routing['directory'],
            'class' => $routing['controller'],
            'method' => $routing['function'],
            'args' => array()
        ), $XY->router->route);

        // Clean up
        unset($XY->load);
        unset($XY->hooks);
        unset($XY->utf8);
        unset($XY->uri);
        unset($XY->router);
        $XY->load_class = array();
    }

    /**
     * Test playCoda()
     *
     * @depends testTune
     */
    public function testPlayCoda($XY)
    {
        // Get mock Hooks and Output
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('displayCache'), array(), '', false);

        // Set up calls
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(false));
        $XY->output->expects($this->once())->method('displayCache')->will($this->returnValue(true));

        // Call playCoda() and confirm TRUE
        $this->assertTrue($XY->playCoda());

        // Clean up
        unset($XY->hooks);
        unset($XY->output);
    }

    /**
     * Test playCoda() with cache override
     *
     * @depends testTune
     */
    public function testPlayCodaOverride($XY)
    {
        // Get mock Hooks and set up callHook() call
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(true));

        // Call playCoda() and confirm FALSE
        $this->assertFalse($XY->playCoda());

        // Clean up
        unset($XY->hooks);
    }

    /**
     * Test playCoda() with no cache
     *
     * @depends testTune
     */
    public function testPlayCodaNoCache($XY)
    {
        // Get mock Hooks and Output
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('displayCache'), array(), '', false);

        // Set up calls
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(false));
        $XY->output->expects($this->once())->method('displayCache')->will($this->returnValue(false));

        // Call playCoda() and confirm FALSE
        $this->assertFalse($XY->playCoda());

        // Clean up
        unset($XY->hooks);
        unset($XY->output);
    }

    /**
     * Test playChorus()
     *
     * @depends testTune
     */
    public function testPlayChorus($XY)
    {
        // Set up arguments
        $benchmark = false;
        $autoload = array('language' => 'klingon', 'drivers' => 'database', 'libraries' => 'books', 'model' => 'T');

        // Get mock Security, Input, Lang, and Loader
        $sec = $this->getMock('Xylophone\core\Security', null, array(), '', false);
        $inp = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        $lng = $this->getMock('Xylophone\core\Lang', array('load'), array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('driver', 'library', 'model'), array(), '', false);

        // Set up calls
        $lng->expects($this->once())->method('load')->with($this->equalTo($autoload['language']));
        $XY->load->expects($this->once())->method('driver')->with($this->equalTo($autoload['drivers']));
        $XY->load->expects($this->once())->method('library')->with($this->equalTo($autoload['libraries']));
        $XY->load->expects($this->once())->method('model')->with($this->equalTo($autoload['model']));

        // Backstop loadClass() with objects
        $XY->load_class['core\Security'] = $sec;
        $XY->load_class['core\Input'] = $inp;
        $XY->load_class['core\Lang'] = $lng;

        // Call playChorus() and confirm loaded objects
        $XY->playChorus($benchmark, $autoload);
        $this->assertObjectHasAttribute('security', $XY);
        $this->assertEquals($sec, $XY->security);
        $this->assertObjectHasAttribute('input', $XY);
        $this->assertEquals($inp, $XY->input);
        $this->assertObjectHasAttribute('lang', $XY);
        $this->assertEquals($lng, $XY->lang);

        // Clean up
        unset($XY->security);
        unset($XY->input);
        unset($XY->lang);
        unset($XY->load);
        $XY->load_class = array();
    }

    /**
     * Test playChorus() with benchmark
     *
     * @depends testTune
     */
    public function testPlayChorusBenchmark($XY)
    {
        // Set up arguments
        $benchmark = 'time';
        $autoload = array();

        // Get mock Benchmark, Security, Input, and Lang
        $XY->benchmark = $this->getMock('Xylophone\core\Benchmark', array('mark'), array(), '', false);
        $sec = $this->getMock('Xylophone\core\Security', null, array(), '', false);
        $inp = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        $lng = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up mark() call
        $XY->benchmark->expects($this->once())->method('mark')->with($this->equalTo('loading_time:_base_classes_end'));

        // Backstop loadClass() with objects
        $XY->load_class['core\Security'] = $sec;
        $XY->load_class['core\Input'] = $inp;
        $XY->load_class['core\Lang'] = $lng;

        // Call playChorus()
        $XY->playChorus($benchmark, $autoload);

        // Clean up
        unset($XY->benchmark);
        unset($XY->security);
        unset($XY->input);
        unset($XY->lang);
        $XY->load_class = array();
    }

    /**
     * Test playVerse()
     *
     * @depends testTune
     */
    public function testPlayVerse($XY)
    {
        // Set up arguments
        $class = 'Main';
        $route = array('path' => '', 'class' => $class, 'method' => 'index', 'args' => array());
        $name = strtolower($class);

        // Mock Hooks, Router, and Loader
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);

        // Set up calls
        $XY->hooks->expects($this->at(0))->method('callHook')->with($this->equalTo('pre_controller'));
        $XY->hooks->expects($this->at(1))->method('callHook')->with($this->equalTo('post_controller_constructor'));
        $XY->hooks->expects($this->at(2))->method('callHook')->with($this->equalTo('post_controller'));
        $XY->hooks->expects($this->at(3))->method('callHook')->with($this->equalTo('display_override'))->
            will($this->returnValue(true));
        $XY->hooks->expects($this->at(4))->method('callHook')->with($this->equalTo('post_system'));
        $XY->load->expects($this->once())->method('controller')->
            with($this->equalTo($route), $this->equalTo($name), $this->equalTo(false))->
            will($this->returnValue(true));

        // Trigger callController() override with return value, set route, and set fake class object
        $XY->call_controller = true;
        $XY->router->route = $route;
        $XY->$name = (object)array('member' => 'club');

        // Call playVerse() and verify routed and callController() argument
        $XY->playVerse(false);
        $this->assertObjectHasAttribute('routed', $XY);
        $this->assertEquals($XY->$name, $XY->routed);
        $this->assertEquals($route, $XY->call_controller);

        // Clean up
        unset($XY->hooks);
        unset($XY->router);
        unset($XY->load);
        unset($XY->routed);
        unset($XY->$name);
        $XY->call_controller = null;
    }

    /**
     * Test playVerse() with benchmarks
     *
     * @depends testTune
     */
    public function testPlayVerseBenchmark($XY)
    {
        // Mock Benchmark, Hooks, Router, Loader, and Output
        $XY->benchmark = $this->getMock('Xylophone\core\Benchmark', array('mark'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('display'), array(), '', false);

        // Set up calls
        $XY->benchmark->expects($this->at(0))->method('mark')->with($this->equalTo('controller_execution_time_start'));
        $XY->benchmark->expects($this->at(1))->method('mark')->with($this->equalTo('controller_execution_time_end'));
        $XY->hooks->expects($this->exactly(5))->method('callHook')->will($this->returnValue(false));
        $XY->load->expects($this->once())->method('controller')->will($this->returnValue(false));
        $XY->output->expects($this->once())->method('display');

        // Trigger callController() override with return value and show404() with argument array
        $XY->call_controller = false;
        $XY->show_404 = array();

        // Set route and dummy class 'object'
        $class = 'BadClass';
        $method = 'none';
        $XY->router->route = array('class' => $class, 'method' => $method);
        $name = strtolower($class);
        $XY->$name = 'null';

        // Call playVerse() and verify show404() arguments
        $XY->playVerse('time');
        $this->assertEquals(array($class.'/'.$method, $class.'/'.$method), $XY->show_404);

        // Clean up
        unset($XY->benchark);
        unset($XY->hooks);
        unset($XY->router);
        unset($XY->load);
        unset($XY->output);
        unset($XY->$name);
        unset($XY->routed);
        $XY->call_controller = null;
        $XY->show_404 = null;
    }

    /**
     * Test playVerse() with Al Fine Exception
     *
     * @depends testTune
     */
    public function testPlayVerseAlFine($XY)
    {
        // Mock Hooks, Router, Loader, and Output
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('display'), array(), '', false);

        // Set up calls
        $XY->hooks->expects($this->exactly(5))->method('callHook')->will($this->returnValue(false));
        $XY->load->expects($this->once())->method('controller')->will($this->returnValue(true));
        $XY->output->expects($this->once())->method('display');

        // Trigger callController() override with exception
        $XY->call_controller = 'Xylophone\core\AlFineException';

        // Set route and dummy class 'object'
        $class = 'ShortClass';
        $XY->router->route = array('class' => $class, 'method' => 'endsEarly');

        // Set dummy class 'object'
        $name = strtolower($class);
        $XY->$name = 'null';

        // Call playVerse()
        $XY->playVerse(false);

        // Clean up
        unset($XY->hooks);
        unset($XY->router);
        unset($XY->load);
        unset($XY->output);
        unset($XY->$name);
        unset($XY->routed);
        $XY->call_controller = null;
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

        // Clean up
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
        // Confirm tune is callable and badmeth is not
        $this->assertTrue($XY->isCallable('Xylophone\core\Xylophone', 'tune'));
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
        $dir = $this->vfsMkdir('writedir', $this->vfs_app_dir, 0777);
        $path = $dir->url();

        // Check writable
        $this->assertTrue($XY->isWritable($path));

        // Make read-only and check
        $dir->chmod(0555);
        $this->assertFalse($XY->isWritable($path));
    }

    /**
     * Test show404()
     *
     * @depends testTune
     */
    public function testShow404($XY)
    {
        // Set up arguments
        $page = 'nonexistent';
        $log = true;

        // Get mock Exceptions class and backstop loadClass()
        $obj = $this->getMock('Xylophone\core\Exceptions', array('show404'), array(), '', false);
        $obj->expects($this->once())->method('show404')->with($this->equalTo($page), $this->equalTo($log));
        $XY->load_class['core\Exceptions'] = $obj;

        // Call show404() and clean up
        $XY->show404($page, $log);
        $XY->load_class = array();
    }

    /**
     * Test showError()
     *
     * @depends testTune
     */
    public function testShowError($XY)
    {
        // Set up arguments
        $msg = 'Something went wrong';
        $stat = 501;
        $head = 'The screw-up fairy has been here';

        // Get mock Exceptions class and backstop loadClass()
        $obj = $this->getMock('Xylophone\core\Exceptions', array('showError'), array(), '', false);
        $obj->expects($this->once())->method('showError')->with($this->equalTo($head), $this->equalTo($msg),
            $this->equalTo('error_general'), $this->equalTo($stat));
        $XY->load_class['core\Exceptions'] = $obj;

        // Call showError() and clean up
        $XY->showError($msg, $stat, $head);
        $XY->load_class = array();
    }

    /**
     * Test exceptionHandler()
     *
     * @depends testTune
     */
    public function testExceptionHandler($XY)
    {
        // Set up arguments
        $severity = E_ERROR;
        $message = 'Something broke';
        $filepath = 'path/to/file';
        $line = 13;

        // Mock Exceptions and set up calls
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $exc->expects($this->never())->method('showPhpError');
        $exc->expects($this->once())->method('logException')->
            with($this->equalTo($severity), $this->equalTo($message), $this->equalTo($filepath), $this->equalTo($line));

        // Backstop loadClass() with object
        $XY->load_class['core\Exceptions'] = $exc;

        // Ensure our severity is set and display_errors is off
        $errors = error_reporting();
        error_reporting($errors | $severity);
        $display = ini_get('display_errors');
        ini_set('display_errors', '0');

        // Call exceptionHandler()
        $XY->exceptionHandler($severity, $message, $filepath, $line);

        // Clean up
        error_reporting($errors);
        ini_set('display_errors', $display);
        $XY->load_class = array();
    }

    /**
     * Test exceptionHandler() with showPhpError()
     *
     * @depends testTune
     */
    public function testExceptionHandlerShow($XY)
    {
        // Set up arguments
        $severity = E_NOTICE;
        $message = 'PEBKAC';
        $filepath = 'some/fake/file';
        $line = 666;

        // Mock Exceptions and set up calls
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $exc->expects($this->once())->method('showPhpError')->
            with($this->equalTo($severity), $this->equalTo($message), $this->equalTo($filepath), $this->equalTo($line));
        $exc->expects($this->once())->method('logException')->
            with($this->equalTo($severity), $this->equalTo($message), $this->equalTo($filepath), $this->equalTo($line));

        // Backstop loadClass() with object
        $XY->load_class['core\Exceptions'] = $exc;

        // Ensure our severity is set and display_errors is on
        $errors = error_reporting();
        error_reporting($errors | $severity);
        $display = ini_get('display_errors');
        ini_set('display_errors', '1');

        // Call exceptionHandler()
        $XY->exceptionHandler($severity, $message, $filepath, $line);

        // Clean up
        error_reporting($errors);
        ini_set('display_errors', $display);
        $XY->load_class = array();
    }

    /**
     * Test exceptionHandler() ignoring an error
     *
     * @depends testTune
     */
    public function testExceptionHandlerIgnore($XY)
    {
        // Mock Exceptions and set up calls
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $exc->expects($this->never())->method('showPhpError');
        $exc->expects($this->never())->method('logException');

        // Backstop loadClass() with object
        $XY->load_class['core\Exceptions'] = $exc;

        // Get reporting level and remove our severity
        $severity = E_WARNING;
        $errors = error_reporting();
        error_reporting($errors ^ $severity);

        // Call exceptionHandler() with the removed severity
        $XY->exceptionHandler($severity, 'foo', 'bar', 42);

        // Clean up
        error_reporting($errors);
        $XY->load_class = array();
    }
}

