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
 * licensing@xylophone.io so we can send you a copy immediately.
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
 * Router Unit Test
 *
 * @package Xylophone
 */
class RouterTest extends XyTestCase
{
    /**
     * Test __construct() with triggers
     */
    public function testConstructTrigger()
    {
        global $XY;

        // Mock Xylophone, Config, URI, Logger, and Router
        $XY = new stdClass();
        $XY->config = $this->getMock('Xylophone\core\Config', array('get', 'offsetGet'), array(), '', false);
        $XY->uri = $this->getMock('Xylophone\core\Uri', array('filterUri'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $router = $this->getMock('Xylophone\core\Router', array('validateRoute'), array(), '', false);

        // Set up args
        $dir_trig = 'd';
        $ctlr_trig = 'c';
        $func_trig = 'm';
        $dir_arg = 'some/dir';
        $ctlr_arg = 'testctlr';
        $func_arg = 'test';
        $route = array($dir_arg, $ctlr_arg, $func_arg);
        $_GET[$dir_trig] = $dir_arg;
        $_GET[$ctlr_trig] = $ctlr_arg;
        $_GET[$func_trig] = $func_arg;

        // Set up calls
        $XY->config->expects($this->once())->method('get')->
            with($this->equalTo('routes.php'), $this->equalTo('route'))->
            will($this->returnValue(false));
        $XY->config->expects($this->exactly(4))->method('offsetGet')->will($this->returnValueMap(array(
            array('controller_trigger', $ctlr_trig),
            array('enable_query_strings', true),
            array('directory_trigger', $dir_trig),
            array('function_trigger', $func_trig)
        )));
        $XY->uri->expects($this->exactly(3))->method('filterUri')->will($this->returnArgument(0));
        $XY->logger->expects($this->once())->method('debug');
        $router->expects($this->once())->method('validateRoute')->with($this->equalTo($route))->
            will($this->returnArgument(0));

        // Call __construct() and verify results
        $router->__construct();
        $this->assertEmpty($router->routes);
        $this->assertFalse($router->default_controller);
        $this->assertEquals($route, $router->route);
    }

    /**
     * Test __construct() with bad triggers
     */
    public function testConstructBadTrigger()
    {
        global $XY;

        // Mock Xylophone, Config, URI, and Router
        $XY = $this->getMock('Xylophone\core\Xylophone', array('show404'), array(), '', false);
        $XY->config = $this->getMock('Xylophone\core\Config', array('get', 'offsetGet'), array(), '', false);
        $XY->uri = $this->getMock('Xylophone\core\Uri', array('filterUri'), array(), '', false);
        $router = $this->getMock('Xylophone\core\Router', array('validateRoute'), array(), '', false);

        // Set up args
        $ctlr_trig = 'ctl';
        $func_trig = 'fnc';
        $ctlr_arg = 'badctlr';
        $func_arg = 'index';
        $route = array($ctlr_arg, $func_arg);
        $_GET[$ctlr_trig] = $ctlr_arg;
        $_GET[$func_trig] = $func_arg;

        // Set up calls
        $XY->config->expects($this->once())->method('get')->
            with($this->equalTo('routes.php'), $this->equalTo('route'))->
            will($this->returnValue(false));
        $XY->config->expects($this->exactly(4))->method('offsetGet')->will($this->returnValueMap(array(
            array('controller_trigger', $ctlr_trig),
            array('enable_query_strings', true),
            array('directory_trigger', null),
            array('function_trigger', $func_trig)
        )));
        $XY->uri->expects($this->exactly(2))->method('filterUri')->will($this->returnArgument(0));
        $router->expects($this->once())->method('validateRoute')->with($this->equalTo($route))->
            will($this->returnValue(false));
        $XY->expects($this->once())->method('show404')->with($this->equalTo($ctlr_arg.'/'.$func_arg))->
            will($this->throwException(new InvalidArgumentException()));

        // Call __construct() and verify results
        $this->setExpectedException('InvalidArgumentException');
        $router->__construct();
        $this->assertFalse($router->route);
    }

    /**
     * Test __construct() with a bad route
     */
    public function testConstructFail()
    {
        global $XY;

        // Mock Xylophone, Config, URI, and Router
        $XY = $this->getMock('Xylophone\core\Xylophone', array('show404'), array(), '', false);
        $XY->config = $this->getMock('Xylophone\core\Config', array('get', 'offsetGet'), array(), '', false);
        $XY->uri = $this->getMock('Xylophone\core\Uri', array('load'), array(), '', false);
        $router = $this->getMock('Xylophone\core\Router', array('parseRoutes', 'validateRoute'), array(), '', false);

        // Set up args
        $route = 'dir/ctlr/func/arg';
        $XY->uri->segments = $route;

        // Set up calls
        $XY->config->expects($this->once())->method('get')->
            with($this->equalTo('routes.php'), $this->equalTo('route'))->
            will($this->returnValue(false));
        $XY->config->expects($this->exactly(2))->method('offsetGet')->will($this->returnValueMap(array(
            array('controller_trigger', 'c'),
            array('enable_query_strings', false)
        )));
        $XY->uri->expects($this->once())->method('load');
        $router->expects($this->once())->method('parseRoutes')->with($this->equalTo($route))->
            will($this->returnArgument(0));
        $router->expects($this->once())->method('validateRoute')->with($this->equalTo($route))->
            will($this->returnValue(false));
        $XY->expects($this->once())->method('show404')->with($this->equalTo($route))->
            will($this->throwException(new InvalidArgumentException()));

        // Call __construct() and verify results
        $this->setExpectedException('InvalidArgumentException');
        $router->__construct();
        $this->assertFalse($router->route);
    }

    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Config, URI, Logger, and Router
        $XY = new stdClass();
        $XY->config = $this->getMock('Xylophone\core\Config', array('get', 'offsetGet'), array(), '', false);
        $XY->uri = $this->getMock('Xylophone\core\Uri', array('load', 'setRuriString'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $router = $this->getMock('Xylophone\core\Router', array('parseRoutes', 'validateRoute'), array(), '', false);

        // Set up args
        $rname = 'foobar';
        $rpath = 'index';
        $dctlr = 'main';
        $dfunc = 'method';
        $routes = array($rname => $rpath, 'default_controller' => $dctlr.'/'.$dfunc);
        $route = 'dir/ctlr/func/arg';
        $XY->uri->segments = $route;

        // Set up calls
        $XY->config->expects($this->once())->method('get')->
            with($this->equalTo('routes.php'), $this->equalTo('route'))->
            will($this->returnValue($routes));
        $XY->config->expects($this->exactly(2))->method('offsetGet')->will($this->returnValueMap(array(
            array('controller_trigger', 'c'),
            array('enable_query_strings', false)
        )));
        $XY->uri->expects($this->once())->method('load');
        $XY->uri->expects($this->once())->method('setRuriString')->with($this->equalTo($route));
        $XY->logger->expects($this->once())->method('debug');
        $router->expects($this->once())->method('parseRoutes')->with($this->equalTo($route))->
            will($this->returnArgument(0));
        $router->expects($this->once())->method('validateRoute')->with($this->equalTo($route))->
            will($this->returnArgument(0));

        // Call __construct() and verify results
        $router->__construct();
        $this->assertEquals(array($rname => $rpath), $router->routes);
        $this->assertEquals(array($dctlr, $dfunc), $router->default_controller);
        $this->assertEquals($route, $router->route);
    }

    /**
     * Test parseRoutes() with a literal match
     */
    public function testParseRoutesLiteral()
    {
        // Set up args
        $rname = 'foo/bar';
        $rpath = 'baz';

        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->routes = array($rname => $rpath);

        // Call parseRoutes() and verify result
        $this->assertEquals($rpath, $router->parseRoutes($rname));
    }

    /**
     * Test parseRoutes() with no match
     */
    public function testParseRoutesNone()
    {
        // Set up args
        $seg1 = 'seg';
        $seg2 = 'ment';

        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->routes = array();

        // Call parseRoutes() and verify result
        $this->assertEquals($seg1.'/'.$seg2, $router->parseRoutes(array($seg1, $seg2)));
    }

    /**
     * Test parseRoutes() with :any
     */
    public function testParseRoutesAny()
    {
        // Set up args
        $arg = 'foo';
        $from = 'baz/';
        $to = 'index/';

        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->routes = array($from.'(:any)' => $to.'$1');

        // Call parseRoutes() and verify result
        $this->assertEquals($to.$arg, $router->parseRoutes($from.$arg));
    }

    /**
     * Test parseRoutes() with :num
     */
    public function testParseRoutesNum()
    {
        // Set up args
        $arg1 = '1';
        $arg2 = '2';
        $from = 'red/';
        $to = 'blue/';

        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->routes = array($from.'(:num)/(:num)' => $to.'$1/$2');

        // Call parseRoutes() and verify result
        $this->assertEquals($to.$arg1.'/'.$arg2, $router->parseRoutes($from.$arg1.'/'.$arg2));
    }

    /**
     * Test parseRoutes() with a callback
     */
    public function testParseRoutesCallback()
    {
        // Set up args
        $arg1 = 'first';
        $arg2 = 'second';
        $uri = 'callme/';
        $rcall = function($a, $b, $c = '123') {
            return implode('|', array($a, $b, $c));
        };

        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->routes = array($uri.'(fi.*t)/(se.*d)' => $rcall);

        // Call parseRoutes() and verify result
        $this->assertEquals($arg1.'|'.$arg2.'|123', $router->parseRoutes($uri.$arg1.'/'.$arg2));
    }

    /**
     * Test validateRoute() with an empty route
     */
    public function testValidateRouteEmpty()
    {
        // Mock Router and set up call
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);
        $router->default_controller = false;

        // Call validateRoute() and verify result
        $this->assertFalse($router->validateRoute(''));
    }

    /**
     * Test validateRoute() with an invalid route
     */
    public function testValidateRouteNotFound()
    {
        global $XY;

        // Set up filesystem
        $this->vfsInit();

        // Mock Xylophone and Router
        $XY = new stdClass();
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up calls
        $XY->ns_paths = array('' => $this->vfs_app_path.'/');
        $router->default_controller = false;

        // Call validateRoute() and verify result
        $this->assertFalse($router->validateRoute('bad/route'));
    }

    /**
     * Test validateRoute() with a default controller
     */
    public function testValidateRouteDefault()
    {
        global $XY;

        // Set up args
        $route = 'subdir';
        $default = 'Main';
        $ns = '';
        $ctlrs = 'controllers/';
        $retval = 'good';

        // Set up filesystem
        $this->vfsInit();
        $this->vfsCreate($ctlrs.$route.'/'.$default.'.php', 'test', $this->vfs_app_dir);

        // Mock Xylophone and Router
        $XY = new stdClass();
        $router = $this->getMock('Xylophone\core\Router', array('makeStack'), array(), '', false);

        // Set up calls
        $XY->ns_paths = array($ns => $this->vfs_app_path.'/');
        $router->default_controller = array($default);
        $router->expects($this->once())->method('makeStack')->
            with($this->equalTo($ns), $this->equalTo($ctlrs.$route.'/'), $this->equalTo($default), $this->isEmpty())->
            will($this->returnValue($retval));

        // Call validateRoute() and verify result
        $this->assertEquals($retval, $router->validateRoute($route));
    }

    /**
     * Test validateRoute() with subdirectories
     */
    public function testValidateRouteSubdirs()
    {
        global $XY;

        // Set up args
        $sub1 = 'module/';
        $sub2 = 'subdir/';
        $ctlr= 'Main';
        $method = 'foo';
        $ns = 'testspace';
        $ctlrs = 'controllers/';
        $retval = 'ok';

        // Set up filesystem
        $this->vfsInit();
        $this->vfsMkdir($sub1.$ctlrs.$sub2.$ctlr.'/'.$method, $this->vfs_app_dir);
        $ns_dir = $this->vfsMkdir('third_party', $this->vfs_base_dir);
        $this->vfsCreate($sub1.$ctlrs.$sub2.$ctlr.'.php', 'controller', $ns_dir);

        // Mock Xylophone and Router
        $XY = new stdClass();
        $router = $this->getMock('Xylophone\core\Router', array('makeStack'), array(), '', false);

        // Set up calls
        $XY->ns_paths = array('' => $this->vfs_app_path.'/', $ns => $ns_dir->url().'/');
        $router->default_controller = array('default');
        $router->expects($this->once())->method('makeStack')->with($this->equalTo($ns),
            $this->equalTo($sub1.$ctlrs.$sub2), $this->equalTo($ctlr), $this->equalTo(array($method)))->
            will($this->returnValue($retval));

        // Call validateRoute() and verify result
        $this->assertEquals($retval, $router->validateRoute($sub1.$sub2.$ctlr.'/'.$method));
    }

    /**
     * Test makeStack()
     */
    public function testMakeStack()
    {
        // Mock Router
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up args
        $ns = 'Namespace';
        $path = 'path';
        $class = 'Class';
        $method = '';
        $stack = array('path' => '', 'class' => $ns.'\\'.$path.'\\'.$class, 'method' => 'index', 'args' => array());

        // Call makeStack() and verify results
        $this->assertEquals($stack, $router->makeStack($ns, $path, $class, $method));
    }

    /**
     * Test makeStack() with arguments
     */
    public function testMakeStackArgs()
    {
        // Mock Router
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up args
        $ns = '';
        $path = 'dir';
        $class = 'Main';
        $method = 'method';
        $arg = 'arg';
        $stack = array('path' => $path, 'class' => $class, 'method' => $method, 'args' => array($arg));

        // Call makeStack() and verify results
        $this->assertEquals($stack, $router->makeStack($ns, $path, $class, array($method, $arg)));
    }

    /**
     * Test getErrorRoute() with a bad route
     */
    public function testGetErrorRouteNone()
    {
        // Mock Router
        $router = $this->getMock('Xylophone\core\Router', null, array(), '', false);

        // Set up args
        $template = 'no_error';
        $router->routes = array();

        // Call getErrorRoute() and verify result
        $this->assertFalse($router->getErrorRoute($template));
    }

    /**
     * Test getErrorRoute()
     */
    public function testGetErrorRoute()
    {
        // Mock Router
        $router = $this->getMock('Xylophone\core\Router', array('validateRoute'), array(), '', false);

        // Set up args
        $template = 'test_error';
        $route = 'some/route';
        $retval = 'stack';

        // Set up calls
        $router->routes = array($template.'_override' => $route);
        $router->expects($this->once())->method('validateRoute')->with($this->equalTo($route))->
            will($this->returnValue($retval));

        // Call getErrorRoute() and verify result
        $this->assertEquals($retval, $router->getErrorRoute($template));
    }
}

