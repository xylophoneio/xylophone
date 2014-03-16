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
 * Exceptions Unit Test
 *
 * @package Xylophone
 */
class ExceptionsTest extends XyTestCase
{
    /**
     * Test logException()
     */
    public function testLogException()
    {
        global $XY;

        // Set up args
        $sev = E_ERROR;
        $msg = 'Test Message';
        $path = '/some/bad/file';
        $line = 42;

        // Mock Xylophone, Logger, and Exceptions and set up call
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', null, array(), '', false);
        $XY->logger->expects($this->once())->method('error')->
            with($this->equalTo('Severity: Error --> '.$msg.' '.$path.' '.$line));

        // Call logException()
        $exceptions->logException($sev, $msg, $path, $line);
    }

    /**
     * Test logException() with an unknown severity
     */
    public function testLogExceptionUnknown()
    {
        global $XY;

        // Set up args
        $sev = 0;
        $msg = 'Test Message';
        $path = '/some/bad/file';
        $line = 42;

        // Mock Xylophone, Logger, and Exceptions and set up call
        $XY = new stdClass();
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', null, array(), '', false);
        $XY->logger->expects($this->once())->method('error')->
            with($this->equalTo('Severity: '.$sev.' --> '.$msg.' '.$path.' '.$line));

        // Call logException()
        $exceptions->logException($sev, $msg, $path, $line);
    }

    /**
     * Test show404()
     */
    public function testShow404()
    {
        global $XY;

        // Set up args
        $page = 'unavailable/page';
        $heading = '404 Page Not Found';
        $retval = 'Formatted';

        // Mock Xylophone, Logger, and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->logger = $this->getMock('Xylophone\core\Logger', array('error'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $XY->logger->expects($this->once())->method('error')->with($this->equalTo($heading.': '.$page));
        $exceptions->expects($this->once())->method('formatError')->with($this->equalTo($heading),
            $this->stringContains('not found'), $this->equalTo('error_404'), $this->equalTo(array('page' => $page)))->
            will($this->returnValue($retval));

        // Set exception and call show404()
        $this->setExpectedException('Xylophone\core\ExitException', $retval,
            Xylophone\core\Xylophone::EXIT_UNKNOWN_FILE);
        $exceptions->show404($page, true);
    }

    /**
     * Test show404() on CLI
     */
    public function testShow404Cli()
    {
        global $XY;

        // Set up args
        $page = 'unavailable/page';
        $heading = 'Not Found';
        $retval = 'Error';

        // Mock Xylophone and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(true));
        $exceptions->expects($this->once())->method('formatError')->with($this->equalTo($heading),
            $this->stringContains('not found'), $this->equalTo('error_404'), $this->equalTo(array('page' => $page)))->
            will($this->returnValue($retval));

        // Set exception and call show404()
        $this->setExpectedException('Xylophone\core\ExitException', $retval,
            Xylophone\core\Xylophone::EXIT_UNKNOWN_FILE);
        $exceptions->show404($page);
    }

    /**
     * Test showDbError()
     */
    public function testShowDbError()
    {
        global $XY;

        // Set up args
        $app_path = '/some/app/path/';
        $sys_path = '/some/sys/path/';
        $heading = 'DB Failure';
        $line_a = 'Your ';
        $line_b = ' is bad';
        $index = 'db_test_line';
        $line = 'This is a test';
        $error = array($line_a.'%s'.$line_b, $index);
        $swap = 'argument';
        $file = 'models/sub/DataGetter.php';
        $line = 42;
        $trace = array(
            array('file' => '/path/libraries/Database.php', 'line' => 1234),
            array('file' => '/path/libraries/Loader.php', 'line' => 567),
            array('file' => $app_path.$file, 'line' => $line)
        );
        $message = array($line_a.$swap.$line_b, $line, 'Filename: '.$file, 'Line Number: '.$line);
        $format = 'Output';
        $docs = 'http://somedomain.com/docs';
        $args = array('link' => $docs.'/libraries/Database');

        // Mock Xylophone, Lang, and Exceptions
        $XY = new stdClass();
        $XY->lang = $this->getMock('Xylophone\core\Lang', array('load', 'line'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('getTrace', 'formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $XY->config = array('docs_url' => $docs);
        $XY->app_path = $app_path;
        $XY->system_path = $sys_path;
        $XY->lang->expects($this->once())->method('load')->with($this->equalTo('db'));
        $XY->lang->expects($this->exactly(2))->method('line')->will($this->returnValueMap(array(
            array('db_error_heading', true, $heading),
            array($index, true, $line)
        )));
        $exceptions->expects($this->once())->method('getTrace')->will($this->returnValue($trace));
        $exceptions->expects($this->once())->method('formatError')->with($this->equalTo($heading),
            $this->equalTo($message), $this->equalTo('error_db'), $this->equalTo($args))->
            will($this->returnValue($format));

        $this->setExpectedException('Xylophone\core\ExitException', $format, Xylophone\core\Xylophone::EXIT_DATABASE);
        $exceptions->showDbError($error, $swap);
    }

    /**
     * Test showError()
     */
    public function testShowError()
    {
        global $XY;

        // Set up args
        $heading = 'Failure Is Always An Option';
        $message = 'Something broke';
        $template = 'error_test';
        $response = 204;
        $dir = 'core';
        $class = 'TestClass';
        $docs = 'http://docserver.com/docs';
        $trace = array(array('class' => 'Some\\'.$dir.'\\'.$class));
        $args = array('link' => $docs.'/'.$dir.'/'.$class);
        $format = 'Error Page';

        // Mock Xylophone, Config, and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $XY->config = array();
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('getTrace', 'formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $XY->config['docs_url'] = $docs;
        $exceptions->expects($this->once())->method('getTrace')->will($this->returnValue($trace));
        $exceptions->expects($this->once())->method('formatError')->
            with($this->equalTo($heading), $this->equalTo($message), $this->equalTo($template), $this->equalTo($args))->
            will($this->returnValue($format));

        // Catch exception and test
        try {
            $exceptions->showError($heading, $message, $template, $response);
        } catch (Xylophone\core\ExitException $ex) {
            $this->assertEquals($format, $ex->getMessage());
            $this->assertEquals(Xylophone\core\Xylophone::EXIT_ERROR, $ex->getCode());
            $this->assertEquals($response, $ex->getResponse());
            $this->assertEquals(Xylophone\core\Xylophone::$status_codes[$response], $ex->getHeader());
        }
    }

    /**
     * Test showError() with an auto exit code
     */
    public function testShowErrorAuto()
    {
        global $XY;

        // Set up args
        $heading = 'Auto Code Error';
        $message = 'You got a fancy exit code';
        $template = 'error_auto';
        $response = 3;
        $trace = array();
        $args = array();
        $format = 'Automagic';

        // Mock Xylophone and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', null, array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('getTrace', 'formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $exceptions->expects($this->once())->method('getTrace')->will($this->returnValue($trace));
        $exceptions->expects($this->once())->method('formatError')->
            with($this->equalTo($heading), $this->equalTo($message), $this->equalTo($template), $this->equalTo($args))->
            will($this->returnValue($format));

        // Catch exception and test
        try {
            $exceptions->showError($heading, $message, $template, $response);
        } catch (Xylophone\core\ExitException $ex) {
            $this->assertEquals($format, $ex->getMessage());
            $this->assertEquals(Xylophone\core\Xylophone::EXIT__AUTO_MIN + $response, $ex->getCode());
            $this->assertEquals(500, $ex->getResponse());
            $this->assertEquals(Xylophone\core\Xylophone::$status_codes[500], $ex->getHeader());
        }
    }

    /**
     * Test showError() with a generic exit code
     */
    public function testShowErrorGeneric()
    {
        global $XY;

        // Set up args
        $heading = 'Generic Error';
        $message = 'Typical';
        $template = 'error_plain';
        $response = -242;
        $trace = array();
        $args = array();
        $format = 'Generic Output';

        // Mock Xylophone and Exceptions
        $XY = new stdClass();
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('getTrace', 'formatError'), array(), '', false);

        // Set up calls
        $XY->init_ob_level = ob_get_level();
        $exceptions->expects($this->once())->method('getTrace')->will($this->returnValue($trace));
        $exceptions->expects($this->once())->method('formatError')->
            with($this->equalTo($heading), $this->equalTo($message), $this->equalTo($template), $this->equalTo($args))->
            will($this->returnValue($format));

        // Catch exception and test
        try {
            $exceptions->showError($heading, $message, $template, $response);
        } catch (Xylophone\core\ExitException $ex) {
            $this->assertEquals($format, $ex->getMessage());
            $this->assertEquals(Xylophone\core\Xylophone::EXIT_ERROR, $ex->getCode());
            $this->assertEquals(500, $ex->getResponse());
            $this->assertEquals('Internal Server Error', $ex->getHeader());
        }
    }

    /**
     * Test showPhpError()
     */
    public function testShowPhpError()
    {
        global $XY;

        // Set up args
        $severity = 42;
        $sev_name = 'Oops';
        $message = 'Typical';
        $file = 'my/file.php';
        $line = 123;
        $args = array('severity' => $sev_name, 'filepath' => $file, 'line' => $line);
        $format = 'PHP Error';

        // Mock Xylophone and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', array('formatError'), array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $exceptions->levels[$severity] = $sev_name;
        $exceptions->expects($this->once())->method('formatError')->with($this->equalTo('PHP Error'),
            $this->equalTo($message), $this->equalTo('error_php'), $this->equalTo($args))->
            will($this->returnValue($format));

        // Call showPhpError and verify output
        $this->expectOutputString($format);
        $exceptions->showPhpError($severity, $message, '/path/to/'.$file, $line);
    }

    /**
     * Test formatError()
     */
    public function testFormatError()
    {
        global $XY;

        // Set up args
        $heading = 'Test Error Format';
        $msg1 = 'Error1';
        $msg2 = 'Error2';
        $template = 'error_format';
        $name1 = 'foo';
        $name2 = 'bar';
        $name3 = 'baz';
        $args = array($name1 => 'far', $name2 => 'faz', $name3 => 'boo');

        // Init filesystem and make template
        $this->vfsInit();
        $content = '<?php echo $heading.\' \'.$message.\' \'.$'.$name1.'.\' \'.$'.$name2.'.\' \'.$'.$name3.';';
        $dir = $this->vfsMkdir('third_party/views', $this->vfs_base_dir);
        $view = $this->vfsCreate('errors/html/'.$template.'.php', $content, $dir);

        // Mock Xylophone and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', null, array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $XY->view_paths = array($this->vfs_app_path.'/views/', $dir->url().'/');

        // Call formatError() and verify results
        $output = $heading.' <p>'.$msg1.'</p><p>'.$msg2.'</p> '.implode(' ', $args);
        $this->assertEquals($output, $exceptions->formatError($heading, array($msg1, $msg2), $template, $args));
    }

    /**
     * Test formatError() for CLI
     */
    public function testFormatErrorCli()
    {
        global $XY;

        // Set up args
        $heading = 'Test Error CLI';
        $message = 'Error message';
        $template = 'error_cli';

        // Init filesystem and make template
        $this->vfsInit();
        $content = '<?php echo $heading.\' \'.$message;';
        $view = $this->vfsCreate('views/errors/cli/'.$template.'.php', $content, $this->vfs_app_dir);

        // Mock Xylophone and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', null, array(), '', false);

        // Set up calls
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(true));
        $XY->view_paths = array($this->vfs_app_path.'/views/');

        // Call formatError() and verify results
        $output = $heading." \t".$message;
        $this->assertEquals($output, $exceptions->formatError($heading, $message, $template));
    }

    /**
     * Test formatError() with an override
     */
    public function testFormatErrorOverride()
    {
        global $XY;

        // Set up args
        $heading = 'Test Error Override';
        $message = 'Bad things happened';
        $template = 'error_over';
        $args1 = array('one' => '1', 'two' => '2');
        $args2 = array('three' => '3', 'four' => '4');
        $route1 = array('class' => 'test', 'args' => $args1);
        $route2 = array(
            'class' => 'test',
            'args' => array_merge(array($heading, '<p>'.$message.'</p>'), $args1, $args2)
        );

        // Mock Xylophone, Router, Loader, and Exceptions
        $XY = $this->getMock('Xylophone\core\Xylophone', array('isCli'), array(), '', false);
        $XY->router = $this->getMock('Xylophone\core\Router', array('getErrorRoute'), array(), '', false);
        $XY->load = $this->getMock('Xylophone\core\Loader', array('controller'), array(), '', false);
        $exceptions = $this->getMock('Xylophone\core\Exceptions', null, array(), '', false);

        // Set up calls
        $XY->routed = new stdClass();
        $XY->expects($this->once())->method('isCli')->will($this->returnValue(false));
        $XY->router->expects($this->once())->method('getErrorRoute')->with($this->equalTo($template))->
            will($this->returnValue($route1));
        $XY->load->expects($this->once())->method('controller')->
            with($this->equalTo($route2), $this->equalTo('routed'))->will($this->returnValue(true));

        // Call formatError() and verify results
        $exceptions->formatError($heading, $message, $template, $args2);
        $this->assertObjectNotHasAttribute('routed', $XY);
    }
}

