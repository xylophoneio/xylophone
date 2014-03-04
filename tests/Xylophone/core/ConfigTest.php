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
 * Config Unit Test
 *
 * @package Xylophone
 */
class ConfigTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        // Mock Config and set up call
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $cfg = array('base_url' => 'http://example.com/');
        $config->expects($this->once())->method('get')->
            with($this->equalTo('config.php'), $this->equalTo('config'))->
            will($this->returnValue($cfg));

        // Verify config and is_loaded are empty
        $this->assertEmpty($config->config);
        $this->assertEmpty($config->is_loaded);

        // Call __construct() (yes, after instantiation) and confirm base config
        $config->__construct();
        $this->assertEquals($cfg, $config->config);
    }

    /**
     * Test __construct() with no config
     */
    public function testConstructNoConfig()
    {
        global $XY;

        // Mock Xylophone and Config and set up calls
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $XY->init_ob_level = ob_get_level();
        $config->expects($this->once())->method('get')->will($this->returnValue(false));

        // Call __construct() (yes, again) and confirm exception
        $this->setExpectedException('Xylophone\core\ExitException', 'The configuration file does not exist.');
        $config->__construct();
    }

    /**
     * Test __construct() with a bad config
     */
    public function testConstructBadConfig()
    {
        global $XY;

        // Mock Xylophone and Config and set up call
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $XY->init_ob_level = ob_get_level();
        $config->expects($this->once())->method('get')->will($this->returnValue('/some/bad/path'));

        // Call __construct() (some more) and confirm exception
        $this->setExpectedException('Xylophone\core\ExitException', 'The configuration file is invalid.');
        $config->__construct();
    }

    /**
     * Test __construct() with no base_url
     */
    public function testConstructNoBase()
    {
        global $XY;

        // Set up args
        $path = '/test/path/';
        $host = 'testhost.com';
        $url = 'http://'.$host.$path;

        // Mock Xylophone and Config and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array(), array(), '', false);
        $config = $this->getMock('Xylophone\core\Config', array('get', 'setItem'), array(), '', false);
        $config->expects($this->once())->method('get')->will($this->returnValue(array()));
        $config->expects($this->once())->method('setItem')->
            with($this->equalTo('base_url'), $this->equalTo($url));
        $XY->expects($this->once())->method('isHttps')->will($this->returnValue(false));

        // Set server vars
        $http = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        $_SERVER['HTTP_HOST'] = $host;
        $script = isset($_SERVER['SCRIPT_NAME']) ?  $_SERVER['SCRIPT_NAME'] : null;
        $_SERVER['SCRIPT_NAME'] = $path.'file';

        // Call __construct() (ad nauseum)
        $config->__construct();

        // Clean up
        if ($script === null) {
            unset($_SERVER['SCRIPT_NAME']);
        }
        else {
            $_SERVER['SCRIPT_NAME'] = $script;
        }
        if ($http === null) {
            unset($_SERVER['HTTP_HOST']);
        }
        else {
            $_SERVER['HTTP_HOST'] = $http;
        }
    }

    /**
     * Test __construct() with no base_url and no host
     */
    public function testConstructNoBaseHost()
    {
        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('get', 'setItem'), array(), '', false);
        $config->expects($this->once())->method('get')->will($this->returnValue(array()));
        $config->expects($this->once())->method('setItem')->
            with($this->equalTo('base_url'), $this->equalTo('http://localhost/'));

        // Set server vars
        $http = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        unset($_SERVER['HTTP_HOST']);

        // Call __construct() (one last time)
        $config->__construct();

        // Clean up
        $http === null || $_SERVER['HTTP_HOST'] = $http;
    }

    /**
     * Test load()
     */
    public function testLoad()
    {
        global $XY;

        // Set up args
        $name = 'testcfg';
        $file = $name.'.php';
        $cfg = array('one' => '1', 'two' => '2', 'three' => '3');

        // Mock Xylophone and Config and Logger and set up calls
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->with($this->equalTo($file), $this->equalTo('config'))->
            will($this->returnValue($cfg));
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $XY->logger->expects($this->once())->method('debug')->
            with($this->equalTo('Config file loaded: '.$name.'.php'));

        // Call load() and verify results
        $this->assertTrue($config->load($file));
        $this->assertEquals($cfg, $config->config);
        $this->assertEquals(array($name), $config->is_loaded);
    }

    /**
     * Test load() with sections
     */
    public function testLoadSections()
    {
        global $XY;

        // Set up args
        $name = 'sectcfg';
        $file = $name.'.php';
        $cfg = array('red' => 'apple', 'blue' => 'berry');

        // Mock Xylophone and Config and Logger and set up calls
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->with($this->equalTo($file), $this->equalTo('config'))->
            will($this->returnValue($cfg));
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $XY->logger->expects($this->once())->method('debug')->
            with($this->equalTo('Config file loaded: '.$name.'.php'));

        // Call load() and verify results
        $this->assertTrue($config->load($file, true));
        $this->assertArrayHasKey($name, $config->config);
        $this->assertEquals($cfg, $config->config[$name]);
        $this->assertEquals(array($name), $config->is_loaded);
    }

    /**
     * Test load() with non-existent file
     */
    public function testLoadNone()
    {
        global $XY;

        // Set up args
        $name = 'figment';

        // Mock Xylophone and Config and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array(), array(), '', false);
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->
            with($this->equalTo($name.'.php'), $this->equalTo('config'))->
            will($this->returnValue(false));
        $XY->expects($this->once())->method('showError')->
            with($this->equalTo('The configuration file '.$name.'.php does not exist.'))->
            will($this->throwException(new InvalidArgumentException));

        // Call load()
        $this->setExpectedException('InvalidArgumentException');
        $config->load($name);
    }

    /**
     * Test load() with graceful non-existent file
     */
    public function testLoadNoneGraceful()
    {
        // Set up args
        $name1 = 'exists';
        $name2 = 'graceful';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->
            with($this->equalTo($name2.'.php'), $this->equalTo('config'))->
            will($this->returnValue(false));

        // Set first file as loaded
        $config->is_loaded[] = $name1;

        // Call load() and verify results
        $this->assertFalse($config->load(array($name1, $name2), false, true));
        $this->assertEquals(array($name1), $config->is_loaded);
    }

    /**
     * Test load() with bad file
     */
    public function testLoadBad()
    {
        global $XY;

        // Set up args
        $name = 'badcfg';

        // Mock Xylophone and Config and set up calls
        $XY = $this->getMock('Xylophone\core\Xylophone', array(), array(), '', false);
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->
            with($this->equalTo($name.'.php'), $this->equalTo('config'))->
            will($this->returnValue('/some/bad/path'));
        $XY->expects($this->once())->method('showError')->
            with($this->equalTo('Your '.$name.'.php file does not appear to contain a valid configuration array.'))->
            will($this->throwException(new InvalidArgumentException));

        // Call load()
        $this->setExpectedException('InvalidArgumentException');
        $config->load($name);
    }

    /**
     * Test load() with graceful bad file
     */
    public function testLoadBadGraceful()
    {
        // Set up args
        $name = 'badgrace';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('get'), array(), '', false);
        $config->expects($this->once())->method('get')->with($this->equalTo($name.'.php'), $this->equalTo('config'))->
            will($this->returnValue('/bad/file/path'));

        // Call load() and verify results
        $this->assertFalse($config->load($name, false, true));
    }

    /**
     * Test get()
     */
    public function testGet()
    {
        // Set up args
        $file = 'somecfg.php';
        $name = 'config';
        $retval = array('key' => 'val');

        // Mock Config and set up call
        $config = $this->getMock('Xylophone\core\Config', array('getExtra'), array(), '', false);
        $config->expects($this->once())->method('getExtra')->
            with($this->equalTo($file), $this->equalTo($name), $this->isFalse())->
            will($this->returnValue($retval));

        // Call get()
        $config->get($file, $name);
    }

    /**
     * Test getExtra() with no file
     */
    public function testGetExtraNone()
    {
        global $XY;

        // Set up VFS
        $this->vfsInit();

        // Mock Xylophone and Config
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Set empty environment and config paths
        $XY->environment = '';
        $XY->config_paths = array($this->vfs_app_path.'/');

        // Call getExtra() with non-existent file and confirm failure
        $extras = false;
        $this->assertFalse($config->getExtra('dummy', 'config', $extras));
    }

    /**
     * Test getExtra() with no array
     */
    public function testGetExtraEmpty()
    {
        global $XY;

        // Set up args
        $file = 'empty.php';
        $extras = false;

        // Set up VFS and make file
        $this->vfsInit();
        $this->vfsCreate('config/'.$file, '<?php $foo = \'bar\';', $this->vfs_app_dir);

        // Mock Xylophone and Config
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Set empty environment and config path
        $XY->environment = '';
        $XY->config_paths = array($this->vfs_app_path.'/');

        // Call getExtra() and confirm result
        $this->assertEquals($this->vfs_app_path.'/config/'.$file, $config->getExtra($file, 'config', $extras));
    }

    /**
     * Test getExtra() with no name
     */
    public function testGetExtraNoName()
    {
        global $XY;

        // Set up args
        $file = 'global';
        $key = 'warming';
        $val = 'trend';
        $extras = false;

        // Set up VFS and make file
        $this->vfsInit();
        $this->vfsCreate('config/'.$file.'.php', '<?php $GLOBALS[\''.$key.'\'] = \''.$val.'\';', $this->vfs_app_dir);

        // Mock Xylophone and Config
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Set empty environment and config path
        $XY->environment = '';
        $XY->config_paths = array($this->vfs_app_path.'/');

        // Call getExtra() and confirm result
        $this->assertTrue($config->getExtra($file, '', $extras));
        $this->assertArrayHasKey($key, $GLOBALS);
        $this->assertEquals($val, $GLOBALS[$key]);
    }

    /**
     * Test getExtra()
     */
    public function testGetExtra()
    {
        global $XY;

        // Set up args
        $file = 'vars.php';
        $cfg1 = array('one' => '1', 'two' => '3');
        $cfg2 = array('two' => '2');
        $result = array('one' => '1', 'two' => '2');
        $key1 = 'pickme';
        $key2 = 'andme';
        $nokey = '_notme';
        $val1 = 'first';
        $val2 = 'second';
        $noval = 'ignore';
        $extras = array();

        // Make file contents
        $content1 = '<?php $config = '.var_export($cfg1, true).'; $'.$key1.' = \''.$val1.'\';';
        $content2 = '<?php $config = '.var_export($cfg2, true).'; $'.$key2.' = \''.$val2.'\'; '.
            '$'.$nokey.' = \''.$noval.'\';';

        // Set up VFS and make files
        $this->vfsInit();
        $tp_dir = $this->vfsMkdir('third-party', $this->vfs_base_dir);
        $this->vfsCreate('config/'.$file, $content1, $tp_dir);
        $this->vfsCreate('config/'.$file, $content2, $this->vfs_app_dir);

        // Mock Xylophone and Config
        $XY = new stdClass();
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Set empty environment and config paths
        $XY->environment = '';
        $XY->config_paths = array($tp_dir->url().'/', $this->vfs_app_path.'/');

        // Call getExtra() and confirm result
        $this->assertEquals($result, $config->getExtra($file, 'config', $extras));
        $this->assertEquals(array($key1 => $val1, $key2 => $val2), $extras);
    }

    /**
     * Test item() fail
     */
    public function testItemFail()
    {
        // Mock Config
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Call item() and confirm missing item fail
        $this->assertNull($config->item('missing'));
    }

    /**
     * Test item()
     */
    public function testItem()
    {
        // Set args
        $key = 'findme';
        $val = 'yay';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = $val;

        // Call item() and confirm result
        $this->assertEquals($val, $config->item($key));
    }

    /**
     * Test item() fail with index
     */
    public function testItemIndexFail()
    {
        // Set arg
        $key = 'empty';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = array();

        // Call item() and confirm missing item index fail
        $this->assertNull($config->item($key, 'nothere'));
    }

    /**
     * Test item() with index
     */
    public function testItemIndex()
    {
        // Set args
        $key1 = 'group';
        $key2 = 'member';
        $val = 'ship';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key1] = array($key2 => $val);

        // Call item() and confirm item index
        $this->assertEquals($val, $config->item($key2, $key1));
    }

    /**
     * Test setItem()
     */
    public function testSetItem()
    {
        // Set up args
        $key1 = 'single';
        $val1 = 'value';
        $key2 = 'multi';
        $val2 = array($key2 => array('gets' => 'newval', 'newkey' => 'added'));
        $pre2 = array('stays' => 'same', 'gets' => 'replaced');
        $result = array('stays' => 'same', 'gets' => 'newval', 'newkey' => 'added');

        // Mock Config and set previous value
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config[$key2] = $pre2;

        // Call setItem() and confirm results
        $config->setItem($key1, $val1);
        $this->assertArrayHasKey($key1, $config->config);
        $this->assertEquals($val1, $config->config[$key1]);
        $config->setItem($val2);
        $this->assertEquals($result, $config->config[$key2]);
    }

    /**
     * Test slashItem() fail
     */
    public function testSlashItemFail()
    {
        // Mock Config
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Call slashItem() and confirm missing item fail
        $this->assertNull($config->slashItem('missing'));
    }

    /**
     * Test slashItem() with empty value
     */
    public function testSlashItemEmpty()
    {
        // Set arg
        $key = 'blank';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = '   ';

        // Call slashItem() and confirm result
        $this->assertEmpty($config->slashItem($key));
    }

    /**
     * Test slashItem()
     */
    public function testSlashItem()
    {
        // Set args
        $key = 'path';
        $val = '/fake/dir';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = $val;

        // Call slashItem() and confirm results
        $this->assertEquals($val.'/', $config->slashItem($key));
        $config->config[$key] = $val.'/';
        $this->assertEquals($val.'/', $config->slashItem($key));
    }

    /**
     * Test siteUrl()
     */
    public function testSiteUrl()
    {
        // Set up args
        $base = 'http://testserver.net/testapp/';
        $index = 'main';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashitem', 'item'), array(), '', false);
        $config->expects($this->once())->method('slashItem')->with($this->equalTo('base_url'))->
            will($this->returnValue($base));
        $config->expects($this->once())->method('item')->with($this->equalTo('index_page'))->
            will($this->returnValue($index));

        // Call siteUrl() and verify result
        $this->assertEquals($base.$index, $config->siteUrl());
    }

    /**
     * Test siteUrl() with a URI string and a URL suffix
     */
    public function testSiteUrlUriSuffix()
    {
        // Set up args
        $base = 'http://myserver.com/someapp/';
        $index = 'home';
        $uri = 'some/args';
        $suffix = 'ctlr';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashitem', 'item', 'uriString'),
            array(), '', false);
        $config->expects($this->exactly(2))->method('slashItem')->will($this->returnValueMap(array(
            array('base_url', $base),
            array('index_page', $index)
        )));
        $config->expects($this->once())->method('uriString')->with($this->equalTo($uri))->
            will($this->returnArgument(0));
        $config->expects($this->once())->method('item')->with($this->equalTo('enable_query_strings'))->
            will($this->returnValue(false));
        $config->config['url_suffix'] = $suffix;

        // Call siteUrl() and verify result
        $this->assertEquals($base.$index.$uri.$suffix, $config->siteUrl($uri));
    }

    /**
     * Test siteUrl() with a split URI string and a URL suffix
     */
    public function testSiteUrlSuffixUri()
    {
        // Set up args
        $base = 'http://test.org/myapp/';
        $index = 'index/';
        $dir = 'sub';
        $query = '?arg';
        $uri = $dir.$query;
        $suffix = '.php';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashitem', 'item', 'uriString'),
            array(), '', false);
        $config->expects($this->exactly(2))->method('slashItem')->will($this->returnValueMap(array(
            array('base_url', $base),
            array('index_page', $index)
        )));
        $config->expects($this->once())->method('uriString')->with($this->equalTo($uri))->
            will($this->returnArgument(0));
        $config->expects($this->once())->method('item')->with($this->equalTo('enable_query_strings'))->
            will($this->returnValue(false));
        $config->config['url_suffix'] = $suffix;

        // Call siteUrl() and verify result
        $this->assertEquals($base.$index.$dir.$suffix.$query, $config->siteUrl($uri));
    }

    /**
     * Test siteUrl() with a URI string, a protocol, and query strings
     */
    public function testSiteUrlProtoQuery()
    {
        // Set up args
        $proto1 = 'http';
        $proto2 = 'https';
        $base = '://testing.us/somemore/';
        $index = 'with';
        $uri = '?this&that';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashitem', 'item', 'uriString'),
            array(), '', false);
        $config->expects($this->once())->method('slashItem')->with($this->equalTo('base_url'))->
            will($this->returnValue($proto1.$base));
        $config->expects($this->once())->method('uriString')->with($this->equalTo($uri))->
            will($this->returnArgument(0));
        $config->expects($this->exactly(2))->method('item')->will($this->returnValueMap(array(
            array('enable_query_strings', '', true),
            array('index_page', '', $index)
        )));

        // Call siteUrl() and verify result
        $this->assertEquals($proto2.$base.$index.$uri, $config->siteUrl($uri, $proto2));
    }

    /**
     * Test baseUrl()
     */
    public function testBaseUrl()
    {
        // Set up args
        $proto1 = 'https';
        $proto2 = 'http';
        $base = '://localhost/localtest/';
        $uri = 'foo/bar';

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashItem', 'item'), array(), '', false);
        $config->expects($this->once())->method('slashItem')->with($this->equalTo('base_url'))->
            will($this->returnValue($proto1.$base));
        $config->expects($this->once())->method('item')->with($this->equalTo('enable_query_strings'))->
            will($this->returnValue(false));

        // Call baseUrl() and verify reult
        $this->assertEquals($proto2.$base.$uri, $config->baseUrl('/'.$uri.'/', $proto2));
    }

    /**
     * Test baseUrl() with query strings
     */
    public function testBaseUrlQuery()
    {
        // Set up args
        $base = 'http://localhost/querytest/';
        $key1 = 'first';
        $val1 = 'arg';
        $key2 = 'second';
        $val2 = 'param';
        $uri = array($key1 => $val1, $key2 => $val2);

        // Mock Config and set up calls
        $config = $this->getMock('Xylophone\core\Config', array('slashItem', 'item'), array(), '', false);
        $config->expects($this->once())->method('slashItem')->with($this->equalTo('base_url'))->
            will($this->returnValue($base));
        $config->expects($this->once())->method('item')->with($this->equalTo('enable_query_strings'))->
            will($this->returnValue(true));

        // Call baseUrl() and verify reult
        $this->assertEquals($base.'?'.$key1.'='.$val1.'&'.$key2.'='.$val2, $config->baseUrl($uri));
    }

    /**
     * Test offsetExists()
     */
    public function testOffsetExists()
    {
        // Set args
        $key = 'good';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = true;

        // Test via array access
        $this->assertTrue(isset($config[$key]));
        $this->assertFalse(isset($config['bad']));
    }

    /**
     * Test offsetGet()
     */
    public function testOffsetGet()
    {
        // Set args
        $key = 'real';
        $val = 'value';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = $val;

        // Test via array access
        $this->assertEquals($val, $config[$key]);
        $this->assertNull($config['figment']);
    }

    /**
     * Test offsetSet()
     */
    public function testOffsetSet()
    {
        // Set args
        $number = 0;
        $key = 'foo';
        $val = 'bar';

        // Mock Config
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);

        // Test via array access
        $config[$number] = 'nothing';
        $this->assertArrayNotHasKey($number, $config->config);
        $config[$key] = $val;
        $this->assertArrayHasKey($key, $config->config);
        $this->assertEquals($val, $config->config[$key]);
    }

    /**
     * Test offsetUnset()
     */
    public function testOffsetUnset()
    {
        // Set arg
        $key = 'nowyouseeme';

        // Mock Config and set item
        $config = $this->getMock('Xylophone\core\Config', null, array(), '', false);
        $config->config[$key] = 'nowyoudont';

        // Test via array access
        unset($config[$key]);
        $this->assertArrayNotHasKey($key, $config->config);
    }
}

