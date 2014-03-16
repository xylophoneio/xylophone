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
 * ExitException Unit Test
 *
 * @package Xylophone
 */
class ExitExceptionTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone and ExitException
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $exit = $this->getMock('Xylophone\core\ExitException', null, array(), '', false);

        // Set up args
        $message = 'something went wrong';
        $code = 13;
        $response = 501;

        // Check defaults
        $this->assertEquals(500, $exit->response);
        $this->assertEquals('Internal Server Error', $exit->header);

        // Set up buffer level
        $XY->init_ob_level = ob_get_level();
        ob_start();

        // Call __construct() and verify results
        $exit->__construct($message, $code, $response);
        $this->assertEquals($message, $exit->getMessage());
        $this->assertEquals($code, $exit->getCode());
        $this->assertEquals($response, $exit->response);
        $this->assertEquals(Xylophone\core\Xylophone::$status_codes[$response], $exit->header);
        $this->assertEquals($XY->init_ob_level, ob_get_level());
    }

    /**
     * Test __construct() with a header message
     */
    public function testConstructHeader()
    {
        global $XY;

        // Mock Xylophone and ExitException
        $XY = new stdClass();
        $exit = $this->getMock('Xylophone\core\ExitException', null, array(), '', false);

        // Set up args
        $message = 'some message';
        $code = 42;
        $response = 503;
        $header = 'Oops';

        // Set up buffer level
        $XY->init_ob_level = ob_get_level();

        // Call __construct() and verify results
        $exit->__construct($message, $code, $response, $header);
        $this->assertEquals($response, $exit->response);
        $this->assertEquals($header, $exit->header);
    }

    /**
     * Test getHeader()
     */
    public function testGetHeader()
    {
        // Mock ExitException and set header
        $exit = $this->getMock('Xylophone\core\ExitException', null, array(), '', false);
        $exit->header = 'HTTP/1.1 123 ABC Error';

        // Call getHeader() and verify result
        $this->assertEquals($exit->header, $exit->getHeader());
    }

    /**
     * Test getResponse()
     */
    public function testGetResponse()
    {
        // Mock ExitException and set response
        $exit = $this->getMock('Xylophone\core\ExitException', null, array(), '', false);
        $exit->response = 11;

        // Call getResponse() and verify result
        $this->assertEquals($exit->response, $exit->getResponse());
    }
}

