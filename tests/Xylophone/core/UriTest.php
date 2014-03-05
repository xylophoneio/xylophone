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
 * @copyright   Copyright (c) 2013, Xylophone Team (http://xylophone.io/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://xylophone.io
 * @since       Version 1.0
 * @filesource
 */

/**
 * URI Unit Test
 *
 * @package Xylophone
 */
class UriTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $chars = 'abc123';
        $enable = true;

        // Set up calls
        $XY->config['permitted_uri_chars'] = $chars;
        $XY->config['enable_query_strings'] = $enable;
        $XY->logger->expects($this->once())->method('debug');

        // Call __construct() and verify results
        $uri->__construct();
        $this->assertEquals($chars, $uri->perm_char);
        $this->assertEquals($enable, $uri->query_str);
    }

    /**
     * Test load() with AUTO protocol and CLI
     */
    public function testLoadAutoCli()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseArgv'), array(), '', false);

        // Set up arg
        $retval = 'cli/argv';

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(true));
        $uri->expects($this->once())->method('parseArgv')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with AUTO protocol and PATH_INFO
     */
    public function testLoadAutoPath()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString'), array(), '', false);

        // Set up args
        $retval = 'path/info';
        $_SERVER['PATH_INFO'] = $retval;

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with AUTO protocol and REQUEST_URI
     */
    public function testLoadAutoUri()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseRequestUri'), array(), '', false);

        // Set up args
        $retval = 'request/uri';

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $uri->expects($this->once())->method('parseRequestUri')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with AUTO protocol and QUERY_STRING
     */
    public function testLoadAutoQuery()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseRequestUri', 'parseQueryString'),
            array(), '', false);

        // Set up args
        $retval = 'query/string';

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $uri->expects($this->once())->method('parseRequestUri')->will($this->returnValue(''));
        $uri->expects($this->once())->method('parseQueryString')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with AUTO protocol and GET
     */
    public function testLoadAutoGet()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseRequestUri', 'parseQueryString'),
            array(), '', false);

        // Set up args
        $retval = 'get/key';
        $_GET = array($retval => true);

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $uri->expects($this->once())->method('parseRequestUri')->will($this->returnValue(''));
        $uri->expects($this->once())->method('parseQueryString')->will($this->returnValue(''));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with AUTO protocol failure
     */
    public function testLoadAutoFail()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseRequestUri', 'parseQueryString'),
            array(), '', false);

        // Set up calls
        $XY->config['uri_protocol'] = 'AUTO';
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $uri->expects($this->once())->method('parseRequestUri')->will($this->returnValue(''));
        $uri->expects($this->once())->method('parseQueryString')->will($this->returnValue(''));
        $uri->expects($this->never())->method('setUriString');

        // Call load() and verify result
        $uri->load();
        $this->assertEquals('', $uri->uri_string);
    }

    /**
     * Test load() with CLI
     */
    public function testLoadCli()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseArgv'), array(), '', false);

        // Set up arg
        $retval = 'foo/bar';

        // Set up calls
        $XY->config['uri_protocol'] = 'CLI';
        $uri->expects($this->once())->method('parseArgv')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with REQUEST_URI
     */
    public function testLoadUri()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseRequestUri'), array(), '', false);

        // Set up args
        $retval = 'some/uri';

        // Set up calls
        $XY->config['uri_protocol'] = 'REQUEST_URI';
        $uri->expects($this->once())->method('parseRequestUri')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with QUERY_STRING
     */
    public function testLoadQuery()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString', 'parseQueryString'), array(), '', false);

        // Set up args
        $retval = 'some/query';

        // Set up calls
        $XY->config['uri_protocol'] = 'QUERY_STRING';
        $uri->expects($this->once())->method('parseQueryString')->will($this->returnValue($retval));
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval))->
            will($this->returnArgument(0));

        // Call load() and verify result
        $this->assertEquals($retval, $uri->load());
    }

    /**
     * Test load() with server string
     */
    public function testLoadServer()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString'), array(), '', false);

        // Set up args
        $retval = 'server/string';
        $proto = 'FOO_BAR';

        // Set up calls
        $XY->config['uri_protocol'] = $proto;
        $_SERVER[$proto] = $retval;
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval));

        // Call load()
        $uri->load();
    }

    /**
     * Test load() with environment variable
     */
    public function testLoadEnv()
    {
        global $XY;

        // Mock Xylophone, Config, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $uri = $this->getMock('Xylophone\core\Uri', array('setUriString'), array(), '', false);

        // Set up args
        $retval = 'server/string';
        $proto = 'PHP_VAR';

        // Set up calls
        $XY->config['uri_protocol'] = $proto;
        putenv($proto.'='.$retval);
        $uri->expects($this->once())->method('setUriString')->with($this->equalTo($retval));

        // Call load()
        $uri->load();
    }

    /**
     * Test setRuriString()
     */
    public function testSetRuriString()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', array('explodeSegments'), array(), '', false);

        // Set up args
        $seg1 = 'a';
        $seg2 = 'b';
        $seg3 = 'c';
        $segs = array($seg1, $seg2, $seg3);
        $rseg1 = 'one';
        $rseg2 = 'two';
        $rseg3 = 'three';
        $str = $rseg1.'/'.$rseg2.'/'.$rseg3;
        $rsegs = array($rseg1, $rseg2, $rseg3);
        $keys = array(1, 2, 3);

        // Set up calls
        $uri->segments = $segs;
        $uri->expects($this->once())->method('explodeSegments')->with($this->equalTo($str))->
            will($this->returnValue($rsegs));

        // Call setRuriString() and verify results
        $uri->setRuriString($str);
        $this->assertEquals(array_combine($keys, $segs), $uri->segments);
        $this->assertEquals(array_combine($keys, $rsegs), $uri->rsegments);
    }

    /**
     * Test filterUri() with an empty string
     */
    public function testFilterUriEmpty()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Call filterUri() and verify result
        $str = '';
        $this->assertEquals($str, $uri->filterUri($str));
    }

    /**
     * Test filterUri() with un-permitted chars
     */
    public function testFilterUriChars()
    {
        global $XY;

        // Mock Xylophone and Uri
        $XY = $this->getMock('Xylophone\core\Xylophone', array('showError'), array(), '', false);
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $uri->perm_chars = 'abc123$%^';
        $uri->query_str = true;
        $str = $uri->perm_chars.'*';

        // Set up call
        $XY->expects($this->once())->method('showError')->
            with($this->stringContains('disallowed characters'), $this->equalTo(400))->
            will($this->throwException(new InvalidArgumentException()));

        // Call filterUri() and verify result
        $this->setExpectedException('InvalidArgumentException');
        $uri->filterUri($str);
    }

    /**
     * Test filterUri()
     */
    public function testFilterUri()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $str = '$foo(bar)%28%29';
        $retval = '&#36;foo&#40;bar&#41;&#40;&#41;';

        // Set up calls
        $uri->perm_chars = 'a-z0-9()%$';
        $uri->query_str = true;

        // Call filterUri() and verify result
        $this->assertEquals($retval, $uri->filterUri($str));
    }

    /**
     * Test setUriString()
     */
    public function testSetUriString()
    {
        global $XY;

        // Mock Xylophone, Config, Output, and Uri
        $XY = new stdClass();
        $XY->config = array();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $uri = $this->getMock('Mocks\core\Uri', array('explodeSegments'), array(), '', false);

        // Set up args
        $base = 'some/uri/path';
        $suffix = '_str';
        $str = '/'.$base.$suffix.'/';

        // Set up calls
        $XY->config['url_suffix'] = $suffix;
        $XY->output->expects($this->once())->method('removeInvisibleCharacters')->with($this->equalTo($str))->
            will($this->returnArgument(0));
        $uri->expects($this->once())->method('explodeSegments')->with($this->equalTo($base))->
            will($this->returnArgument(0));

        // Call setUriString() and verify result
        $uri->setUriString($str);
        $this->assertEquals($base, $uri->segments);
    }

    /**
     * Test explodeSegments() with empty segments
     */
    public function testExplodeSegmentsEmpty()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', array('filterUri'), array(), '', false);

        // Set up arg
        $str = '/ / / /';

        // Set up call
        $uri->expects($this->exactly(3))->method('filterUri')->with($this->equalTo(' '))->
            will($this->returnArgument(0));

        // Call explodeSegments() and verify result
        $this->assertEmpty($uri->explodeSegments($str));
    }

    /**
     * Test explodeSegments()
     */
    public function testExplodeSegments()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', array('filterUri'), array(), '', false);

        // Set up arg
        $seg1 = 'foo';
        $seg2 = 'bar';
        $seg3 = 'baz';
        $str = '//'.$seg1.'/'.$seg2.'/'.$seg3.'//';

        // Set up call
        $uri->expects($this->exactly(3))->method('filterUri')->will($this->returnArgument(0));

        // Call explodeSegments() and verify result
        $this->assertEquals(array($seg1, $seg2, $seg3), $uri->explodeSegments($str));
    }

    /**
     * Test parseRequestUri() when there is non
     */
    public function testParseRequestUriNone()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up calls
        $_SERVER['SCRIPT_NAME'] = 'myscript';
        unset($_SERVER['REQUEST_URI']);

        // Call parseRequestUri() and verify result
        $this->assertEquals('', $uri->parseRequestUri());

        // Switch empties and call again
        $_SERVER['REQUEST_URI'] = 'path/to/some/script';
        unset($_SERVER['SCRIPT_NAME']);
        $this->assertEquals('', $uri->parseRequestUri());
    }

    /**
     * Test parseRequestUri() with no path
     */
    public function testParseRequestUriNoPath()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up arg
        $path = '/path';
        $arg = 'which';
        $val = 'way';
        $query = $arg.'='.$val;
        $_SERVER['REQUEST_URI'] = 'http://empty.com'.$path.'?'.$query;
        $_SERVER['SCRIPT_NAME'] = $path.'/index.php';

        // Call parseRequestUri() and verify results
        $this->assertEquals('/', $uri->parseRequestUri());
        $this->assertEquals($query, $_SERVER['QUERY_STRING']);
        $this->assertEquals(array($arg => $val), $_GET);
    }

    /**
     * Test parseRequestUri()
     */
    public function testParseRequestUri()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', array('removeRelativeDirectory'), array(), '', false);

        // Set up args
        $app = '/some';
        $path = '/dir';
        $arg = 'foo';
        $val = 'bar';
        $query = $arg.'='.$val;
        $_SERVER['REQUEST_URI'] = 'http://example.com'.$app.'?'.$path.'?'.$query;
        $_SERVER['SCRIPT_NAME'] = $app;

        // Set up call
        $uri->expects($this->once())->method('removeRelativeDirectory')->with($this->equalTo($path))->
            will($this->returnArgument(0));

        // Call parseRequestUri() and verify results
        $this->assertEquals($path, $uri->parseRequestUri());
        $this->assertEquals($query, $_SERVER['QUERY_STRING']);
        $this->assertEquals(array($arg => $val), $_GET);
    }

    /**
     * Test parseQueryString() when there is none
     */
    public function testParseQueryStringNone()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Call parseQueryString() and verify result
        $this->assertEquals('', $uri->parseQueryString());
    }

    /**
     * Test parseQueryString()
     */
    public function testParseQueryString()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', array('removeRelativeDirectory'), array(), '', false);

        // Set up args
        $path = '/something';
        $arg = 'number';
        $val = '42';
        $query = $arg.'='.$val;
        $_SERVER['QUERY_STRING'] = $path.'?'.$query;

        // Set up call
        $uri->expects($this->once())->method('removeRelativeDirectory')->with($this->equalTo($path))->
            will($this->returnArgument(0));

        // Call parseQueryString() and verify results
        $this->assertEquals($path, $uri->parseQueryString());
        $this->assertEquals($query, $_SERVER['QUERY_STRING']);
        $this->assertEquals(array($arg => $val), $_GET);
    }

    /**
     * Test parseArgv() when there is none
     */
    public function testParseArgvNone()
    {
        // Mock Uri and set up args
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);
        $_SERVER['argv'] = array('myscript');

        // Call parseArgv() and verify result
        $this->assertEquals('', $uri->parseArgv());
    }

    /**
     * Test parseArgv()
     */
    public function testParseArgv()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $app = 'some';
        $dir = 'path';
        $_SERVER['argv'] = array('myscript', $app, $dir);

        // Call parseArgv() and verify result
        $this->assertEquals($app.'/'.$dir, $uri->parseArgv());
    }

    /**
     * Test removeRelativeDirectory()
     */
    public function testRemoveRelativeDirectory()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $dir = 'foo/bar';

        // Call removeRelativeDirectory() and verify result
        $this->assertEquals($dir, $uri->removeRelativeDirectory('..////'.$dir));
    }

    /**
     * Test segment() with a non-existent segment
     */
    public function testSegmentMissing()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Call segment() and verify result
        $this->assertNull($uri->segment(3));
    }

    /**
     * Test segment() with no result
     */
    public function testSegmentNoResult()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $seg = 2;
        $no = 'none';

        // Call segment() and verify result
        $this->assertEquals($no, $uri->segment($seg, $no));
    }

    /**
     * Test segment()
     */
    public function testSegment()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $seg = 1;
        $val = 'foo';
        $uri->segments[$seg] = $val;

        // Call segment() and verify result
        $this->assertEquals($val, $uri->segment($seg));
    }

    /**
     * Test rsegment() with a non-existent rsegment
     */
    public function testRsegmentMissing()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Call rsegment() and verify result
        $this->assertNull($uri->rsegment(3));
    }

    /**
     * Test rsegment() with no result
     */
    public function testRsegmentNoResult()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $seg = 2;
        $no = 'none';

        // Call rsegment() and verify result
        $this->assertEquals($no, $uri->rsegment($seg, $no));
    }

    /**
     * Test rsegment()
     */
    public function testRsegment()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $seg = 1;
        $val = 'foo';
        $uri->rsegments[$seg] = $val;

        // Call rsegment() and verify result
        $this->assertEquals($val, $uri->rsegment($seg));
    }

    /**
     * Test uriToAssoc()
     */
    public function testUriToAssoc()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', array('toAssoc'), array(), '', false);

        // Set up args
        $start = 1;
        $default = array('one', 'two');

        // Set up calls
        $uri->segments = array('three');
        $uri->expects($this->once())->method('toAssoc')->
            with($this->equalTo($start), $this->equalTo($default), $this->equalTo($uri->segments))->
            will($this->returnArgument(1));

        // Call uriToAssoc() and verify result
        $this->assertEquals($default, $uri->uriToAssoc($start, $default));
    }

    /**
     * Test ruriToAssoc()
     */
    public function testRuriToAssoc()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', array('toAssoc'), array(), '', false);

        // Set up args
        $start = 1;
        $default = array('a', 'b');

        // Set up calls
        $uri->segments = array('c');
        $uri->expects($this->once())->method('toAssoc')->
            with($this->equalTo($start), $this->equalTo($default), $this->equalTo($uri->rsegments))->
            will($this->returnArgument(1));

        // Call ruriToAssoc() and verify result
        $this->assertEquals($default, $uri->ruriToAssoc($start, $default));
    }

    /**
     * Test toAssoc() with a bad start
     */
    public function testToAssocBadStart()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $start = 'foo';
        $default = array('bar', 'baz');

        // Call toAssoc() and verify result
        $this->assertEquals($default, $uri->toAssoc($start, $default, $uri->segments));
    }

    /**
     * Test toAssoc() with a start too big
     */
    public function testToAssocStartBig()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $start = 2;
        $def1 = 'this';
        $def2 = 'that';
        $default = array($def1, $def2);
        $segments = array();

        // Call toAssoc() and verify result
        $this->assertEquals(array($def1 => null, $def2 => null), $uri->toAssoc($start, $default, $segments));
    }

    /**
     * Test toAssoc()
     */
    public function testToAssoc()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $start = 3;
        $def1 = 'here';
        $def2 = 'there';
        $default = array($def1, $def2);
        $seg1 = 'red';
        $seg2 = 'blue';
        $segments = array('one', 'two', $seg1, $seg2);

        // Call toAssoc() and verify result
        $retval = array($seg1 => $seg2, $def1 => null, $def2 => null);
        $this->assertEquals($retval, $uri->toAssoc($start, $default, $segments));
    }

    /**
     * Test assocToUri()
     */
    public function testAssocToUri()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', null, array(), '', false);

        // Set up args
        $arg1 = 'crash';
        $val1 = 'override';
        $arg2 = 'acid';
        $val2 = 'burn';
        $assoc = array($arg1 => $val1, $arg2 => $val2);

        // Call assocToUri() and verify result
        $this->assertEquals($arg1.'/'.$val1.'/'.$arg2.'/'.$val2, $uri->assocToUri($assoc));
    }

    /**
     * Test slashSegment()
     */
    public function testSlashSegment()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', array('addSlash'), array(), '', false);

        // Set up args
        $seg = 2;
        $val = 'bar';
        $where = 'leading';

        // Set up calls
        $uri->segments = array('foo', $val);
        $uri->expects($this->once())->method('addSlash')->
            with($this->equalTo($seg), $this->equalTo($where), $this->equalTo($uri->segments))->
            will($this->returnValue($val));

        // Call slashSegment() and verify result
        $this->assertEquals($val, $uri->slashSegment($seg, $where));
    }

    /**
     * Test slashRsegment()
     */
    public function testSlashRsegment()
    {
        // Mock Uri
        $uri = $this->getMock('Xylophone\core\Uri', array('addSlash'), array(), '', false);

        // Set up args
        $seg = 1;
        $val = 'foo';
        $where = 'both';

        // Set up calls
        $uri->rsegments = array($val, 'bar');
        $uri->expects($this->once())->method('addSlash')->
            with($this->equalTo($seg), $this->equalTo($where), $this->equalTo($uri->rsegments))->
            will($this->returnValue($val));

        // Call slashRsegment() and verify result
        $this->assertEquals($val, $uri->slashRsegment($seg, $where));
    }

    /**
     * Test addSlash() with a non-existent segment
     */
    public function testAddSlashNone()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $n = 1;
        $where = 'both';
        $segments = array();

        // Call addSlash() and verify result
        $this->assertNull($uri->addSlash($n, $where, $segments));
    }

    /**
     * Test addSlash() with a leading slash
     */
    public function testAddSlashLeading()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $n = 1;
        $val = 'voodoo';
        $where = 'leading';
        $segments = array('whodo', $val);

        // Call addSlash() and verify result
        $this->assertEquals('/'.$val, $uri->addSlash($n, $where, $segments));
    }

    /**
     * Test addSlash() with a trailing slash
     */
    public function testAddSlashTrailing()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $n = 0;
        $val = 'tia';
        $where = 'trailing';
        $segments = array($val, 'dalma');

        // Call addSlash() and verify result
        $this->assertEquals($val.'/', $uri->addSlash($n, $where, $segments));
    }

    /**
     * Test addSlash() with both
     */
    public function testAddSlashBoth()
    {
        // Mock Uri
        $uri = $this->getMock('Mocks\core\Uri', null, array(), '', false);

        // Set up args
        $n = 1;
        $val = 'sparrow';
        $where = 'both';
        $segments = array('jack', $val);

        // Call addSlash() and verify result
        $this->assertEquals('/'.$val.'/', $uri->addSlash($n, $where, $segments));
    }
}

