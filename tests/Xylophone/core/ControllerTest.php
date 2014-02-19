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
 * Controller Unit Test
 *
 * @package Xylophone
 */
class ControllerTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone and Controller
        $XY = (object)array('core' => 'object');
        $controller = $this->getMock('Xylophone\core\Controller', null, array(), '', false);

        // Call __construct() and verify result
        $controller->__construct();
        $this->assertSame($XY, $controller->XY);
    }

    /**
     * Test __get()
     */
    public function testGet()
    {
        // Set up args
        $key = 'testobj';
        $val = (object)array('member' => 'var');

        // Mock Controller and Xylophone
        $controller = $this->getMock('Xylophone\core\Controller', null, array(), '', false);
        $controller->XY = (object)array($key => $val);

        // Verify identity and non-existent member
        $this->assertSame($val, $controller->$key);
        $this->assertNull($controller->nonexistent);
    }
}

