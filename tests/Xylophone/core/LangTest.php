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
 * Lang Unit Test
 *
 * @package Xylophone
 */
class LangTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Logger, and lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up call and call __construct()
        $XY->logger->expects($this->once())->method('debug');
        $lang->__construct();
    }

    /**
     * Test load() on loaded file
     */
    public function testLoadLoaded()
    {
        global $XY;

        // Set up arg
        $langfile = 'loaded';

        // Mock Xylophone, Config, and Lang
        $XY = new stdClass();
        $XY->config = array();
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->config['language'] = '';
        $lang->is_loaded[$langfile.'_lang.php'] = 'english';

        // Call load() and verify result
        $this->assertTrue($lang->load($langfile.'.php'));
    }

    /**
     * Test load() for a file not found
     */
    public function testLoadNotFound()
    {
        global $XY;

        // Set up args
        $langfile = 'not_found_lang.php';
        $idiom = 'latin';

        // Mock Xylophone, Config, and Lang
        $XY = $this->getMock('Xylophone\core\Xylophone', array('showError'), array(), '', false);
        $XY->config = array();
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->config['language'] = $idiom;
        $XY->system_path = '/usr/share/Xylophone/system/';
        $XY->expects($this->once())->method('showError')->
            with($this->equalTo('Unable to load the requested language file: language/'.$idiom.'/'.$langfile))->
            will($this->throwException(new InvalidArgumentException));
        $this->setExpectedException('InvalidArgumentException');

        // Call load()
        $lang->load($langfile);
    }

    /**
     * Test load() with an empty alt path
     */
    public function testLoadAlt()
    {
        global $XY;

        // Set up args
        $langfile = 'alt_lang.php';
        $idiom = 'klingon';

        // Set up filesystem
        $this->vfsInit();
        $alt_dir = $this->vfsMkdir('altlang', $this->vfs_base_dir);
        $alt_path = $alt_dir->url().'/';
        $this->vfsCreate('language/'.$idiom.'/'.$langfile, '<?php $lang = false;', $alt_dir);

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = '/usr/share/Xylophone/system/';
        $XY->logger->expects($this->once())->method('error')->
            with($this->equalTo('Language file contains no data: language/'.$idiom.'/'.$langfile));

        // Call load() and verify result
        $this->assertFalse($lang->load($langfile, $idiom, false, false, $alt_path));
    }

    /**
     * Test load() returning an empty ns path
     */
    public function testLoadNsReturn()
    {
        global $XY;

        // Set up args
        $langfile = 'space_lang.php';
        $idiom = 'vulcan';

        // Set up filesystem
        $this->vfsInit();
        $ns_dir = $this->vfsMkdir('space', $this->vfs_base_dir);
        $ns_path = $ns_dir->url().'/';
        $this->vfsCreate('language/'.$idiom.'/'.$langfile, '<?php $lang = false;', $ns_dir);

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = '/usr/share/Xylophone/system/';
        $XY->ns_paths = array($ns_path);
        $XY->logger->expects($this->once())->method('error');

        // Call load() and verify result
        $this->assertEquals(array(), $lang->load($langfile, $idiom, true));
    }

    /**
     * Test load() returning a lang array
     */
    public function testLoadReturn()
    {
        global $XY;

        // Set up args
        $langfile = 'test_lang.php';
        $arg1 = 'foo';
        $val1 = 'bar';
        $arg2 = 'boo';
        $val2 = 'hoo';

        // Set up filesystem
        $this->vfsInit();
        $content = '<?php $lang[\''.$arg1.'\'] = \''.$val1.'\'; $lang[\''.$arg2.'\'] = \''.$val2.'\';';
        $this->vfsCreate('language/english/'.$langfile, $content, $this->vfs_sys_dir);

        // Mock Xylophone and Lang
        $XY = new stdClass();
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = $this->vfs_sys_path.'/';
        $XY->ns_paths = array();

        // Call load() and verify result
        $this->assertEquals(array($arg1 => $val1, $arg2 => $val2), $lang->load($langfile, '', true));
    }

    /**
     * Test load()
     */
    public function testLoad()
    {
        global $XY;

        // Set up args
        $langfile = 'test_lang.php';
        $idiom = 'seussian';
        $arg1 = 'one';
        $val1 = 'two';
        $arg2 = 'red';
        $val2 = 'blue';

        // Set up filesystem
        $this->vfsInit();
        $content = '<?php $lang[\''.$arg1.'\'] = \''.$val1.'\'; $lang[\''.$arg2.'\'] = \''.$val2.'\';';
        $this->vfsCreate('language/'.$idiom.'/'.$langfile, $content, $this->vfs_sys_dir);

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = $this->vfs_sys_path.'/';
        $XY->ns_paths = array();
        $XY->logger->expects($this->once())->method('debug')->
            with($this->equalTo('Language file loaded: language/'.$idiom.'/'.$langfile));
        $lang->language = array($arg1 => '1');

        // Call load() and verify results
        $this->assertTrue($lang->load($langfile, $idiom));
        $this->assertArrayHasKey($langfile, $lang->is_loaded);
        $this->assertEquals($idiom, $lang->is_loaded[$langfile]);
        $this->assertEquals(array($arg1 => $val1, $arg2 => $val2), $lang->language);
    }

    /**
     * Test load() with a bad array
     */
    public function testLoadBadArray()
    {
        global $XY;

        // Set up args
        $file1 = 'one_lang.php';
        $file2 = 'bad_lang.php';

        // Set up filesystem
        $this->vfsInit();
        $this->vfsCreate('language/english/'.$file2, '<?php $lang = false;', $this->vfs_sys_dir);

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = $this->vfs_sys_path.'/';
        $XY->ns_paths = array();
        $XY->logger->expects($this->once())->method('error')->with($this->stringContains($file2));
        $lang->is_loaded[$file1] = 'english';

        // Call load()
        $this->assertFalse($lang->load(array($file1, $file2)));
    }

    /**
     * Test load() with an array return
     */
    public function testLoadArrayReturn()
    {
        global $XY;

        // Set up args
        $file1 = 'first_lang.php';
        $file2 = 'second_lang.php';
        $arg1 = 'first';
        $val1 = 'one';
        $arg2 = 'second';
        $val2 = 'two';

        // Set up filesystem
        $this->vfsInit();
        $this->vfsCreate('language/english/'.$file1, '<?php $lang[\''.$arg1.'\'] = \''.$val1.'\';', $this->vfs_sys_dir);
        $this->vfsCreate('language/english/'.$file2, '<?php $lang[\''.$arg2.'\'] = \''.$val2.'\';', $this->vfs_sys_dir);

        // Mock Xylophone and Lang
        $XY = new stdClass();
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->system_path = $this->vfs_sys_path.'/';
        $XY->ns_paths = array();

        // Call load() and verify results
        $retval = array($file1 => array($arg1 => $val1), $file2 => array($arg2 => $val2));
        $this->assertEquals($retval, $lang->load(array($file1, $file2), '', true));
    }

    /**
     * Test load() with a loaded array
     */
    public function testLoadArrayLoaded()
    {
        global $XY;

        // Set up args
        $file1 = 'load1_lang.php';
        $file2 = 'load2_lang.php';

        // Mock Xylophone and Lang
        $XY = new stdClass();
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up call
        $lang->is_loaded = array($file1 => 'english', $file2 => 'english');

        // Call load() and verify results
        $this->assertTrue($lang->load(array($file1, $file2)));
    }

    /**
     * Test line() with an empty line
     */
    public function testLineEmpty()
    {
        global $XY;

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->logger->expects($this->once())->method('error')->
            with($this->equalTo('Could not find the language line ""'));

        // Call line() and verify result
        $this->assertFalse($lang->line(''));
    }

    /**
     * Test line() not found
     */
    public function testLineNotFound()
    {
        global $XY;

        // Set up arg
        $line = 'not_found';

        // Mock Xylophone, Logger, and Lang
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up calls
        $XY->logger->expects($this->once())->method('error')->with($this->stringContains($line));

        // Call line() and verify result
        $this->assertFalse($lang->line($line));
    }

    /**
     * Test line()
     */
    public function testLine()
    {
        // Set up args
        $line = 'my_value';
        $val = '42';

        // Mock Lang
        $lang = $this->getMock('Xylophone\core\Lang', null, array(), '', false);

        // Set up call
        $lang->language[$line] = $val;

        // Call line() and verify result
        $this->assertEquals($val, $lang->line($line));
    }
}

