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
 * Utf8 Unit Test
 *
 * @package Xylophone
 */
class Utf8Test extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Utf8
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $utf8 = $this->getMock('Xylophone\core\Utf8', null, array(), '', false);

        // Set up calls
        $charset = 'UTF-8';
        $XY->config['charset'] = $charset;
        $XY->logger->expects($this->once())->method('debug');

        // Get environment info
        $mb_enabled = extension_loaded('mbstring');
        $mb_enabled && mb_internal_encoding('ASCII');
        $iconv_enabled = function_exists('iconv');

        // Call __construct() and verify results
        $utf8->__construct();
        $this->assertEquals($mb_enabled, $utf8->mb_enabled);
        $this->assertTrue(!$mb_enabled || mb_internal_encoding() == $charset);
        $this->assertEquals($iconv_enabled, $utf8->iconv_enabled);
        $this->assertEquals($mb_enabled && $iconv_enabled, $utf8->enabled);
    }

    /**
     * Test cleanString() while disabled
     */
    public function testCleanStringDisabled()
    {
        // Mock Utf8 and set up call
        $utf8 = $this->getMock('Xylophone\core\Utf8', array('isAscii'), array(), '', false);
        $utf8->expects($this->never())->method('isAscii');
        $utf8->iconv_enabled = false;

        // Set up arg and call cleanString()
        $str = chr(0xC4).chr(0xA7).chr(0xC7).chr(0xCC).chr(0xCC);
        $this->assertEquals($str, $utf8->cleanString($str));
    }

    /**
     * Test cleanString() with an "ASCII" string
     */
    public function testCleanStringAscii()
    {
        // Mock Utf8 and set up call
        $utf8 = $this->getMock('Xylophone\core\Utf8', array('isAscii'), array(), '', false);
        $utf8->expects($this->once())->method('isAscii')->will($this->returnValue(true));
        $utf8->iconv_enabled = true;

        // Set up arg and call cleanString()
        $str = chr(0xC4).chr(0xA7).chr(0xC7).chr(0xCC).chr(0xCC);
        $this->assertEquals($str, $utf8->cleanString($str));
    }

    /**
     * Test cleanString()
     */
    public function testCleanString()
    {
        // Mock Utf8 and set up call
        $utf8 = $this->getMock('Xylophone\core\Utf8', array('isAscii'), array(), '', false);
        $utf8->expects($this->once())->method('isAscii')->will($this->returnValue(false));

        // Set up args and call cleanString()
        $str = chr(0xC4).chr(0xA7).chr(0xC7).chr(0xCC).chr(0xCC);
        $clean = 'Ä§';
        $this->assertEquals($clean, $utf8->cleanString($str));
    }

    /**
     * Test safeAsciiForXml()
     */
    public function testSafeAsciiForXml()
    {
        global $XY;

        // Mock Xylophone, Output, and Utf8
        $XY = new stdClass();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $utf8 = $this->getMock('Xylophone\core\Utf8', null, array(), '', false);

        // Set up arg and call
        $str = 'test_string';
        $XY->output->expects($this->once())->method('removeInvisibleCharacters')->with($this->equalTo($str));

        // Call safeAsciiForXml()
        $utf8->safeAsciiForXml($str);
    }

    /**
     * Test convertToUtf8()
     */
    public function testConvertToUtf8()
    {
        // Mock Utf8
        $utf8 = $this->getMock('Xylophone\core\Utf8', null, array(), '', false);

        // Set up args
        $encoding = 'WINDOWS-1251';
        $str = 'òåñò';
        $clean = 'Ñ‚ÐµÑÑ‚';

        // Call convertToUtf8() and verify results
        $utf8->iconv_enabled = false;
        $utf8->mb_enabled = false;
        $this->assertFalse($utf8->convertToUtf8($str, $encoding));
        if (function_exists('iconv')) {
            // Test iconv conversion
            $utf8->iconv_enabled = true;
            $this->assertEquals($clean, $utf8->convertToUtf8($str, $encoding));
        }
        if (extension_loaded('mbstring')) {
            // Test mb conversion
            $utf8->iconv_enabled = false;
            $utf8->mb_enabled = true;
            mb_internal_encoding('UTF-8');
            $this->assertEquals($clean, $utf8->convertToUtf8($str, $encoding));
        }
    }

    /**
     * Test isAscii()
     */
    public function testIsAscii()
    {
        // Mock Utf8
        $utf8 = $this->getMock('Xylophone\core\Utf8', null, array(), '', false);

        // Call isAscii() with ASCII and not
        $this->assertTrue($utf8->isAscii('ascii string'));
        $this->assertFalse($utf8->isAscii(chr(0xF0).chr(0xBA)));
    }
}

