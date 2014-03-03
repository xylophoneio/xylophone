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
 * Input Unit Test
 *
 * @package Xylophone
 */
class InputTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Input
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $input = $this->getMock('Xylophone\core\Input', array('sanitizeGlobals'), array(), '', false);

        // Set up args
        $allow = true;
        $xss = true;
        $csrf = false;

        // Set up calls
        $XY->config['allow_get_array'] = $allow;
        $XY->config['global_xss_filtering'] = $xss;
        $XY->config['csrf_protection'] = $csrf;
        $XY->logger->expects($this->once())->method('debug');
        $input->expects($this->once())->method('sanitizeGlobals');

        // Call __construct() and verify results
        $input->__construct();
        $this->assertEquals($allow, $input->allow_get_array);
        $this->assertEquals($xss, $input->enable_xss);
        $this->assertEquals($csrf, $input->enable_csrf);
    }

    /**
     * Test fetchFromArray() with a missing key
     */
    public function testFetchFromArrayNone()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $array = array();
        $index = 'missing';

        // Call fetchFromArray() and verify result
        $this->assertNull($input->fetchFromArray($array, $index));
    }

    /**
     * Test fetchFromArray()
     */
    public function testFetchFromArray()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'key';
        $value = 'found';
        $array = array($index => $value);

        // Call fetchFromArray() and verify result
        $this->assertEquals($value, $input->fetchFromArray($array, $index));
    }

    /**
     * Test fetchFromArray() with xss_clean
     */
    public function testFetchFromArrayClean()
    {
        global $XY;

        // Mock Xylophone, Security and Input
        $XY = new stdClass();
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'dirty';
        $value = 'clean';
        $array = array($index => $value);

        // Set up call
        $XY->security->expects($this->once())->method('xssClean')->with($this->equalTo($value))->
            will($this->returnArgument(0));

        // Call fetchFromArray() and verify result
        $this->assertEquals($value, $input->fetchFromArray($array, $index, true));
    }

    /**
     * Test fetchFromArray() with a whole sub-array
     */
    public function testFetchFromArrayWholeSub()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'sub';
        $value = array('one', 'two');
        $array = array($index => $value);

        // Call fetchFromArray() and verify result
        $this->assertEquals($value, $input->fetchFromArray($array, $index.'[]'));
    }

    /**
     * Test fetchFromArray() with a sub-key
     */
    public function testFetchFromArraySub()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'one';
        $sub = 'two';
        $value = 'three';
        $array = array($index => array('red' => 'blue', $sub => $value));

        // Call fetchFromArray() and verify result
        $this->assertEquals($value, $input->fetchFromArray($array, $index.'['.$sub.']'));
    }

    /**
     * Test fetchFromArray() with a bad sub-key
     */
    public function testFetchFromArrayBadSub()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'good';
        $sub = 'bad';
        $value = array('a' => 'foo', 'b' => 'bar');
        $array = array($index => $value);

        // Call fetchFromArray() and verify result
        $this->assertNull($input->fetchFromArray($array, $index.'['.$sub.']'));
    }

    /**
     * Test get() with all keys
     */
    public function testGetAll()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Set up args
        $_GET = array('one' => 'foo', 'two' => 'bar');

        // Call get() and verify results
        $this->assertEquals($_GET, $input->get());
    }

    /**
     * Test get() with all keys cleaned
     */
    public function testGetAllClean()
    {
        global $XY;

        // Mock Xylophone, Security, and Input
        $XY = new stdClass();
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Set up args
        $_GET = array('a' => 'one', 'b' => 'two');

        // Set up calls
        $XY->security->expects($this->exactly(count($_GET)))->method('xssClean')->will($this->returnArgument(0));

        // Call get() and verify results
        $this->assertEquals($_GET, $input->get(null, true));
    }

    /**
     * Test get()
     */
    public function testGet()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('fetchFromArray'), array(), '', false);

        // Set up args
        $index = 'pick';
        $value = 'me';
        $clean = true;
        $_GET = array('not' => 'this', $index => $value);

        // Set up call
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($_GET), $this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call get() and verify results
        $this->assertEquals($value, $input->get($index, $clean));
    }

    /**
     * Test post() with all keys
     */
    public function testPostAll()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Set up args
        $_POST = array('heave' => 'ho', 'lets' => 'go');

        // Call post() and verify results
        $this->assertEquals($_POST, $input->post());
    }

    /**
     * Test post() with all keys cleaned
     */
    public function testPostAllClean()
    {
        global $XY;

        // Mock Xylophone, Security, and Input
        $XY = new stdClass();
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Set up args
        $_POST = array('foo' => 'bar', 'baz' => 'boo');

        // Set up calls
        $XY->security->expects($this->exactly(count($_POST)))->method('xssClean')->will($this->returnArgument(0));

        // Call post() and verify results
        $this->assertEquals($_POST, $input->post(null, true));
    }

    /**
     * Test post()
     */
    public function testPost()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('fetchFromArray'), array(), '', false);

        // Set up args
        $index = 'this';
        $value = 'one';
        $clean = false;
        $_POST = array('not' => 'me', $index => $value);

        // Set up call
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($_POST), $this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call post() and verify results
        $this->assertEquals($value, $input->post($index, $clean));
    }

    /**
     * Test postGet() with a value in $_POST
     */
    public function testPostGetPost()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('post'), array(), '', false);

        // Set up args
        $index = 'for';
        $value = 'whipping';
        $clean = false;
        $_POST = array($index => $value);

        // Set up call
        $input->expects($this->once())->method('post')->with($this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call postGet() and verify result
        $this->assertEquals($value, $input->postGet($index, $clean));
    }

    /**
     * Test postGet() with a value not in $_POST
     */
    public function testPostGet()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('get'), array(), '', false);

        // Set up args
        $index = 'not';
        $value = 'posted';
        $clean = true;

        // Set up call
        $input->expects($this->once())->method('get')->with($this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call postGet() and verify result
        $this->assertEquals($value, $input->postGet($index, $clean));
    }

    /**
     * Test getPost() with a value in $_GET
     */
    public function testGetPostGet()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('get'), array(), '', false);

        // Set up args
        $index = 'this';
        $value = 'arg';
        $clean = false;
        $_GET = array($index => $value);

        // Set up call
        $input->expects($this->once())->method('get')->with($this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call getPost() and verify result
        $this->assertEquals($value, $input->getPost($index, $clean));
    }

    /**
     * Test getPost() with a value not in $_GET
     */
    public function testGetPost()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('post'), array(), '', false);

        // Set up args
        $index = 'was';
        $value = 'posted';
        $clean = true;

        // Set up call
        $input->expects($this->once())->method('post')->with($this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call getPost() and verify result
        $this->assertEquals($value, $input->getPost($index, $clean));
    }

    /**
     * Test cookie()
     */
    public function testCookie()
    {
        // Set up args
        $index = 'monster';
        $value = 'blue';
        $clean = true;
        $_COOKIE = array($index => $value);

        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', array('fetchFromArray'), array(), '', false);
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($_COOKIE), $this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call cookie() and verify result
        $this->assertEquals($value, $input->cookie($index, $clean));
    }

    /**
     * Test server()
     */
    public function testServer()
    {
        // Set up args
        $index = 'rack';
        $value = 'dusty';
        $clean = false;
        $_SERVER = array($index => $value);

        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', array('fetchFromArray'), array(), '', false);
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($_SERVER), $this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call server() and verify result
        $this->assertEquals($value, $input->server($index, $clean));
    }

    /**
     * Test inputStream() after reading
     */
    public function testInputStreamRead()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', array('fetchFromArray'), array(), '', false);

        // Set up args
        $index = 'key';
        $value = 'arg';
        $clean = true;
        $stream = array($index => $value);

        // Set up calls
        $input->input_stream = $stream;
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($stream), $this->equalTo($index), $this->equalTo($clean))->
            will($this->returnValue($value));

        // Call inputStream() and verify result
        $this->assertEquals($value, $input->inputStream($index, $clean));
    }

    /**
     * Test inputStream() with a bad stream
     */
    public function testInputStreamBad()
    {
        // Set up filesystem
        $this->vfsInit();
        $stream = $this->vfsCreate('inputstream', '', $this->vfs_base_dir);

        // Mock Input
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up calls
        $input->input_stream = null;
        $input->input_source = $stream->url();

        // Call inputStream() and verify result
        $this->assertNull($input->inputStream('foo'));
        $this->assertEquals(array(), $input->input_stream);
    }

    /**
     * Test inputStream()
     */
    public function testInputStream()
    {
        // Set up args
        $key1 = 'duck';
        $val1 = 'quack';
        $key2 = 'cow';
        $val2 = 'moo';
        $args = array($key1 => $val1, $key2 => $val2);
        $clean = false;

        // Set up filesystem
        $this->vfsInit();
        $stream = $this->vfsCreate('inputstream', $key1.'='.$val1.'&'.$key2.'='.$val2, $this->vfs_base_dir);

        // Mock Input
        $input = $this->getMock('Mocks\core\Input', array('fetchFromArray'), array(), '', false);

        // Set up calls
        $input->input_stream = null;
        $input->input_source = $stream->url();
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($args), $this->equalTo($key1), $this->equalTo($clean))->
            will($this->returnValue($val1));

        // Call inputStream() and verify result
        $this->assertEquals($val1, $input->inputStream($key1, $clean));
        $this->assertEquals($args, $input->input_stream);
    }

    /**
     * Test setCookie() with default values
     */
    public function testSetCookieDefault()
    {
        global $XY;

        // Mock Xylophone, Config, and Input
        $XY = new stdClass();
        $XY->config = array();
        $input = $this->getMock('Xylophone\core\Input', array('xySetCookie'), array(), '', false);

        // Set up arg
        $name = 'test_cookie';

        // Set up calls
        $XY->config['cookie_prefix'] = '';
        $XY->config['cookie_domain'] = '';
        $XY->config['cookie_path'] = '/';
        $XY->config['cookie_secure'] = false;
        $XY->config['cookie_httponly'] = false;
        $input->expects($this->once())->method('xySetCookie')->with($this->equalTo($name), $this->equalTo(''),
            $this->lessThan(time()), $this->equalTo('/'), $this->equalTo(''), $this->equalTo(false),
            $this->equalTo(false));

        // Call setCookie()
        $input->setCookie($name);
    }

    /**
     * Test setCookie() from config
     */
    public function testSetCookieConfig()
    {
        global $XY;

        // Mock Xylophone, Config, and Input
        $XY = new stdClass();
        $XY->config = array();
        $input = $this->getMock('Xylophone\core\Input', array('xySetCookie'), array(), '', false);

        // Set up args
        $name = 'config_cookie';
        $value = 'yum';
        $expire = 42;
        $prefix = 'my_';
        $domain = 'darkside.com';
        $path = '/come/to/';
        $secure = true;
        $http = true;

        // Set up calls
        $XY->config['cookie_prefix'] = $prefix;
        $XY->config['cookie_domain'] = $domain;
        $XY->config['cookie_path'] = $path;
        $XY->config['cookie_secure'] = $secure;
        $XY->config['cookie_httponly'] = $http;
        $input->expects($this->once())->method('xySetCookie')->with($this->equalTo($prefix.$name),
            $this->equalTo($value), $this->greaterThanOrEqual(time() + $expire), $this->equalTo($path),
            $this->equalTo($domain), $this->equalTo($secure), $this->equalTo($http));

        // Call setCookie()
        $input->setCookie($name, $value, $expire);
    }

    /**
     * Test setCookie() from array
     */
    public function testSetCookieArray()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', array('xySetCookie'), array(), '', false);

        // Set up args
        $name = 'array_cookie';
        $value = 'element';
        $expire = 0;
        $prefix = 'some_';
        $domain = 'vector.com';
        $path = '/dimension/';
        $secure = true;
        $http = true;
        $arg = array('name' => $name, 'value' => $value, 'expire' => $expire, 'prefix' => $prefix, 'domain' => $domain,
            'path' => $path, 'secure' => $secure, 'httponly' => $http);

        // Set up calls
        $input->expects($this->once())->method('xySetCookie')->with($this->equalTo($prefix.$name),
            $this->equalTo($value), $this->equalTo(0), $this->equalTo($path), $this->equalTo($domain),
            $this->equalTo($secure), $this->equalTo($http));

        // Call setCookie()
        $input->setCookie($arg);
    }

    /**
     * Test ipAddress() already set
     */
    public function testIpAddressSet()
    {
        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        $input->ip_address = '1.2.3.4';

        // Call ipAddress() and verify result
        $this->assertEquals($input->ip_address, $input->ipAddress());
    }

    /**
     * Test ipAddress() with no proxy
     */
    public function testIpAddressNoProxy()
    {
        global $XY;

        // Mock Xylophone, Config, and Input
        $XY = new stdClass();
        $XY->config = array();
        $input = $this->getMock('Xylophone\core\Input', array('server', 'validIp'), array(), '', false);

        // Set up arg
        $ip = '192.168.0.1';

        // Set up calls
        $input->ip_address = false;
        $XY->config['proxy_ips'] = '';
        $input->expects($this->once())->method('server')->with('REMOTE_ADDR')->will($this->returnValue($ip));
        $input->expects($this->once())->method('validIp')->with($ip)->will($this->returnValue(true));

        // Call ipAddress() and verify result
        $this->assertEquals($ip, $input->ipAddress());
    }

    /**
     * Test ipAddress() with a v4 proxy
     */
    public function testIpAddressV4Proxy()
    {
        global $XY;

        // Mock Xylophone, Config, and Input
        $XY = new stdClass();
        $XY->config = array();
        $input = $this->getMock('Xylophone\core\Input', array('server', 'validIp'), array(), '', false);

        // Set up args
        $ip = '12.34.56.78';
        $client = '98.76.54.32';
        $xclient = '123.45.67.89';

        // Set up calls
        $input->ip_address = false;
        $XY->config['proxy_ips'] = '1:2:3::4/24,192.168.1.2/24,'.$ip;
        $input->expects($this->exactly(4))->method('server')->will($this->returnValueMap(array(
            array('REMOTE_ADDR', false, $ip),
            array('HTTP_X_FORWARDED_FOR', false, null),
            array('HTTP_CLIENT_IP', false, $client),
            array('HTTP_X_CLIENT_IP', false, $xclient)
        )));
        $input->expects($this->exactly(4))->method('validIp')->will($this->returnValueMap(array(
            array($ip, '', true),
            array($client, '', false),
            array($xclient, '', true),
            array($ip, 'ipv4', true)
        )));

        // Call ipAddress() and verify result
        $this->assertEquals($xclient, $input->ipAddress());
    }

    /**
     * Test ipAddress() with a v6 proxy
     */
    public function testIpAddressV6Proxy()
    {
        global $XY;

        // Mock Xylophone, Config, and Input
        $XY = new stdClass();
        $XY->config = array();
        $input = $this->getMock('Xylophone\core\Input', array('server', 'validIp'), array(), '', false);

        // Set up args
        $ip = '12:34::56:78';
        $client = '98:76::54:32';

        // Set up calls
        $input->ip_address = false;
        $XY->config['proxy_ips'] = '1:2::3:4,'.$ip.'/24';
        $input->expects($this->exactly(2))->method('server')->will($this->returnValueMap(array(
            array('REMOTE_ADDR', false, $ip),
            array('HTTP_X_FORWARDED_FOR', false, $client),
        )));
        $input->expects($this->exactly(3))->method('validIp')->will($this->returnValueMap(array(
            array($ip, '', true),
            array($client, '', true),
            array($ip, 'ipv4', false)
        )));

        // Call ipAddress() and verify result
        $this->assertEquals($client, $input->ipAddress());
    }

    /**
     * Test validIp()
     */
    public function testValidIp()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Call validIp() and verify results
        $this->assertTrue($input->validIp('192.168.0.1'));
        $this->assertTrue($input->validIp('10.0.0.1', 'ipv4'));
        $this->assertTrue($input->validIp('12ab:34cd::56ef:7800'));
        $this->assertTrue($input->validIp('9876:fedc::5432:ba1', 'ipv6'));
        $this->assertFalse($input->validIp('1.2.3'));
        $this->assertFalse($input->validIp('ab:cd::ef:00', 'ipv4'));
        $this->assertFalse($input->validIp('f00::bar'));
        $this->assertFalse($input->validIp('172.0.0:1'), 'ipv6');
    }

    /**
     * Test userAgent() already set
     */
    public function testUserAgentSet()
    {
        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        $input->user_agent = 'some user agent';

        // Call userAgent() and verify result
        $this->assertEquals($input->user_agent, $input->userAgent());
    }

    /**
     * Test userAgent() with none
     */
    public function testUserAgentNone()
    {
        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        unset($_SERVER['HTTP_USER_AGENT']);

        // Call userAgent() and verify results
        $this->assertNull($input->userAgent());
        $this->assertNull($input->user_agent);
    }

    /**
     * Test userAgent()
     */
    public function testUserAgent()
    {
        // Set up arg
        $agent = 'Mozilla something or other';

        // Mock Input and set up call
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);
        $_SERVER['HTTP_USER_AGENT'] = $agent;

        // Call userAgent() and verify results
        $this->assertEquals($agent, $input->userAgent());
        $this->assertEquals($agent, $input->user_agent);
    }

    /**
     * Test sanitizeGlobals()
     */
    public function testSanitizeGlobals()
    {
        global $XY;

        // Mock Xylophone, Logger, and Input
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', array('cleanInputKeys', 'cleanInputData'), array(), '', false);

        // Set up args
        $gkey = 'global_get';
        $gval = 'foo';
        $pkey = 'global_post';
        $pval = 'bar';
        $ckey1 = 'global_cookie';
        $cval1 = 'baz';
        $ckey2 = 'bad_cookie';
        $cval2 = 'disappear';
        $cvkey = '$Version';
        $cpkey = '$Path';
        $cdkey = '$Domain';
        $self = 'identity';
        $_GET = array($gkey => $gval);
        $_POST = array($pkey => $pval);
        $_COOKIE = array($ckey1 => $cval1, $ckey2 => $cval2, $cvkey => '42', $cpkey => 'narrow', $cdkey => 'range');
        $GLOBALS[$gkey] = $gval;
        $GLOBALS[$pkey] = $pval;
        $GLOBALS[$ckey1] = $cval1;
        $_SERVER['PHP_SELF'] = '<p>'.$self.'</p>';

        // Set up calls
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(false));
        $XY->logger->expects($this->once())->method('debug');
        $input->expects($this->exactly(4))->method('cleanInputKeys')->will($this->returnValueMap(array(
            array($gkey, true, $gkey),
            array($pkey, true, $pkey),
            array($ckey1, true, $ckey1),
            array($ckey2, false, false)
        )));
        $input->expects($this->exactly(3))->method('cleanInputData')->will($this->returnValueMap(array(
            array($gval, true, $gval),
            array($pval, true, $pval),
            array($cval1, true, $cval1)
        )));
        $input->allow_get_array = true;
        $input->enable_csrf = false;

        // Call sanitizeGlobal() and verify results
        $input->sanitizeGlobals();
        $this->assertNull($GLOBALS[$gkey]);
        $this->assertNull($GLOBALS[$pkey]);
        $this->assertNull($GLOBALS[$ckey1]);
        $this->assertArrayNotHasKey($cvkey, $_COOKIE);
        $this->assertArrayNotHasKey($cpkey, $_COOKIE);
        $this->assertArrayNotHasKey($cdkey, $_COOKIE);
        $this->assertArrayNotHasKey($ckey2, $_COOKIE);
        $this->assertEquals($self, $_SERVER['PHP_SELF']);
    }

    /**
     * Test sanitizeGlobals() with no $_GET
     */
    public function testSanitizeGlobalsNoGet()
    {
        global $XY;

        // Mock Xylophone, Security, Logger, and Input
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp', 'isCli'), array(), '', false);
        $XY->security = $this->getMock('Xylophone\core\Security', array('csrfVerify'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', array('cleanInputKeys'), array(), '', false);

        // Set up args
        $_GET = array('foo' => 'bar');

        // Set up calls
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(true));
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $XY->security->expects($this->once())->method('csrfVerify');
        $input->expects($this->never())->method('cleanInputKeys');
        $input->allow_get_array = false;
        $input->enable_csrf = true;

        // Call sanitizeGlobals() and verify results
        $input->sanitizeGlobals();
        $this->assertEmpty($_GET);
    }

    /**
     * Test cleanInputData()
     */
    public function testCleanInputData()
    {
        global $XY;

        // Mock Xylophone, Utf8, Output, Security, and Input
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp'), array(), '', false);
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $stra = 'part_a';
        $strb = 'part_b';
        $str = $stra."\r".$strb;

        // Set up calls
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(true));
        $XY->utf8->expects($this->once())->method('cleanString')->with($this->equalTo($str))->
            will($this->returnArgument(0));
        $XY->output->expects($this->once())->method('removeInvisibleCharacters')->with($this->equalTo($str))->
            will($this->returnArgument(0));
        $XY->security->expects($this->once())->method('xssClean')->with($this->equalTo($str))->
            will($this->returnArgument(0));
        $input->enable_xss = true;

        // Call cleanInputData() and verify result
        $this->assertEquals($stra.PHP_EOL.$strb, $input->cleanInputData($str));
    }

    /**
     * Test cleanInputData() with an array
     */
    public function testCleanInputDataArray()
    {
        global $XY;

        // Mock Xylophone, Utf8, Output, and Input
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isPhp'), array(), '', false);
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', array('cleanInputKeys'), array(), '', false);

        // Set up args
        $key = 'somekey';
        $val = 'someval';
        $str = array($key => $val);

        // Set up calls
        $XY->expects($this->once())->method('isPhp')->with($this->equalTo('5.4'))->will($this->returnValue(true));
        $XY->utf8->expects($this->once())->method('cleanString')->with($this->equalTo($val))->
            will($this->returnArgument(0));
        $XY->output->expects($this->once())->method('removeInvisibleCharacters')->with($this->equalTo($val))->
            will($this->returnArgument(0));
        $input->expects($this->once())->method('cleanInputKeys')->with($this->equalTo($key))->
            will($this->returnArgument(0));

        // Call cleanInputData() and verify result
        $this->assertEquals($str, $input->cleanInputData($str));
    }

    /**
     * Test cleanInputKeys() with a clean key
     */
    public function testCleanInputKeysClean()
    {
        global $XY;

        // Mock Xylophone, Utf8, and Input
        $XY = new stdClass();
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up arg and call
        $key = 'this_1|is:a/good-key';
        $XY->utf8->expects($this->never())->method('cleanString');

        // Call cleanInputKeys(), and verify result
        $this->assertEquals($key, $input->cleanInputKeys($key));
    }

    /**
     * Test cleanInputKeys() with a dirty key
     */
    public function testCleanInputKeysDirty()
    {
        global $XY;

        // Mock Xylophone, Utf8, and Input
        $XY = new stdClass();
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up arg and call
        $key = '#badkey';
        $XY->utf8->expects($this->once())->method('cleanString')->with($this->equalTo($key))->
            will($this->returnArgument(0));

        // Call cleanInputKeys(), and verify result
        $this->assertEquals($key, $input->cleanInputKeys($key));
    }

    /**
     * Test cleanInputKeys() with a non-fatal key
     */
    public function testCleanInputKeysNonFatal()
    {
        global $XY;

        // Mock Xylophone, Utf8, and Input
        $XY = new stdClass();
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up arg and call
        $key = 'bad%key';
        $XY->utf8->expects($this->once())->method('cleanString')->with($this->equalTo($key))->
            will($this->returnValue(''));

        // Call cleanInputKeys(), and verify result
        $this->assertFalse($input->cleanInputKeys($key, false));
    }

    /**
     * Test cleanInputKeys() with a fatal key
     */
    public function testCleanInputKeysFatal()
    {
        global $XY;

        // Mock Xylophone, Utf8, and Input
        $XY = new stdClass();
        $XY->utf8 = $this->getMock('Xylophone\core\Utf8', array('cleanString'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up arg and calls
        $key = 'bad@key';
        $XY->utf8->expects($this->once())->method('cleanString')->with($this->equalTo($key))->
            will($this->returnValue(''));
        $XY->init_ob_level = ob_get_level();

        // Call cleanInputKeys(), and verify result
        $this->setExpectedException('Xylophone\core\ExitException', 'Disallowed Key Characters.');
        $input->cleanInputKeys($key);
    }

    /**
     * Test requestHeaders() when already set
     */
    public function testRequestHeadersSet()
    {
        // Mock Input and set up call
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);
        $input->headers = array('myheader' => 'headerval');

        // Call requestHeaders() and verify result
        $this->assertEquals($input->headers, $input->requestHeaders());
    }

    /**
     * Test requestHeaders()
     */
    public function testRequestHeaders()
    {
        // Set up args
        $key1 = 'HTTP_SOME_HEADER';
        $key2 = 'Some-Header';
        $val = 'someval';
        $clean = false;
        $ckey1 = 'CONTENT_TYPE';
        $ckey2 = 'Content-Type';
        $ctype = 'text/html';
        $headers = array($ckey2 => $ctype, $key2 => $val);
        $_SERVER = array($ckey1 => $ctype, $key1 => $val);

        // Mock Input and set up call
        $input = $this->getMock('Mocks\core\Input', array('fetchFromArray'), array(), '', false);
        $input->expects($this->once())->method('fetchFromArray')->
            with($this->equalTo($_SERVER), $this->equalTo($key1), $this->equalTo($clean))->
            will($this->returnValue($val));

        // Call requestHeaders() and verify result
        $this->assertEquals($headers, $input->requestHeaders($clean));
        $this->assertEquals($headers, $input->headers);
    }

    /**
     * Test getRequestHeader() with a non-existent header
     */
    public function testGetRequestHeaderNone()
    {
        // Mock Input
        $input = $this->getMock('Mocks\core\Input', array('requestHeaders'), array(), '', false);

        // Set up calls
        $input->headers = array();
        $input->expects($this->once())->method('requestHeaders');

        // Call getRequestHeader() and verify result
        $this->assertNull($input->getRequestHeader('notfound'));
    }

    /**
     * Test getRequestHeader() with xss_clean
     */
    public function testGetRequestHeaderClean()
    {
        global $XY;

        // Mock Xylophone, Security, and Input
        $XY = new stdClass();
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'clean';
        $val = 'me';

        // Set up calls
        $XY->security->expects($this->once())->method('xssClean')->with($this->equalTo($val))->
            will($this->returnArgument(0));
        $input->headers = array($index => $val);

        // Call getRequestHeader() and verify result
        $this->assertEquals($val, $input->getRequestHeader($index, true));
    }

    /**
     * Test getRequestHeader()
     */
    public function testGetRequestHeader()
    {
        global $XY;

        // Mock Xylophone, Security, and Input
        $XY = new stdClass();
        $XY->security = $this->getMock('Xylophone\core\Security', array('xssClean'), array(), '', false);
        $input = $this->getMock('Mocks\core\Input', null, array(), '', false);

        // Set up args
        $index = 'already';
        $val = 'clean';

        // Set up calls
        $XY->security->expects($this->never())->method('xssClean');
        $input->headers = array($index => $val);

        // Call getRequestHeader() and verify result
        $this->assertEquals($val, $input->getRequestHeader($index));
    }

    /**
     * Test isAjaxRequest()
     */
    public function testIsAjaxRequest()
    {
        // Mock Input
        $input = $this->getMock('Xylophone\core\Input', null, array(), '', false);

        // Set empty and verify result
        $_SERVER['HTTP_X_REQUESTED_WITH'] = '';
        $this->assertFalse($input->isAjaxRequest());

        // Set other and verify result
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'http';
        $this->assertFalse($input->isAjaxRequest());

        // Set AJAX and verify result
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $this->assertTrue($input->isAjaxRequest());
    }

    /**
     * Test method()
     */
    public function testMethod()
    {
        // Set up arg
        $val = 'CustomRequest';

        // Mock Input and set up calls
        $input = $this->getMock('Xylophone\core\Input', array('server'), array(), '', false);
        $input->expects($this->exactly(2))->method('server')->with($this->equalTo('REQUEST_METHOD'))->
            will($this->returnValue($val));

        // Call method() and verify results
        $this->assertEquals(strtolower($val), $input->method());
        $this->assertEquals(strtoupper($val), $input->method(true));
    }
}

