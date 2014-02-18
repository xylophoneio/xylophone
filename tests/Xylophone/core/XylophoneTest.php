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
    }

    /**
     * Load our virtual filesystem
     */
    private function loadFilesystem()
    {
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
        Xylophone\core\Xylophone::instance($init);
    }

    /**
     * Test instance() with a bad namespace
     */
    public function testInstanceNsFail()
    {
        // Load VFS
        $this->vfsInit();

        // Call instance with a bad (missing) namespace
        $init = array('environment' => 'production', 'ns_paths' => array('' => $this->vfs_app_path, 'badpath/'));
        $this->setExpectedException('Xylophone\core\ExitException',
            'The global namespace is reserved for application classes. '.
            'Please specify a namespace for your additional path in the following file: '.
            basename($_SERVER['PHP_SELF']), EXIT_XY);
        Xylophone\core\Xylophone::instance($init);
    }

    /**
     * Test instance() with a bad namespace path
     */
    public function testInstanceNsPathFail()
    {
        // Load VFS
        $this->vfsInit();

        // Call instance with a bad ns path
        $ns = 'BadSpace';
        $init = array('environment' => 'production', 'ns_paths' => array('' => $this->vfs_app_path, $ns => 'badpath/'));
        $this->setExpectedException('Xylophone\core\ExitException',
            'The "'.$ns.'" namespace path does not appear to be set correctly.'.
            ' Please fix it in the following file: '.basename($_SERVER['PHP_SELF']), EXIT_XY);
        Xylophone\core\Xylophone::instance($init);
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
        Mocks\core\Xylophone::$skip_tune = true;
        $init = array('ns_paths' => array('Mocks' => TESTPATH.'Mocks/'));
        $XY = Xylophone\core\Xylophone::instance($init);
        $this->assertInstanceOf('Xylophone\core\Xylophone', $XY);

        // Call again and check for same object
        $xy = Xylophone\core\Xylophone::instance();
        $this->assertSame($XY, $xy);
        Mocks\core\Xylophone::$skip_tune = false;

        // Return our instance for use later
        return $XY;
    }

    /**
     * Test tune() defaults
     */
    public function testTuneDefault()
    {
        // Mock Xylophone and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp', 'addViewPath', 'registerHandlers'),
            array(), '', false);
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(true));
        $XY->expects($this->once())->method('addViewPath')->with($this->equalTo(array('')));
        $XY->expects($this->once())->method('registerHandlers');

        // Call tune with empty parameters
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

        // Check config paths
        $this->assertEquals(array($app_path), $XY->config_paths);

        // Check override flags
        $this->assertFalse($XY->override_core);
        $this->assertFalse($XY->library_search);
    }

    /**
     * Test tune()
     *
     * @return  object  Mock Xylophone instance
     */
    public function testTune()
    {
        // Load the filesystem
        $this->loadFilesystem();

        // Set up test parameter vars
        $env = 'development';
        $basedir = $this->vfs_base_path.'/';
        $sysdir = $this->vfs_sys_path.'/';
        $appdir = $this->vfs_app_path.'/';
        $shdir = $this->share_path.'/';
        $appns = '';
        $nspath = array($appns => $appdir, $this->share_ns => $shdir, 'Mocks' => TESTPATH.'Mocks/');
        $appvwnm = 'views/';

        // Mock Xylophone and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp', 'addViewPath', 'registerHandlers'),
            array(), '', false);
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(true));
        $XY->expects($this->once())->method('addViewPath')->with($this->equalTo(array($appvwnm)));
        $XY->expects($this->once())->method('registerHandlers');

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

        // Check config paths
        $this->assertEquals($XY->config_paths, array($shdir, $appdir));

        // Check override flags
        $this->assertFalse($XY->override_core);
        $this->assertTrue($XY->library_search);

        // Return instance for post-init testing
        return $XY;
    }

    /**
     * Test autoloader() fail
     */
    public function testAutoloaderFail()
    {
        // Load vfs
        $this->vfsinit();

        // Mock xylophone and set system path
        $XY = $this->getmock('xylophone\core\xylophone', null, array(), '', false);
        $XY->system_path = $this->vfs_sys_path.'/';

        // Try to load a non-existent class
        $class = 'Xylophone\core\badclass';
        $this->setExpectedException('\Xylophone\Core\AutoloadException', 'Could not find class "'.$class.'"');
        $XY->autoloader($class);
    }

    /**
     * Test autoloader() with its own exception
     */
    public function testAutoloaderException()
    {
        // Load vfs
        $this->vfsinit();

        // Mock xylophone and set system path
        $XY = $this->getmock('xylophone\core\xylophone', null, array(), '', false);
        $XY->system_path = $this->vfs_sys_path.'/';

        // Try to load exception without file
        $class = 'Xylophone\core\AutoloadException';
        $XY->autoloader($class);
    }

    /**
     * Test autoloader()
     */
    public function testAutoloader()
    {
        // Load the filesystem
        $this->loadFilesystem();

        // Mock Xylophone and set ns paths
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $XY->ns_paths = array('' => $this->vfs_app_path.'/', $this->share_ns => $this->share_path.'/');

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
     */
    public function testAutoloaderHint()
    {
        // Load VFS
        $this->vfsInit();

        // Mock Xylophone and set ns paths
        $XY = $this->getMock('Mocks\core\Xylophone', null, array(), '', false);
        $XY->ns_paths = array('' => $this->vfs_app_path.'/');

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
     */
    public function testLoadClassFail()
    {
        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

        // Make a class that emulates the autoloader not-found behavior
        $hint = 'core';
        $class = 'MissingClass';
        $this->makeClass($class, '__construct', null, $XY->system_ns.'\\'.$hint, '', 'AutoloadException');

        // Check for NULL
        $this->assertNull($XY->loadClass($class, $hint));
    }

    /**
     * Test loadClass() with a global class
     */
    public function testLoadClassGlobal()
    {
        // Mock Xylophone
        $XY = $this->getMock('Mocks\core\Xylophone', null, array(), '', false);

        // Enable library search and set ns_paths
        $XY->library_search = true;
        $XY->ns_paths = array('' => '/dont/need/path/');

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
     */
    public function testLoadClassNs()
    {
        // Load the filesystem
        $this->loadFilesystem();

        // Mock Xylophone and set ns paths
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $XY->ns_paths = array('' => $this->vfs_app_path.'/', $this->share_ns => $this->share_path.'/');

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
     */
    public function testCallControllerFail()
    {
        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

        // Pass a non-existent class and an invalid route stack and confirm failure
        $this->assertFalse($XY->callController('BadClass'));
        $this->assertFalse($XY->callController(array('method' => 'index')));
    }

    /**
     * Test callController() with a bad method
     */
    public function testCallControllerBadMethod()
    {
        // Mock Xylophone and set up call
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCallable'), array(), '', false);
        $XY->expects($this->any())->method('isCallable')->will($this->returnValue(false));

        // Create dummy class with no index method
        $class = 'EmptyClass';
        $this->makeClass($class, 'none');

        // Attach instance
        $name = strtolower($class);
        $XY->$name = new $class();

        // Call class (with default 'index') and confirm failure
        $this->assertFalse($XY->callController(array('class' => $class)));
    }

    /**
     * Test callController() with a remap method
     */
    public function testCallControllerRemap()
    {
        // Set up args
        $class = 'RemapCtlr';

        // Mock Xylophone and set up call
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCallable'), array(), '', false);
        $XY->expects($this->once())->method('isCallable')->with($this->equalTo($class), $this->equalTo('xyRemap'))->
            will($this->returnValue(true));

        // Create controller with remap method
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
    }

    /**
     * Test callController() with output capture
     */
    public function testCallControllerOutput()
    {
        // Set up args
        $class = 'TestOutCtlr';
        $method = 'makeOut';
        $output = 'My Controller Made This';

        // Mock Xylophone and Output and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCallable'), array(), '', false);
        $XY->expects($this->at(0))->method('isCallable')->with($this->equalTo($class), $this->equalTo('xyRemap'))->
            will($this->returnValue(false));
        $XY->expects($this->at(1))->method('isCallable')->with($this->equalTo($class), $this->equalTo($method))->
            will($this->returnValue(true));
        $XY->output = $this->getMock('Xylophone\core\Output', array('stackPush', 'stackPop'), array(), '', false);
        $XY->output->expects($this->once())->method('stackPush');
        $XY->output->expects($this->once())->method('stackPop')->will($this->returnValue($output));

        // Create controller and attach
        $name = strtolower($class);
        $member = 'passed';
        $this->makeClass($class, $method, array($member));
        $XY->$name = new $class();

        // Call class and confirm output
        $param = 'session';
        $this->assertEquals($output, $XY->callController($class, $method, array($param), '', true));

        // Verify passed argument
        $this->assertObjectHasAttribute($member, $XY->$name);
        $this->assertEquals($param, $XY->$name->$member);
    }

    /**
     * Test play()
     */
    public function testPlay()
    {
        // Set up args
        $autoload = array('foo' => 'bar', 'bar' => 'baz');
        $benchmark = 'time';
        $config = array('name' => 'value');
        $routing = array('dir' => 'empty', 'ctlr' => 'none', 'meth' => 'blank');

        // Mock Xylophone and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'playChorus', 'playVerse'), array(), '', false);
        $XY->expects($this->once())->method('playIntro')->with($this->equalTo($benchmark), $this->equalTo($config))->
            will($this->returnValue($autoload));
        $XY->expects($this->once())->method('playBridge')->with($this->equalTo($routing));
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->once())->method('playChorus')->with($this->equalTo($benchmark), $this->equalTo($autoload));
        $XY->expects($this->once())->method('playVerse')->with($this->equalTo($benchmark));

        // Call play
        $XY->play($benchmark, $config, $routing);
    }

    /**
     * Test play() with a cache
     */
    public function testPlayCache()
    {
        // Set up play test arguments
        $benchmark = false;
        $config = array('name' => 'value');
        $routing = array('dir' => 'empty', 'ctlr' => 'none', 'meth' => 'blank');

        // Mock Xylophone and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'playChorus', 'playVerse'), array(), '', false);
        $XY->expects($this->once())->method('playIntro')->with($this->equalTo($benchmark), $this->equalTo($config));
        $XY->expects($this->once())->method('playBridge')->with($this->equalTo($routing));
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(true));
        $XY->expects($this->never())->method('playChorus');
        $XY->expects($this->never())->method('playVerse');

        // Call play
        $XY->play($benchmark, $config, $routing);
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
        $autons = 'foo';
        $autovp = 'bar';
        $autoload = array('config' => $autocfg, 'namespaces' => $autons, 'view_paths' => $autovp);
        $mimes = array('face' => 'white', 'speech' => false);

        // Mock Xylophone, Config, Logger, and Output
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('loadClass', 'addNamespace', 'addViewPath', 'playBridge', 'playCoda', 'playChorus', 'playVerse'),
            array(), '', false);
        $cfg = $this->getMock('Xylophone\core\Config', array('setItem', 'get', 'load'), array(), '', false);
        $lgr = (object)array('name' => 'Logger');
        $out = (object)array('name' => 'Output');

        // Set up Xylophone calls
        // The at() indexes represent the sequenced calls to Xylophone methods
        // In order to verify the various parameters, we have to specify the sequence
        $XY->expects($this->at(0))->method('loadClass')->with($this->equalTo('Config'), $this->equalTo('core'))->
            will($this->returnValue($cfg));
        $XY->expects($this->at(1))->method('loadClass')->with($this->equalTo('Logger'), $this->equalTo('core'))->
            will($this->returnValue($lgr));
        $XY->expects($this->at(2))->method('loadClass')->with($this->equalTo('Output'), $this->equalTo('core'))->
            will($this->returnValue($out));
        $XY->expects($this->once())->method('addNamespace')->with($this->equalTo($autons));
        $XY->expects($this->once())->method('addViewPath')->with($this->equalTo($autovp));
        $XY->expects($this->once())->method('playBridge');
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->once())->method('playChorus')->with($this->equalTo($benchmark), $this->equalTo($autoload));
        $XY->expects($this->once())->method('playVerse');

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

        // Call play() and confirm autoload return and loaded objects
        $XY->play($benchmark, $config);
        $this->assertObjectNotHasAttribute('benchmark', $XY);
        $this->assertObjectHasAttribute('config', $XY);
        $this->assertSame($cfg, $XY->config);
        $this->assertObjectHasAttribute('logger', $XY);
        $this->assertSame($lgr, $XY->logger);
        $this->assertObjectHasAttribute('output', $XY);
        $this->assertSame($out, $XY->output);
    }

    /**
     * Test playIntro() with Benchmark
     */
    public function testPlayIntroBenchmark()
    {
        // Set up args
        $benchmark = 'time';
        $config = false;
        $obj = new stdClass();

        // Mock Xylophone, Benchmark and Config
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass', 'playBridge', 'playCoda'),
            array(), '', false);
        $bmk = $this->getMock('Xylophone\core\Benchmark', null, array(), '', false);
        $cfg = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);

        // Set up Xylophone calls
        // The at() indexes represent the sequenced calls to Xylophone methods
        // In order to verify the various parameters, we have to specify the sequence
        $XY->expects($this->at(0))->method('loadClass')->with($this->equalTo('Benchmark'), $this->equalTo('core'))->
            will($this->returnValue($bmk));
        $XY->expects($this->at(1))->method('loadClass')->with($this->equalTo('Config'), $this->equalTo('core'))->
            will($this->returnValue($cfg));
        $XY->expects($this->at(2))->method('loadClass')->with($this->equalTo('Logger'), $this->equalTo('core'))->
            will($this->returnValue($obj));
        $XY->expects($this->at(3))->method('loadClass')->with($this->equalTo('Output'), $this->equalTo('core'))->
            will($this->returnValue($obj));
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(true));

        // Call play() and verify markers
        $XY->play($benchmark, $config);
        $this->assertObjectHasAttribute('benchmark', $XY);
        $this->assertSame($bmk, $XY->benchmark);
        $this->assertObjectHasAttribute('marker', $XY->benchmark);
        $this->assertEquals(array(
            'total_execution_time_start' => $benchmark,
            'loading_time:_base_classes_start' => $benchmark
        ), $XY->benchmark->marker);
    }

    /**
     * Test playBridge()
     */
    public function testPlayBridge()
    {
        // Set up args
        $routing = array('directory' => 'foo', 'controller' => 'bar', 'function' => 'baz');

        // Mock Xylophone, Loader, Hooks, Utf8, URI, and Router
        $XY = $this->getMock('Xylophone\core\Xylophone', array('playIntro', 'loadClass', 'playCoda'),
            array(), '', false);
        $ldr = (object)array('name' => 'Loader');
        $hks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $utf = (object)array('name' => 'Utf8');
        $uri = (object)array('name' => 'URI');
        $rtr = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up calls
        $XY->expects($this->at(0))->method('playIntro');
        $XY->expects($this->at(1))->method('loadClass')->with($this->equalTo('Loader'), $this->equalTo('core'))->
            will($this->returnValue($ldr));
        $XY->expects($this->at(2))->method('loadClass')->with($this->equalTo('Hooks'), $this->equalTo('core'))->
            will($this->returnValue($hks));
        $XY->expects($this->at(3))->method('loadClass')->with($this->equalTo('Utf8'), $this->equalTo('core'))->
            will($this->returnValue($utf));
        $XY->expects($this->at(4))->method('loadClass')->with($this->equalTo('URI'), $this->equalTo('core'))->
            will($this->returnValue($uri));
        $XY->expects($this->at(5))->method('loadClass')->with($this->equalTo('Router'), $this->equalTo('core'))->
            will($this->returnValue($rtr));
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(true));
        $hks->expects($this->once())->method('callHook')->with($this->equalTo('pre_system'));

        // Call play() and confirm loaded objects
        $XY->play(false, null, $routing);
        $this->assertObjectHasAttribute('load', $XY);
        $this->assertSame($ldr, $XY->load);
        $this->assertObjectHasAttribute('hooks', $XY);
        $this->assertSame($hks, $XY->hooks);
        $this->assertObjectHasAttribute('utf8', $XY);
        $this->assertSame($utf, $XY->utf8);
        $this->assertObjectHasAttribute('uri', $XY);
        $this->assertSame($uri, $XY->uri);
        $this->assertObjectHasAttribute('router', $XY);
        $this->assertSame($rtr, $XY->router);

        // Verify routing
        $this->assertObjectHasAttribute('route', $XY->router);
        $this->assertEquals(array(
            'path' => $routing['directory'],
            'class' => $routing['controller'],
            'method' => $routing['function'],
            'args' => array()
        ), $XY->router->route);
    }

    /**
     * Test playCoda()
     */
    public function testPlayCoda()
    {
        // Mock Xylophone, Hooks, and Output and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playChorus', 'playVerse'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('displayCache'), array(), '', false);
        $XY->expects($this->once())->method('playIntro');
        $XY->expects($this->once())->method('playBridge');
        $XY->expects($this->never())->method('playChorus');
        $XY->expects($this->never())->method('playVerse');
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(false));
        $XY->output->expects($this->once())->method('displayCache')->will($this->returnValue(true));

        // Call play()
        $XY->play();
    }

    /**
     * Test playCoda() with cache override
     */
    public function testPlayCodaOverride()
    {
        // Mock Xylophone, Hooks, and Output and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('playIntro', 'playBridge', 'playChorus', 'playVerse'),
            array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->expects($this->once())->method('playIntro');
        $XY->expects($this->once())->method('playBridge');
        $XY->expects($this->once())->method('playChorus');
        $XY->expects($this->once())->method('playVerse');
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(true));

        // Call play()
        $XY->play();
    }

    /**
     * Test playCoda() with no cache
     */
    public function testPlayCodaNoCache()
    {
        // Mock Xylophone, Hooks, and Output and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playChorus', 'playVerse'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('displayCache'), array(), '', false);
        $XY->expects($this->once())->method('playIntro');
        $XY->expects($this->once())->method('playBridge');
        $XY->expects($this->once())->method('playChorus');
        $XY->expects($this->once())->method('playVerse');
        $XY->hooks->expects($this->once())->method('callHook')->with($this->equalTo('cache_override'))->
            will($this->returnValue(false));
        $XY->output->expects($this->once())->method('displayCache')->will($this->returnValue(false));

        // Call play()
        $XY->play();
    }

    /**
     * Test playChorus()
     */
    public function testPlayChorus()
    {
        // Set up args
        $benchmark = false;
        $autoload = array('language' => 'klingon', 'drivers' => 'database', 'libraries' => 'books', 'model' => 'T');

        // Mock Xylophone, Security, Input, Lang, and Loader
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'loadClass', 'playVerse'), array(), '', false);
        $sec = (object)array('name' => 'Security');
        $inp = (object)array('name' => 'Input');
        $lng = $this->getMock('Xylophone\core\Lang', array('load'), array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('driver', 'library', 'model'), array(), '', false);

        // Set up calls
        $XY->expects($this->at(0))->method('playIntro')->will($this->returnValue($autoload));
        $XY->expects($this->at(1))->method('playBridge');
        $XY->expects($this->at(2))->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->at(3))->method('loadClass')->with($this->equalTo('Security'), $this->equalTo('core'))->
            will($this->returnValue($sec));
        $XY->expects($this->at(4))->method('loadClass')->with($this->equalTo('Input'), $this->equalTo('core'))->
            will($this->returnValue($inp));
        $XY->expects($this->at(5))->method('loadClass')->with($this->equalTo('Lang'), $this->equalTo('core'))->
            will($this->returnValue($lng));
        $lng->expects($this->once())->method('load')->with($this->equalTo($autoload['language']));
        $XY->load->expects($this->once())->method('driver')->with($this->equalTo($autoload['drivers']));
        $XY->load->expects($this->once())->method('library')->with($this->equalTo($autoload['libraries']));
        $XY->load->expects($this->once())->method('model')->with($this->equalTo($autoload['model']));

        // Call play() and confirm loaded objects
        $XY->play($benchmark);
        $this->assertObjectHasAttribute('security', $XY);
        $this->assertSame($sec, $XY->security);
        $this->assertObjectHasAttribute('input', $XY);
        $this->assertSame($inp, $XY->input);
        $this->assertObjectHasAttribute('lang', $XY);
        $this->assertSame($lng, $XY->lang);
    }

    /**
     * Test playChorus() with benchmark
     */
    public function testPlayChorusBenchmark()
    {
        // Set up arguments
        $benchmark = 'time';
        $obj = new stdClass();

        // Mock Xylophone and Benchmark and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'playVerse', 'loadClass'), array(), '', false);
        $XY->benchmark = $this->getMock('Xylophone\core\Benchmark', array('mark'), array(), '', false);
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->exactly(3))->method('loadClass')->will($this->returnValue($obj));
        $XY->benchmark->expects($this->once())->method('mark')->with($this->equalTo('loading_time:_base_classes_end'));

        // Call play()
        $XY->play($benchmark);
    }

    /**
     * Test playVerse()
     */
    public function testPlayVerse()
    {
        // Set up arguments
        $class = 'Main';
        $route = array('path' => '', 'class' => $class, 'method' => 'index', 'args' => array());
        $name = strtolower($class);

        // Mock Xylophone, Hooks, Router, and Loader
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'playChorus', 'callController'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = new stdClass();
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->once())->method('callController')->will($this->returnValue(true));
        $XY->hooks->expects($this->at(0))->method('callHook')->with($this->equalTo('pre_controller'));
        $XY->hooks->expects($this->at(1))->method('callHook')->with($this->equalTo('post_controller_constructor'));
        $XY->hooks->expects($this->at(2))->method('callHook')->with($this->equalTo('post_controller'));
        $XY->hooks->expects($this->at(3))->method('callHook')->with($this->equalTo('display_override'))->
            will($this->returnValue(true));
        $XY->hooks->expects($this->at(4))->method('callHook')->with($this->equalTo('post_system'));
        $XY->load->expects($this->once())->method('controller')->
            with($this->equalTo($route), $this->equalTo($name), $this->isFalse())->
            will($this->returnValue(true));

        // Set route, and set fake class object
        $XY->router->route = $route;
        $XY->$name = (object)array('member' => 'club');

        // Call play() and verify routed
        $XY->play(false);
        $this->assertObjectHasAttribute('routed', $XY);
        $this->assertSame($XY->$name, $XY->routed);
    }

    /**
     * Test playVerse() with benchmarks
     */
    public function testPlayVerseBenchmark()
    {
        // Set up args
        $class = 'BadClass';
        $method = 'none';
        $clsmth = $class.'/'.$method;
        $name = strtolower($class);

        // Mock Xylophone, Benchmark, Hooks, Router, Loader, and Output
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playIntro', 'playBridge', 'playCoda', 'playChorus', 'callController', 'show404'),
            array(), '', false);
        $XY->benchmark = $this->getMock('Xylophone\core\Benchmark', array('mark'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = new stdClass();
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('display'), array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->once())->method('callController')->will($this->returnValue(false));
        $XY->expects($this->exactly(2))->method('show404')->with($this->equalTo($clsmth));
        $XY->benchmark->expects($this->at(0))->method('mark')->with($this->equalTo('controller_execution_time_start'));
        $XY->benchmark->expects($this->at(1))->method('mark')->with($this->equalTo('controller_execution_time_end'));
        $XY->hooks->expects($this->exactly(5))->method('callHook')->will($this->returnValue(false));
        $XY->load->expects($this->once())->method('controller')->will($this->returnValue(false));
        $XY->output->expects($this->once())->method('display');

        // Set route and dummy class 'object'
        $XY->router->route = array('class' => $class, 'method' => $method);
        $XY->$name = 'null';

        // Call play()
        $XY->play('time');
    }

    /**
     * Test playVerse() with Al Fine Exception
     */
    public function testPlayVerseAlFine()
    {
        // Mock Xylophone, Hooks, Router, and Loader
        $XY = $this->getMock('Xylophone\core\Xylophone',
            array('playintro', 'playBridge', 'playCoda', 'playChorus', 'callController'), array(), '', false);
        $XY->hooks = $this->getMock('Xylophone\core\Hooks', array('callHook'), array(), '', false);
        $XY->router = new stdClass();
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('playCoda')->will($this->returnValue(false));
        $XY->expects($this->once())->method('callController')->
            will($this->throwException(new Xylophone\core\AlFineException));
        $XY->hooks->expects($this->exactly(5))->method('callHook')->will($this->returnValue(true));
        $XY->load->expects($this->once())->method('controller')->will($this->returnValue(true));

        // Set route and dummy class 'object'
        $class = 'ShortClass';
        $XY->router->route = array('class' => $class, 'method' => 'endsEarly');

        // Set dummy class 'object'
        $name = strtolower($class);
        $XY->$name = 'null';

        // Call play()
        $XY->play(false);
    }

    /**
     * Test adding a bad view path
     */
    public function testAddBadViewPath()
    {
        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

        // Check for bad view path add failure
        $this->assertFalse($XY->addViewPath('some/path'));
    }

    /**
     * Test adding a view path
     */
    public function testAddViewPath()
    {
        // Load the filesystem
        $this->loadFilesystem();

        // Set up args
        $viewdir = 'sharedviews';
        $shvwdir = $this->share_dir->getName().'/'.$viewdir;

        // Mock Xylophone and set up call
        $XY = $this->getMock('Mocks\core\Xylophone', array('realpath'), array(), '', false);
        $XY->expects($this->once())->method('realpath')->with($this->equalTo($this->share_path.'/'.$viewdir))->
            will($this->returnValue($this->share_path.'/'.$viewdir));

        // Set resolve bases and add our view dir
        $XY->resolve_bases = array('', $this->inc_path.'/');
        $this->vfsMkdir($viewdir, $this->share_dir);

        // Call addViewPath() and verify new path
        $this->assertTrue($XY->addViewPath($shvwdir));
        $this->assertArrayHasKey($shvwdir, $XY->view_paths);
        $this->assertEquals($this->inc_path.'/'.$shvwdir.'/', $XY->view_paths[$shvwdir]);
    }

    /**
     * Test removing a view path
     */
    public function testRemoveViewPath()
    {
        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

        // Set test view
        $name = 'testvw';
        $path = '/fake/view/path';
        $XY->view_paths[$name] = $path;

        // Check for view removal
        $XY->removeViewPath($name);
        $this->assertArrayNotHasKey($name, $XY->view_paths);
    }

    /**
     * Test adding an empty namespace
     */
    public function testAddGlobalNamespace()
    {
        // Mock Xylophone and set up ns paths
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $XY->ns_paths[''] = '/my/app/path';

        // Check for global namespace add failure
        $this->assertFalse($XY->addNamespace('', 'some/path'));
    }

    /**
     * Test adding a bad namespace path
     */
    public function testAddBadNamespace()
    {
        // Mock Xylophone and set up ns paths
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $XY->ns_paths[''] = '/my/app/path';

        // Check for bad namespace add failure
        $this->assertFalse($XY->addNamespace('NoSpace', 'some/path'));
    }

    /**
     * Test adding a namespace path
     */
    public function testAddNamespace()
    {
        // Load the filesystem
        $this->loadFilesystem();

        // Add a path in includes
        $ns = 'OtherSpace';
        $dir = 'xyothers';
        $path = $this->inc_path.'/'.$dir;
        $this->vfsMkdir($dir, $this->inc_dir);

        // Mock Xylophone, set up call, and set resolve bases
        $XY = $this->getMock('Mocks\core\Xylophone', array('realpath'), array(), '', false);
        $XY->expects($this->once())->method('realpath')->with($this->equalTo($path))->will($this->returnValue($path));
        $XY->resolve_bases = array('', $this->inc_path.'/');

        // Check for namespace add
        $this->assertTrue($XY->addNamespace($ns, $dir));
        $this->assertArrayHasKey($ns, $XY->ns_paths);
        $this->assertEquals($path.'/', $XY->ns_paths[$ns]);
    }

    /**
     * Test removing a namespace path
     */
    public function testRemoveNamespace()
    {
        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

        // Set test namespace
        $ns = 'TestSpace';
        $XY->ns_paths[$ns] = '/path/to/nothing';

        // Check for namespace removal
        $XY->removeNamespace($ns);
        $this->assertArrayNotHasKey($ns, $XY->ns_paths);
    }

    /**
     * Test checking for HTTPS
     */
    public function testIsHttps()
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

        // Mock Xylophone and confirm not HTTPS
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
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
     */
    public function testIsCli()
    {
        // Mock Xylophone and confirm CLI - can't negative test defines
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $this->assertTrue($XY->isCli());
    }

    /**
     * Test isCallable()
     */
    public function testIsCallable()
    {
        // Mock Xylophone and confirm tune is callable and badmeth is not
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $this->assertTrue($XY->isCallable('Xylophone\core\Xylophone', 'tune'));
        $this->assertFalse($XY->isCallable('Xylophone\core\Xylophone', 'badmeth'));
    }

    /**
     * Test isUsable()
     */
    public function testIsUsable()
    {
        // Mock Xylophone and confirm a known function is usable and a fake one is not
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $this->assertTrue($XY->isUsable('function_exists'));
        $this->assertFalse($XY->isUsable('fake_function_exists'));
    }

    /**
     * Test isWritable() for a file
     */
    public function testIsWritableFile()
    {
        // Load VFS
        $this->vfsInit();

        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

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
     */
    public function testIsWritableDir()
    {
        // Load VFS
        $this->vfsInit();

        // Mock Xylophone
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);

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
     */
    public function testShow404()
    {
        // Set up args
        $page = 'nonexistent';
        $log = true;

        // Mock Xylophone and Exceptions and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass'), array(), '', false);
        $exc = $this->getMock('Xylophone\core\Exceptions', array('show404'), array(), '', false);
        $XY->expects($this->once())->method('loadClass')->with($this->equalTo('Exceptions'), $this->equalTo('core'))->
            will($this->returnValue($exc));
        $exc->expects($this->once())->method('show404')->with($this->equalTo($page), $this->equalTo($log));

        // Call show404()
        $XY->show404($page, $log);
    }

    /**
     * Test showError()
     */
    public function testShowError()
    {
        // Set up arguments
        $msg = 'Something went wrong';
        $stat = 501;
        $head = 'The screw-up fairy has been here';

        // Mock Xylophone and Exceptions and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass'), array(), '', false);
        $exc = $this->getMock('Xylophone\core\Exceptions', array('showError'), array(), '', false);
        $XY->expects($this->once())->method('loadClass')->with($this->equalTo('Exceptions'), $this->equalTo('core'))->
            will($this->returnValue($exc));
        $exc->expects($this->once())->method('showError')->with($this->equalTo($head), $this->equalTo($msg),
            $this->equalTo('error_general'), $this->equalTo($stat));

        // Call showError()
        $XY->showError($msg, $stat, $head);
    }

    /**
     * Test exceptionHandler()
     */
    public function testExceptionHandler()
    {
        // Set up arguments
        $severity = E_ERROR;
        $message = 'Something broke';
        $filepath = 'path/to/file';
        $line = 13;

        // Mock Xylophone and Exceptions and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass'), array(), '', false);
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $XY->expects($this->once())->method('loadClass')->with($this->equalTo('Exceptions'), $this->equalTo('core'))->
            will($this->returnValue($exc));
        $exc->expects($this->never())->method('showPhpError');
        $exc->expects($this->once())->method('logException')->
            with($this->equalTo($severity), $this->equalTo($message), $this->equalTo($filepath), $this->equalTo($line));

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
    }

    /**
     * Test exceptionHandler() with showPhpError()
     */
    public function testExceptionHandlerShow()
    {
        // Set up arguments
        $severity = E_NOTICE;
        $message = 'PEBKAC';
        $filepath = 'some/fake/file';
        $line = 666;

        // Mock Xylophone and Exceptions and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass'), array(), '', false);
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $XY->expects($this->once())->method('loadClass')->with($this->equalTo('Exceptions'), $this->equalTo('core'))->
            will($this->returnValue($exc));
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
     */
    public function testExceptionHandlerIgnore()
    {
        // Mock Xylophone and Exceptions and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array('loadClass'), array(), '', false);
        $exc = $this->getMock('Xylophone\core\Output', array('showPhpError', 'logException'), array(), '', false);
        $XY->expects($this->never())->method('loadClass');
        $exc->expects($this->never())->method('showPhpError');
        $exc->expects($this->never())->method('logException');

        // Get reporting level and remove our severity
        $severity = E_WARNING;
        $errors = error_reporting();
        error_reporting($errors ^ $severity);

        // Call exceptionHandler() with the removed severity
        $XY->exceptionHandler($severity, 'foo', 'bar', 42);

        // Clean up
        error_reporting($errors);
    }
}

