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
 * Model Unit Test
 *
 * @package Xylophone
 */
class ModelTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone and Model
        $XY = (object)array('core' => 'object');
        $model = $this->getMock('Xylophone\core\Model', null, array(), '', false);

        // Call __construct() and verify result
        $model->__construct();
        $this->assertSame($XY, $model->XY);
        $this->assertEmpty($model->config);
        $this->assertEmpty($model->db);
    }

    /**
     * Test __construct() with a config param
     */
    public function testConstructConfig()
    {
        global $XY;

        // Set up arg
        $config = 'foo';

        // Mock Xylophone and Model
        $XY = (object)array('core' => 'object');
        $model = $this->getMock('Xylophone\core\Model', null, array(), '', false);

        // Call __construct() and verify result
        $model->__construct($config);
        $this->assertSame($XY, $model->XY);
        $this->assertEquals(array($config), $model->config);
    }

    /**
     * Test __construct() with a db connection
     */
    public function testConstructDb()
    {
        global $XY;

        // Set up args
        $db = (object)array();
        $config = array('enable' => true, 'db' => $db);

        // Mock Xylophone and Model
        $XY = new stdClass();
        $model = $this->getMock('Xylophone\core\Model', null, array(), '', false);

        // Call __construct() and verify result
        $model->__construct($config);
        $this->assertEquals($config, $model->config);
        $this->assertSame($db, $model->db);
    }

    /**
     * Test __get()
     */
    public function testGet()
    {
        // Set up args
        $key = 'someobj';
        $val = (object)array('mykey' => 'myval');

        // Mock Model and Xylophone
        $model = $this->getMock('Xylophone\core\Model', null, array(), '', false);
        $model->XY = (object)array($key => $val);

        // Verify identity and non-existent member
        $this->assertSame($val, $model->$key);
        $this->assertNull($model->nonexistent);
    }
}

