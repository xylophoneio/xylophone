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
 * Hooks Unit Test
 *
 * @package Xylophone
 */
class HooksTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Set up args
        $hks = array('first_hook' => 'some/function', 'second_hook' => 'other/function');

        // Mock Xylophone, Hooks, Config, and Logger
        $XY = new stdClass();
        $XY->config = $this->getMock('Xylophone\core\Config', array('offsetGet', 'get'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);

        // Set up calls
        $XY->config->expects($this->once())->method('offsetGet')->with($this->equalTo('enable_hooks'))->
            will($this->returnValue(true));
        $XY->config->expects($this->once())->method('get')->with($this->equalTo('hooks.php'), $this->equalTo('hook'))->
            will($this->returnValue($hks));
        $XY->logger->expects($this->once())->method('debug');

        // Call __construct() and verify results
        $hooks->__construct();
        $this->assertEquals($hks, $hooks->hooks);
        $this->assertTrue($hooks->enabled);
    }

    /**
     * Test __construct() disabled
     */
    public function testConstructDisabled()
    {
        global $XY;

        // Mock Xylophone, Hooks, Config, and Logger
        $XY = new stdClass();
        $XY->config = $this->getMock('Xylophone\core\Config', array('offsetGet'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);

        // Set up calls
        $XY->config->expects($this->once())->method('offsetGet')->with($this->equalTo('enable_hooks'))->
            will($this->returnValue(false));
        $XY->logger->expects($this->once())->method('debug');

        // Call __construct() and verify results
        $hooks->__construct();
        $this->assertEmpty($hooks->hooks);
        $this->assertFalse($hooks->enabled);
    }

    /**
     * Test __construct() with no hooks
     */
    public function testConstructNone()
    {
        global $XY;

        // Mock Xylophone, Hooks, Config, and Logger
        $XY = new stdClass();
        $XY->config = $this->getMock('Xylophone\core\Config', array('offsetGet', 'get'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);

        // Set up calls
        $XY->config->expects($this->once())->method('offsetGet')->with($this->equalTo('enable_hooks'))->
            will($this->returnValue(true));
        $XY->config->expects($this->once())->method('get')->with($this->equalTo('hooks.php'), $this->equalTo('hook'))->
            will($this->returnValue(false));
        $XY->logger->expects($this->once())->method('debug');

        // Call __construct() and verify results
        $hooks->__construct();
        $this->assertEmpty($hooks->hooks);
        $this->assertFalse($hooks->enabled);
    }

    /**
     * Test callHook() while disabled
     */
    public function testCallHookDisabled()
    {
        // Mock Hooks
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);
        $hooks->enabled = false;

        // Call callHook() and verify result
        $this->assertFalse($hooks->callHook('some_hook'));
    }

    /**
     * Test callHook() with a hook not set
     */
    public function testCallHookNotSet()
    {
        // Mock Hooks
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);
        $hooks->enabled = true;

        // Call callHook() and verify result
        $this->assertFalse($hooks->callHook('not_hook'));
    }

    /**
     * Test callHook() while in progress
     */
    public function testCallHookInProgress()
    {
        global $XY;

        // Set up args
        $name = 'double_hook';
        $hook = 'hook/func';
        $retval = true;

        // Mock Xylophone, Loader, and Hooks
        $XY = new stdClass();
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);

        // Set up call and hook
        $XY->load->expects($this->once())->method('controller')->with($this->equalTo($hook))->will(
            $this->returnCallback(function ($arg) use ($hooks, $name, &$retval) {
                // Call recursively and capture return while in progress
                $retval = $hooks->callHook($name);
            }));
        $hooks->enabled = true;
        $hooks->hooks[$name] = $hook;

        // Call callHook() and verify result
        $this->assertTrue($hooks->callHook($name));
        $this->assertFalse($retval);
    }

    /**
     * Test callHook() with a multi-hook
     */
    public function testCallHookMulti()
    {
        global $XY;

        // Set up args
        $name = 'multi_hook';
        $hook1 = 'left';
        $hook2 = 'right';

        // Mock Xylophone, Loader, and Hooks
        $XY = new stdClass();
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $hooks = $this->getMock('Xylophone\core\Hooks', null, array(), '', false);

        // Set up calls and hooks
        $XY->load->expects($this->exactly(2))->method('controller')->will($this->returnValueMap(array(
            array($hook1, null),
            array($hook2, null)
        )));
        $hooks->enabled = true;
        $hooks->hooks[$name] = array($hook1, $hook2);

        // Call callHook() and verify result
        $this->assertTrue($hooks->callHook($name));
    }
}

