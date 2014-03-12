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
 * Security Unit Test
 *
 * @package Xylophone
 */
class SecurityTest extends XyTestCase
{
    /**
     * Test __construct()
     */
    public function testConstruct()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Security
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security', array('csrfSetHash'), array(), '', false);

        // Set up args
        $expire = 2600;
        $token = 'skeeball';
        $cookie = 'monster';
        $prefix = 'my_';

        // Set up calls
        $XY->config['csrf_protection'] = true;
        $XY->config['csrf_expire'] = $expire;
        $XY->config['csrf_token_name'] = $token;
        $XY->config['csrf_cookie_name'] = $cookie;
        $XY->config['cookie_prefix'] = $prefix;
        $XY->logger->expects($this->once())->method('debug');
        $security->expects($this->once())->method('csrfSetHash');

        // Call __construct() and verify results
        $security->__construct();
        $this->assertEquals($expire, $security->csrf_expire);
        $this->assertEquals($token, $security->csrf_token_name);
        $this->assertEquals($prefix.$cookie, $security->csrf_cookie_name);
    }

    /**
     * Test csrfVerify() with GET
     */
    public function testCsrfVerifyGet()
    {
        // Mock Security
        $security = $this->getMock('Xylophone\core\Security', array('csrfSetCookie'), array(), '', false);

        // Set up calls
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $security->expects($this->once())->method('csrfSetCookie')->will($this->returnSelf());

        // Call csrfVerify() and verify result
        $this->assertSame($security, $security->csrfVerify());
    }

    /**
     * Test csrfVerify() with an excluded URI
     */
    public function testCsrfVerifyExclude()
    {
        global $XY;

        // Mock Xylophone, Config, Uri, and Security
        $XY = new stdClass();
        $XY->config = array();
        $XY->uri = $this->getMock('Xylophone\core\Uri', array('uriString'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up arg
        $uri = 'some/safe/path';

        // Set up calls
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $XY->config['csrf_exclude_uris'] = array($uri);
        $XY->uri->expects($this->once())->method('uriString')->will($this->returnValue($uri));

        // Call csrfVerify() and verify result
        $this->assertSame($security, $security->csrfVerify());
    }

    /**
     * Test csrfVerify() with a mismatch
     */
    public function testCsrfVerifyMismatch()
    {
        global $XY;

        // Mock Xylophone, Config, and Security
        $XY = new stdClass();
        $XY->config = array();
        $security = $this->getMock('Xylophone\core\Security', array('csrfShowError'), array(), '', false);

        // Set up args
        $token = 'bitcoin';
        $cookie = 'sugar';

        // Set up calls
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$token] = 'one';
        $_COOKIE[$cookie] = 'two';
        $XY->config['csrf_exclude_uris'] = false;
        $security->csrf_token_name = $token;
        $security->csrf_cookie_name = $cookie;
        $security->expects($this->once())->method('csrfShowError')->
            will($this->throwException(new InvalidArgumentException));

        // Call csrfVerify() and verify result
        $this->setExpectedException('InvalidArgumentException');
        $security->csrfVerify();
    }

    /**
     * Test csrfVerify()
     */
    public function testCsrfVerify()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Security
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $security = $this->getMock('Mocks\core\Security', array('csrfSetHash', 'csrfSetCookie'), array(), '', false);

        // Set up args
        $token = 'toll';
        $cookie = 'chocolate';
        $val = 'same';

        // Set up calls
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$token] = $val;
        $_COOKIE[$cookie] = $val;
        $XY->config['csrf_exclude_uris'] = false;
        $XY->config['csrf_regenerate'] = true;
        $security->csrf_token_name = $token;
        $security->csrf_cookie_name = $cookie;
        $security->csrf_hash = 'old';
        $security->expects($this->once())->method('csrfSetHash');
        $security->expects($this->once())->method('csrfSetCookie');
        $XY->logger->expects($this->once())->method('debug');

        // Call csrfVerify() and verify results
        $this->assertSame($security, $security->csrfVerify());
        $this->assertArrayNotHasKey($token, $_POST);
        $this->assertArrayNotHasKey($cookie, $_COOKIE);
        $this->assertEquals('', $security->csrf_hash);
    }

    /**
     * Test csrfSetCookie() with a secure failure
     */
    public function testCsrfSetCookieSecureFail()
    {
        global $XY;

        // Mock Xylophone, Config, and Security
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isHttps'), array(), '', false);
        $XY->config = array();
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up calls
        $XY->config['cookie_secure'] = true;
        $XY->expects($this->once())->method('isHttps')->will($this->returnValue(false));

        // Call csrfSetCookie() and verify result
        $this->assertFalse($security->csrfSetCookie());
    }

    /**
     * Test csrfSetCookie()
     */
    public function testCsrfSetCookie()
    {
        global $XY;

        // Mock Xylophone, Config, Logger, and Security
        $XY = new stdClass();
        $XY->config = array();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $security = $this->getMock('Mocks\core\Security', array('xySetCookie'), array(), '', false);

        // Set up args
        $secure = false;
        $cookie = 'macadamia';
        $hash = 'mark';
        $expire = 12345;
        $path = 'home';
        $domain = 'virtual';
        $http = true;

        // Set up calls
        $XY->config['cookie_secure'] = $secure;
        $XY->config['cookie_path'] = $path;
        $XY->config['cookie_domain'] = $domain;
        $XY->config['cookie_httponly'] = $http;
        $security->csrf_expire = $expire;
        $security->csrf_cookie_name = $cookie;
        $security->csrf_hash = $hash;
        $security->expects($this->once())->method('xySetCookie')->with($this->equalTo($cookie), $this->equalTo($hash),
            $this->greaterThanOrEqual(time() + $expire), $this->equalTo($path), $this->equalTo($domain),
            $this->equalTo($secure), $this->equalTo($http));
        $XY->logger->expects($this->once())->method('debug');

        // Call csrfSetCookie() and verify result
        $this->assertSame($security, $security->csrfSetCookie());
    }

    /**
     * Test csrfShowError()
     */
    public function testCsrfShowError()
    {
        global $XY;

        // Mock Xylophone and Security
        $XY = $this->getMock('Xylophone\core\Xylophone', array('showError'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up call
        $XY->expects($this->once())->method('showError')->
            with($this->equalTo('The action you have requested is not allowed.'));

        // Call csrfShowError()
        $security->csrfShowError();
    }

    /**
     * Test getCsrfHash()
     */
    public function testGetCsrfHash()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up call
        $security->csrf_hash = 'browns';

        // Call getCsrfHash() and verify result
        $this->assertEquals($security->csrf_hash, $security->getCsrfHash());
    }

    /**
     * Test getCsrfTokenName()
     */
    public function testGetCsrfTokenName()
    {
        // Mock Security
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up call
        $security->csrf_token_name = 'secret_token';

        // Call getCsrfTokenName() and verify result
        $this->assertEquals($security->csrf_token_name, $security->getCsrfTokenName());
    }

    /**
     * Test xssClean()
     */
    public function testXssClean()
    {
        global $XY;

        // Mock Xylophone, Output, Logger, and Security
        $XY = new stdClass();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security', array('validateEntities', 'convertAttribute',
            'decodeEntity', 'doNeverAllowed', 'compactExplodedWords', 'jsLinkRemoval', 'jsImgRemoval',
            'removeEvilAttributes', 'sanitizeNaughtyHtml'), array(), '', false);

        // Set up args
        $is_image = false;
        $leadin = 'beforestuff ';
        $lnk_attr = 'href="some%6C%69%6E%6B.com"';
        $lnk_attr2 = 'href="somelink.com"';
        $link = '<a '.$lnk_attr.'>';
        $link2 = '<a '.$lnk_attr2.'>';
        $linkend = 'somelink</a>';
        $img_attr = 'src="someimg.png"';
        $img = '<img '.$img_attr.' />';
        $tabs = "\t\t";
        $spcs = '  ';
        $scr_spc = 's c r i p t';
        $scr_nsp = 'script';
        $scr_attr = 'type="evil"';
        $scr_tag = '<'.$scr_spc.' '.$scr_attr.' />';
        $scr_rem = '[removed]';
        $htm_type = 'object';
        $htm_attr = 'type="malicious"';
        $html = '<'.$htm_type.' '.$htm_attr.'>';
        $open = '<?';
        $open2 = '&lt;?';
        $close = '?'.'>';
        $close2 = '?&gt;';
        $func = ' unlink';
        $parm = '"somefile"';

        // The original string
        $str = $leadin.$link.$linkend.' '.$img.$tabs.$scr_tag.$html.$open.$func.'('.$parm.')'.$close;

        // decodeEntity gets from the first tag on, urldecoded
        $str_ent = $link2.$linkend.' '.$img.$tabs.$scr_tag.$html.$open.$func.'('.$parm.')'.$close;

        // After decoding, it looks like this
        $str_dec = $leadin.$str_ent;

        // Midway, removeEvilAttributes gets a copy with no tabs, no tags, compacted, script removed
        $str_mid = $leadin.$linkend.' '.$spcs.$scr_rem.$html.$open2.$func.'('.$parm.')'.$close2;

        // At the end, we have also removed parens
        $str_end = $leadin.$linkend.' '.$spcs.$scr_rem.$html.$open2.$func.'&#40;'.$parm.'&#41;'.$close2;

        // Set up calls
        $XY->output->expects($this->exactly(2))->method('removeInvisibleCharacters')->will($this->returnArgument(0));
        $security->expects($this->once())->method('validateEntities')->with($this->equalTo($str))->
            will($this->returnArgument(0));
        $security->expects($this->exactly(4))->method('convertAttribute')->will($this->returnValueMap(array(
            array(array($lnk_attr2, '"'), $lnk_attr2),
            array(array($img_attr, '"'), $img_attr),
            array(array($scr_attr, '"'), $scr_attr),
            array(array($htm_attr, '"'), $htm_attr)
        )));
        $security->expects($this->once())->method('decodeEntity')->with($this->equalTo(array($str_ent)))->
            will($this->returnValue($str_ent));
        $security->expects($this->exactly(2))->method('doNeverAllowed')->will($this->returnArgument(0));
        $security->expects($this->once())->method('compactExplodedWords')->
            with($this->equalTo(array($scr_spc.' ', $scr_spc, ' ')))->will($this->returnValue($scr_nsp.' '));
        $security->expects($this->once())->method('jsLinkRemoval')->with($this->equalTo(array($link2, ' '.$lnk_attr2)));
        $security->expects($this->once())->method('jsImgRemoval')->with($this->equalTo(array($img, ' '.$img_attr)));
        $security->expects($this->once())->method('removeEvilAttributes')->
            with($this->equalTo($str_mid), $this->equalTo($is_image))->will($this->returnArgument(0));
        $security->expects($this->once())->method('sanitizeNaughtyHtml')->
            with($this->equalTo(array($html, '', $htm_type, ' '.$htm_attr, '>')))->will($this->returnValue($html));
        $XY->logger->expects($this->once())->method('debug');

        // Call xssClean() and verify result
        $this->assertEquals($str_end, $security->xssClean($str, $is_image));
    }

    /**
     * Test xssClean() with an image
     */
    public function testXssCleanImage()
    {
        global $XY;

        // Mock Xylophone, Output, and Security
        $XY = new stdClass();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security',
            array('validateEntities', 'doNeverAllowed', 'removeEvilAttributes'), array(), '', false);

        // Set up args
        $is_image = true;
        $bin = '010101010100101010101111111';
        $str = $bin.'<?php'.$bin;
        $clean = $bin.'&lt;?php'.$bin;

        // Set up calls
        $XY->output->expects($this->exactly(2))->method('removeInvisibleCharacters')->will($this->returnArgument(0));
        $security->expects($this->once())->method('validateEntities')->will($this->returnArgument(0));
        $security->expects($this->exactly(2))->method('doNeverAllowed')->will($this->returnArgument(0));
        $security->expects($this->once())->method('removeEvilAttributes')->
            with($this->equalTo($clean), $this->equalTo($is_image))->will($this->returnArgument(0));

        // Call xssClean() and verify result
        $this->assertFalse($security->xssClean($str, $is_image));
    }

    /**
     * Test xssClean() with an array
     */
    public function testXssCleanArray()
    {
        global $XY;

        // Mock Xylophone, Output, Logger, and Security
        $XY = new stdClass();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('debug'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security',
            array('validateEntities', 'doNeverAllowed', 'removeEvilAttributes'), array(), '', false);

        // Set up args
        $str = array("\tfoobar", '%42%61%7A');
        $clean = array(' foobar', 'Baz');

        // Set up calls
        $XY->output->expects($this->exactly(4))->method('removeInvisibleCharacters')->will($this->returnArgument(0));
        $security->expects($this->exactly(2))->method('validateEntities')->will($this->returnArgument(0));
        $security->expects($this->exactly(4))->method('doNeverAllowed')->will($this->returnArgument(0));
        $security->expects($this->exactly(2))->method('removeEvilAttributes')->will($this->returnArgument(0));
        $XY->logger->expects($this->exactly(2))->method('debug');

        // Call xssClean() and verify result
        $this->assertEquals($clean, $security->xssClean($str));
    }

    /**
     * Test xssHash() when it exists
     */
    public function testXssHashExists()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up call
        $security->xss_hash = 'exists';

        // Call xssHash() and verify result
        $this->assertEquals($security->xss_hash, $security->xssHash());
    }

    /**
     * Test xssHash()
     */
    public function testXssHash()
    {
        // Mock Security
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Call xssHash() and verify results
        $hash = $security->xssHash();
        $this->assertEquals(32, strlen($hash));
        $this->assertTrue(ctype_xdigit($hash));
    }

    /**
     * Test entityDecode() with no entities
     */
    public function testEntityDecodeEmpty()
    {
        // Mock Security
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Call entityDecode() and verify result
        $str = 'Nothing to see here';
        $this->assertEquals($str, $security->entityDecode($str));
    }

    /**
     * Test entityDecode()
     */
    public function testEntityDecode()
    {
        global $XY;

        // Mock Xylophone, Config, and Security
        $XY = new stdClass();
        $XY->config = array();
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up call
        $XY->config['charset'] = 'UTF-8';
        $str = 'THIS &amp;&#103&#x074&#59 &quot;that&quot;';
        $clean = 'THIS > "that"';

        // Call entityDecode() and verify result
        $this->assertEquals($clean, $security->entityDecode($str));
    }

    /**
     * Test sanitizeFilename()
     */
    public function testSanitizeFilename()
    {
        global $XY;

        // Mock Xylophone, Output, and Security
        $XY = new stdClass();
        $XY->output = $this->getMock('Xylophone\core\Output', array('removeInvisibleCharacters'), array(), '', false);
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up args
        $bad = '../<!----><>\'"&$#{}[]=;?%20%22%3c%253c%3e%0e%28%29%2528%26%24%3f%3b%3d/';
        $good = 'nice_file.txt';
        $str = $bad.$good;

        // Set up call
        $XY->output->expects($this->once())->method('removeInvisibleCharacters')->
            with($this->equalTo($str), $this->equalTo(false))->will($this->returnArgument(0));

        // Call sanitizeFIlename() and verify result
        $this->assertEquals($good, $security->sanitizeFilename($str));
    }

    /**
     * Test stripImageTags()
     */
    public function testStripImageTags()
    {
        // Mock Security
        $security = $this->getMock('Xylophone\core\Security', null, array(), '', false);

        // Set up args
        $src = 'some_source.png';
        $str = '<img class="hidden" src="'.$src.'" style="display: none" />';

        // Call stripImageTags() and verify result
        $this->assertEquals($src, $security->stripImageTags($str));
    }

    /**
     * Test compactExplodedWords()
     */
    public function testCompactExplodedWords()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $str = 'e x p l o d e d';
        $com = 'exploded';
        $aft = ' ';

        // Call compactExplodedWords() and verify result
        $this->assertEquals($com.$aft, $security->compactExplodedWords(array('', $str, $aft)));
    }

    /**
     * Test removeEvilAttributes()
     */
    public function testRemoveEvilAttributes()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $bad1 = ' formaction="bad"';
        $bad2 = "\tonsubmit=\"evil\"";
        $bad3 = ' onclick=nefarious';
        $bad4 = "\nstyle=\"despicable\"";
        $bad5 = ' xmlns=danger';
        $ok1 = '<form class="ok"';
        $ok2 = ' method="post">myxmlns=foo<input';
        $ok3 = ' name=bar';
        $ok4 = ' readonly=readonly value="style=none" />this < that</form';
        $ok5 = '>';
        $str = $ok1.$bad1.$bad2.$ok2.$bad3.$ok3.$bad4.$ok4.$bad5.$ok5;
        $clean = $ok1.$ok2.$ok3.$ok4.$ok5;

        // Call removeEvilAttributes() and verify result
        $this->assertEquals($clean, $security->removeEvilAttributes($str, false));
    }

    /**
     * Test sanitizeNaughtyHtml()
     */
    public function testSanitizeNaughtyHtml()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $arg1 = 'tag';
        $arg2 = ' ';
        $arg3 = 'arg=val';
        $args = array('<', $arg1, $arg2, $arg3, '>');
        $ret1 = '&lt;'.$arg1.$arg2.$arg3.'&gt;';
        $ret2 = '&lt;'.$arg1.$arg2.$arg3.'&lt;';

        // Call sanitizeNaughtyHtml() and verify results
        $this->assertEquals($ret1, $security->sanitizeNaughtyHtml($args));
        $args[4] = '<';
        $this->assertEquals($ret2, $security->sanitizeNaughtyHtml($args));
    }

    /**
     * Test jsLinkRemoval()
     */
    public function testJsLinkRemoval()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', array('filterAttributes'), array(), '', false);

        // Set up args
        $attr1 = ' class=clickable';
        $attr2 = ' target="goodness"';
        $evil = ' href="javascript:do_evil()"';
        $attrs = $attr1.$evil.$attr2;
        $open = '<a';
        $close = '>';
        $link = $open.$attrs.$close;
        $clean = $open.$attr1.$attr2.$close;

        // Set up call
        $security->expects($this->once())->method('filterAttributes')->with($this->equalTo($attrs))->
            will($this->returnArgument(0));

        // Call jsLinkRemoval() and verify result
        $this->assertEquals($clean, $security->jsLinkRemoval(array($link, $attrs)));
    }

    /**
     * Test jsImgRemoval()
     */
    public function testJsImgRemoval()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', array('filterAttributes'), array(), '', false);

        // Set up args
        $attr1 = ' class=foo';
        $attr2 = ' alt="badness"';
        $evil = ' src="javascript:do_bad()"';
        $attrs = $attr1.$evil.$attr2;
        $open = '<img';
        $close = ' />';
        $img = $open.$attrs.$close;
        $clean = $open.$attr1.$attr2.$close;

        // Set up call
        $security->expects($this->once())->method('filterAttributes')->with($this->equalTo($attrs))->
            will($this->returnArgument(0));

        // Call jsImgRemoval() and verify result
        $this->assertEquals($clean, $security->jsImgRemoval(array($img, $attrs)));
    }

    /**
     * Test convertAttribute()
     */
    public function testConvertAttribute()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $junk = '<\>';
        $clean = '&lt;\\\\&gt;';
        $start = 'foobar="';
        $end = '"';

        // Call convertAttribute() and verify results
        $this->assertEquals($start.$clean.$end, $security->convertAttribute(array($start.$junk.$end, $end)));
    }

    /**
     * Test filterAttributes()
     */
    public function testFilterAttributes()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $str = 'foo=bar baz = "stuff n\' things" other=\'more\'';
        $clean = 'foo="bar" baz="stuff n\' things" other="more"';

        // Call filterAttributes() and verify result
        $this->assertEquals($clean, $security->filterAttributes($str));
    }

    /**
     * Test decodeEntity()
     */
    public function testDecodeEntity()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', array('entityDecode'), array(), '', false);

        // Set up arg and call
        $str = '<sometag>';
        $security->expects($this->once())->method('entityDecode')->with($this->equalTo($str))->
            will($this->returnArgument(0));

        // Call decodeEntity() and verify results
        $this->assertEquals($str, $security->decodeEntity(array($str)));
    }

    /**
     * Test validateEntities()
     */
    public function testValidateEntities()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', array('xssHash'), array(), '', false);

        // Set up calls
        $security->expects($this->exactly(2))->method('xssHash')->will($this->returnValue('foobar'));

        // Set up args
        $str = '?&#42 &this=that&#x7A';
        $clean = '?&#42; &this=that&#x7A;';

        // Call validateEntities()
        $this->assertEquals($clean, $security->validateEntities($str));
    }

    /**
     * Test doNeverAllowed()
     */
    public function testDoNeverAllowed()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up args
        $bad1 = '<!--';
        $good1 = '&lt;!--';
        $bad2 = '-->';
        $good2 = '--&gt;';
        $bad3 = 'window.location';
        $bad4 = '"data:f00ba5b4zbase6402507205825,4242424242"';
        $rem = '[removed]';
        $ok1 = 'some';
        $ok2 = 'allowed';
        $ok3 = 'parts';
        $str = $bad1.$ok1.$bad2.$ok2.$bad3.$ok3.$bad4;
        $clean = $good1.$ok1.$good2.$ok2.$rem.$ok3.$rem;

        // Call doNeverAllowed()
        $this->assertEquals($clean, $security->doNeverAllowed($str));
    }

    /**
     * Test csrfSetHash() when existing
     */
    public function testCsrfSetHashExists()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up call
        $security->csrf_hash = 'foobar';

        // Call csrfSetHash() and verify result
        $this->assertEquals($security->csrf_hash, $security->csrfSetHash());
    }

    /**
     * Test csrfSetHash() from cookie
     */
    public function testCsrfSetHashCookie()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', null, array(), '', false);

        // Set up calls
        $name = 'mycsrfcookie';
        $hash = '0123456789abcdef0123456789abcdef';
        $security->csrf_cookie_name = $name;
        $_COOKIE[$name] = $hash;

        // Call csrfSetHash() and verify results
        $this->assertEquals($hash, $security->csrfSetHash());
        $this->assertEquals($hash, $security->csrf_hash);
    }

    /**
     * Test csrfSetHash()
     */
    public function testCsrfSetHash()
    {
        // Mock Security
        $security = $this->getMock('Mocks\core\Security', array('csrfSetCookie'), array(), '', false);

        // Set up call
        $security->expects($this->once())->method('csrfSetCookie');

        // Call csrfSetHash() and verify results
        $hash = $security->csrfSetHash();
        $this->assertEquals($hash, $security->csrf_hash);
        $this->assertEquals(32, strlen($hash));
        $this->assertTrue(ctype_xdigit($hash));
    }
}

