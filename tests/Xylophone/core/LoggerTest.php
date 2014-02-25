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
 * Logger Unit Test
 *
 * @package Xylophone
 */
class LoggerTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('logs', $this->vfs_app_dir);

        // Set up args
        $path = $dir->url().DIRECTORY_SEPARATOR;
        $thresh = 2;
        $format = 'M d, Y H:i:s';

        // Mock Xylophone, Config, and Logger
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isWritable'), array(), '', false);
        $XY->config = array();
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $XY->app_path = $this->vfs_app_path.'/';
        $XY->config['log_path'] = '';
        $XY->config['logfile_extension'] = '';
        $XY->config['log_threshold'] = $thresh;
        $XY->config['log_date_format'] = $format;
        $XY->expects($this->once())->method('isWritable')->with($this->equalTo($path))->will($this->returnValue(true));

        // Verify default date format
        $this->assertEquals('Y-m-d H:i:s', $logger->date_fmt);

        // Call __construct() and verify results
        $logger->__construct();
        $this->assertTrue($logger->enabled);
        $this->assertEquals($path, $logger->path);
        $this->assertEquals('php', $logger->file_ext);
        $this->assertEquals($thresh, $logger->threshold);
        $this->assertEquals($format, $logger->date_fmt);
    }

    /**
     * Test __construct() with no directory and none threshold
     */
    public function testConstructNone()
    {
        global $XY;

        // Set up filesystem
        $this->vfsInit();

        // Set up args
        $path = $this->vfs_base_path.'logs';
        $ext = 'log';

        // Mock Xylophone, Config, and Logger
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isWritable'), array(), '', false);
        $XY->config = array();
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $XY->config['log_path'] = $path;
        $XY->config['logfile_extension'] = $ext;
        $XY->config['log_threshold'] = 'none';
        $XY->config['log_date_format'] = '';
        $XY->expects($this->once())->method('isWritable')->will($this->returnValue(true));

        // Call __construct() and verify results
        $logger->__construct();
        $this->assertFalse($logger->enabled);
        $this->assertEquals($path.DIRECTORY_SEPARATOR, $logger->path);
        $this->assertEquals($ext, $logger->file_ext);
        $this->assertEquals(0, $logger->threshold);
    }

    /**
     * Test __construct() with unwritable dir and threshold translation
     */
    public function testConstructThresh()
    {
        global $XY;

        // Set up filesystem
        $this->vfsInit();

        // Set up args
        $path = $this->vfs_app_path.'mylogs'.DIRECTORY_SEPARATOR;

        // Mock Xylophone, Config, and Logger
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isWritable'), array(), '', false);
        $XY->config = array();
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $XY->config['log_path'] = $path;
        $XY->config['logfile_extension'] = '';
        $XY->config['log_threshold'] = 'critical';
        $XY->config['log_date_format'] = '';
        $XY->expects($this->once())->method('isWritable')->will($this->returnValue(false));

        // Call __construct() and verify results
        $logger->__construct();
        $this->assertFalse($logger->enabled);
        $this->assertEquals($path, $logger->path);
        $this->assertEquals(3, $logger->threshold);
    }

    /**
     * Test __construct() with threshold array
     */
    public function testConstructArray()
    {
        global $XY;

        // Set up filesystem and arg
        $this->vfsInit();
        $thresh = array('', '');

        // Mock Xylophone, Config, and Logger
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isWritable'), array(), '', false);
        $XY->config = array();
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $XY->config['log_path'] = '';
        $XY->config['logfile_extension'] = '';
        $XY->config['log_threshold'] = $thresh;
        $XY->config['log_date_format'] = '';
        $XY->expects($this->once())->method('isWritable')->will($this->returnValue(true));

        // Call __construct() and verify results
        $logger->__construct();
        $this->assertTrue($logger->enabled);
        $this->assertEquals(0, $logger->threshold);
        $this->assertEquals(array_flip($thresh), $logger->enabled_levels);
    }

    /**
     * Test log() when disabled
     */
    public function testLogDisabled()
    {
        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('logs', $this->vfs_base_dir);

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = false;
        $logger->path = $dir->url().DIRECTORY_SEPARATOR;
        $logger->file_ext = 'php';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Call log() and verify file isn't created
        $logger->log('emergency', 'message');
        $this->assertFileNotExists($file);
    }

    /**
     * Test log() with a bad level
     */
    public function testLogBadLevel()
    {
        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('logs', $this->vfs_base_dir);

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = true;
        $logger->path = $dir->url().DIRECTORY_SEPARATOR;
        $logger->file_ext = 'php';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Call log() and verify file isn't created
        $logger->log('badlevel', 'message');
        $this->assertFileNotExists($file);
    }

    /**
     * Test log() threshold
     */
    public function testLogThreshold()
    {
        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('logs', $this->vfs_base_dir);

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = true;
        $logger->threshold = 2;
        $logger->path = $dir->url().DIRECTORY_SEPARATOR;
        $logger->file_ext = 'php';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Call log() and verify file isn't created
        $logger->log('error', 'message');
        $this->assertFileNotExists($file);
    }

    /**
     * Test log() unable to create file
     */
    public function testLogNoCreate()
    {
        // Set up filesystem
        $this->vfsInit();

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = true;
        $logger->path = $this->vfs_base_path.DIRECTORY_SEPARATOR.'nologs'.DIRECTORY_SEPARATOR;
        $logger->file_ext = 'php';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Call log() and verify file isn't created
        $logger->log('emergency', 'message');
        $this->assertFileNotExists($file);
    }

    /**
     * Test log()
     */
    public function testLog()
    {
        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('testlogs', $this->vfs_app_dir);

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = true;
        $logger->path = $dir->url().DIRECTORY_SEPARATOR;
        $logger->file_ext = 'php';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Set up args
        $level = 'emergency';
        $msg = 'This is only a test';

        // Call log() and verify file contents
        $logger->log($level, $msg);
        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringStartsWith('<?php defined(\'BASEPATH\') OR exit', $content);
        $this->assertRegExp('/'.strtoupper($level).'/', $content);
        $this->assertRegExp('/--> '.$msg.'/', $content);
    }

    /**
     * Test log() with non-PHP file
     */
    public function testLogNonPhp()
    {
        // Set up filesystem
        $this->vfsInit();
        $dir = $this->vfsMkdir('barelogs', $this->vfs_app_dir);

        // Mock Logger
        $logger = $this->getMock('Xylophone\core\Logger', null, array(), '', false);

        // Set up calls
        $logger->enabled = true;
        $logger->path = $dir->url().DIRECTORY_SEPARATOR;
        $logger->file_ext = 'log';
        $file = $logger->path.'log-'.date('Y-m-d').'.'.$logger->file_ext;

        // Set up args
        $level = 'emergency';
        $msg = 'Call 5-0';

        // Call log() and verify file contents
        $logger->log($level, $msg);
        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringStartsWith(strtoupper($level), $content);
        $this->assertRegExp('/--> '.$msg.'/', $content);
    }

    /**
     * Test debug()
     */
    public function testDebug()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('debug'), $this->equalTo($msg), $this->equalTo($context));

        // Call debug()
        $logger->debug($msg, $context);
    }

    /**
     * Test info()
     */
    public function testInfo()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('info'), $this->equalTo($msg), $this->equalTo($context));

        // Call info()
        $logger->info($msg, $context);
    }

    /**
     * Test notice()
     */
    public function testNotice()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('notice'), $this->equalTo($msg), $this->equalTo($context));

        // Call notice()
        $logger->notice($msg, $context);
    }

    /**
     * Test warning()
     */
    public function testWarning()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('warning'), $this->equalTo($msg), $this->equalTo($context));

        // Call warning()
        $logger->warning($msg, $context);
    }

    /**
     * Test error()
     */
    public function testError()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('error'), $this->equalTo($msg), $this->equalTo($context));

        // Call error()
        $logger->error($msg, $context);
    }

    /**
     * Test critical()
     */
    public function testCritical()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('critical'), $this->equalTo($msg), $this->equalTo($context));

        // Call critical()
        $logger->critical($msg, $context);
    }

    /**
     * Test alert()
     */
    public function testAlert()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('alert'), $this->equalTo($msg), $this->equalTo($context));

        // Call alert()
        $logger->alert($msg, $context);
    }

    /**
     * Test emergency()
     */
    public function testEmergency()
    {
        // Set up args
        $msg = 'testmsg';
        $context = 'nothing';

        // Mock Logger and set up call
        $logger = $this->getMock('Xylophone\core\Logger', array('log'), array(), '', false);
        $logger->expects($this->once())->method('log')->
            with($this->equalTo('emergency'), $this->equalTo($msg), $this->equalTo($context));

        // Call emergency()
        $logger->emergency($msg, $context);
    }
}

