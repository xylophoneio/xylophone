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
namespace Xylophone\core;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exceptions Class
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/exceptions.html
 */
class Exceptions
{
    /** @var    array   List if available error levels */
    public $levels = array(
        E_ERROR             =>  'Error',
        E_WARNING           =>  'Warning',
        E_PARSE             =>  'Parsing Error',
        E_NOTICE            =>  'Notice',
        E_CORE_ERROR        =>  'Core Error',
        E_CORE_WARNING      =>  'Core Warning',
        E_COMPILE_ERROR     =>  'Compile Error',
        E_COMPILE_WARNING   =>  'Compile Warning',
        E_USER_ERROR        =>  'User Error',
        E_USER_WARNING      =>  'User Warning',
        E_USER_NOTICE       =>  'User Notice',
        E_STRICT            =>  'Runtime Notice'
    );

    /**
     * Exception Logger
     *
     * Logs PHP generated error messages
     *
     * @used-by Xylophone::exceptionHandler()
     *
     * @param   int     $severity   Log level
     * @param   string  $message    Error message
     * @param   string  $filepath   File path
     * @param   int     $line       Line number
     * @return  void
     */
    public function logException($severity, $message, $filepath, $line)
    {
        global $XY;

        if (isset($XY->logger)) {
            $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
            $XY->logger->error('Severity: '.$severity.' --> '.$message.' '.$filepath.' '.$line);
        }
    }

    /**
     * 404 Error Handler
     *
     * Formats a 404 error page and exits to index.php with an Exit Exception.
     *
     * @uses    Exceptions::formatError()
     * @used-by Xylophone::show404()
     *
     * @throws  Xylophone\core\ExitException
     *
     * @param   string  $page       Page URI
     * @param   bool    $log_error  Whether to log the error
     * @return  void
     */
    public function show404($page = '', $log_error = true)
    {
        global $XY;

        // Check for CLI
        if ($XY->isCli()) {
            // Set CLI heading and message
            $heading = 'Not Found';
            $message = 'The controller/method pair you requested was not found.';
        }
        else {
            // Set HTML heading and message
            $heading = '404 Page Not Found';
            $message = 'The page you requested was not found.';
        }

        // By default we log this, but allow a dev to skip it
        $log_error && isset($XY->logger) && $XY->logger->error($heading.': '.$page);

        // Format error and throw exception
        $msg = $this->formatError($heading, $message, 'error_404', array('page' => $page));
        throw new ExitException($msg, Xylophone::EXIT_UNKNOWN_FILE, 404, 'Not Found');
    }

    /**
     * General Error Page
     *
     * Takes an error message as input (either as a string or an array)
     * and displays it using the specified template.
     *
     * @uses    Exceptions::formatError()
     * @used-by Xylophone::showError()
     *
     * @throws  Xylophone\core\ExitException
     *
     * @param   string  $heading    Page heading
     * @param   mixed   $message    Error message or array of messages
     * @param   string  $template   Template name
     * @param   int     $response   Header response code (default: 500)
     * @return  void
     */
    public function showError($heading, $message, $template = 'error_general', $response = 500)
    {
        global $XY;

        // Determine exit status
        $response = abs($response);
        if ($response < 100) {
            // Set auto exit code between min and max and header to 500
            $exit_code = Xylophone::EXIT__AUTO_MIN + $response;
            $exit_code > Xylophone::EXIT__AUTO_MAX && $exit_code = Xylophone::EXIT_ERROR;
            $response = 500;
        }
        else {
            // Set generic error exit code
            $exit_code = Xylophone::EXIT_ERROR;
        }

        // Check Output for status code - it may not be loaded yet
        if (isset($XY->output->status_codes[$response])) {
            // Get header text
            $header = $XY->output->status_codes[$response];
        }
        else {
            // Use generic 500 error
            $response = 500;
            $header = 'Internal Server Error';
        }

        // Check for call from core or library class
        // First trace is us, second is usually Xylophone
        $args = array();
        $trace = $this->getTrace();
        if (isset($trace[0]['class'], $XY->config) && ($url = $XY->config['docs_url'])) {
            // Explode namespace and get class name
            $parts = explode('\\', $trace[0]['class']);
            $class = array_pop($parts);
            $dir = array_pop($parts);

            // Set link argument if core class or system library
            if ($dir == 'core' || ($dir == 'library' && end($parts) == 'Xylophone')) {
                $args['link'] = $url.'/'.$dir.'/'.$class;
            }
        }

        // Format error and throw exception
        $msg = $this->formatError($heading, $message, $template, $args);
        throw new ExitException($msg, $exit_code, $response, $header);
    }

    /**
     * Database Error Handler
     *
     * Formats a database error page and exits to index.php with an Exit Exception.
     *
     * @uses    Exceptions::formatError()
     *
     * @throws  Xylophone\core\ExitException
     *
     * @param   string  $error  Error string or language line index
     * @param   string  $swap   Error wildcard replacement
     * @return  void
     */
    public function showDbError($error, $swap = '')
    {
        global $XY;

        // Load db lines and get heading
        // If Database is loaded, it's safe to assume Lang is too
        $XY->lang->load('db');
        $heading = $XY->lang->line('db_error_heading');
        $message = array();

        // Convert error(s) to message array
        foreach ((array)$error as $errstr) {
            // Check for potential line index
            if (strpos($errstr, ' ') === false) {
                // Replace index with line if found
                $line = $XY->lang->line($errstr);
                $line && $errstr = $line;
            }

            // Search and replace if swap provided
            $swap && $errstr = str_replace('%s', $swap, $errstr);
            $message[] = $errstr;
        }

        // Find the most likely source of the error by unwinding the trace
        // until we're out of Database and Loader
        foreach ($this->getTrace() as $call) {
            if (isset($call['file'])) {
                // Convert slashes in path
                DIRECTORY_SEPARATOR == '/' || $call['file'] = str_replace('\\', '/', $call['file']);

                // Check file for Database or Loader
                if (strpos($call['file'], 'libraries/Database') === false &&
                strpos($call['file'], 'libraries/Loader') === false) {
                    // Found it - use a relative path for safety
                    $message[] = 'Filename: '.str_replace(array($XY->app_path, $XY->system_path), '', $call['file']);
                    $message[] = 'Line Number: '.$call['line'];
                    break;
                }
            }
        }

        // Add smart error link if URL is configured
        $args = array();
        $url = $XY->config['docs_url'];
        if ($url) {
            // Set link argument if core class or system library
            $args['link'] = $url.'/libraries/Database';
        }

        // Format error and throw exception
        $msg = $this->formatError($heading, $message, 'error_db', $args);
        throw new ExitException($msg, Xylophone::EXIT_DATABASE, 500, 'Internal Server Error');
    }

    /**
     * Native PHP error handler
     *
     * @used-by Xylophone::exceptionHandler()
     *
     * @param   int     $severity   Error level
     * @param   string  $message    Error message
     * @param   string  $filepath   File path
     * @param   int     $line       Line number
     * @return  string  Error page output
     */
    public function showPhpError($severity, $message, $filepath, $line)
    {
        global $XY;

        // Convert severity to text
        $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;

        // For safety reasons we don't show the full file path in non-CLI requests
        if (!$XY->isCli()) {
            DIRECTORY_SEPARATOR == '/' || $filepath = str_replace('\\', '/', $filepath);
            if (strpos($filepath, '/') !== false) {
                $parts = explode('/', $filepath);
                $filepath = $parts[count($parts)-2].'/'.end($parts);
            }
        }

        // Format error and echo
        $args = array('severity' => $severity, 'filepath' => $filepath, 'line' => $line);
        echo $this->formatError('PHP Error', $message, 'error_php', $args);
    }

    /**
     * Format an error message with a template or override
     *
     * @param   string  $heading    Error heading
     * @param   mixed   $message    Error message or array of messages
     * @param   string  $template   Template name
     * @param   array   $args       Additional template arguments
     * @return  string  Formatted error output
     */
    public function formatError($heading, $message, $template = 'error_general', array $args = array())
    {
        global $XY;

        // Check for CLI
        $cli = $XY->isCli();
        if ($cli) {
            // Format CLI message
            $message = "\t".(is_array($message) ? implode("\n\t", $message) : $message);
        }
        else {
            // Format HTML message
            $message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
        }

        // Check Router for an override
        $route = isset($XY->router, $XY->load) ? $XY->router->getErrorRoute($template) : false;
        if ($route !== false) {
            // Insert arguments
            array_unshift($route['args'], $message);
            array_unshift($route['args'], $heading);
            empty($args) || $route['args'] = array_merge($route['args'], $args);

            // Ensure "routed" is not set
            if (isset($XY->routed)) {
                unset($XY->routed);
            }
        }

        // Capture output in a buffer
        ob_start();

        // If we have a route, load the error Controller as "routed" and call the method
        if ($route === false || !$XY->load->controller($route, 'routed')) {
            // Otherwise, just use the generic error template
            $path = 'errors'.DIRECTORY_SEPARATOR.($cli ? 'cli' : 'html').DIRECTORY_SEPARATOR.$template.'.php';
            empty($args) || extract($args);

            // Search view paths until a template is found
            foreach ($XY->view_paths as $vdir) {
                if (@include($vdir.$path)) {
                    break;
                }
            }
        }

        // Return the output
        return ob_get_clean();
    }

    /**
     * Get debug backtrace
     *
     * This abstraction of the debug_backtrace call allows overriding for unit testing
     *
     * @codeCoverageIgnore
     *
     * @return  array   Debug backtrace
     */
    protected function getTrace()
    {
        // By default, just call debug_backtrace()
        // We automatically remove this method, the Exceptions method
        // that called it, and the Database or Xylophone method that
        // called Exceptions
        $trace = debug_backtrace();
        return array_slice($trace, 3);
    }
}

