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
    /** @var    int     Nesting level of the output buffering mechanism */
    public $ob_level;

    /** @var    array   List if available error levels */
    public $levels = array(
        E_ERROR             =>    'Error',
        E_WARNING           =>    'Warning',
        E_PARSE             =>    'Parsing Error',
        E_NOTICE            =>    'Notice',
        E_CORE_ERROR        =>    'Core Error',
        E_CORE_WARNING      =>    'Core Warning',
        E_COMPILE_ERROR     =>    'Compile Error',
        E_COMPILE_WARNING   =>    'Compile Warning',
        E_USER_ERROR        =>    'User Error',
        E_USER_WARNING      =>    'User Warning',
        E_USER_NOTICE       =>    'User Notice',
        E_STRICT            =>    'Runtime Notice'
    );

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        $this->ob_level = ob_get_level();
        // Note: Do not log messages from this constructor.
    }

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
        $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
        $XY->logger->error('Severity: '.$severity.' --> '.$message.' '.$filepath.' '.$line);
    }

    /**
     * 404 Error Handler
     *
     * @uses    Exceptions::showError()
     * @used-by Xylophone::show404()
     *
     * @param   string  $page       Page URI
     * @param   bool    $log_error  Whether to log the error
     * @return  void
     */
    public function show404($page = '', $log_error = true)
    {
        global $XY;

        if ($XY->isCli()) {
            $heading = 'Not Found';
            $message = 'The controller/method pair you requested was not found.';
        }
        else {
            $heading = '404 Page Not Found';
            $message = 'The page you requested was not found.';
        }

        // By default we log this, but allow a dev to skip it
        $log_error && $XY->logger->error($heading.': '.$page);

        // Call showError for the 404 - it will exit
        $this->showError($heading, $message, 'error_404', 404);
    }

    /**
     * General Error Page
     *
     * Takes an error message as input (either as a string or an array)
     * and displays it using the specified template.
     *
     * @used-by Exceptions::show404()
     * @used-by Xylophone::showError()
     *
     * @param   string  $heading        Page heading
     * @param   mixed   $message        Error message or array of messages
     * @param   string  $template       Template name
     * @param   int     $status_code    Status code (default: 500)
     * @return  string  Error page output
     */
    public function showError($heading, $message, $template = 'error_general', $status_code = 500)
    {
        global $XY;

        // Determine exit status
        $status_code = abs($status_code);
        if ($status_code < 100) {
            $exit_status = $status_code + EXIT__AUTO_MIN;
            if ($exit_status > EXIT__AUTO_MAX) {
                $exit_status = EXIT_ERROR;
            }
            $status_code = 500;
        }
        elseif ($status_code == 404) {
            $exit_status = EXIT_UNKNOWN_FILE;
        }
        else {
            $exit_status = EXIT_ERROR;
        }

        // Check for CLI
        if ($XY->isCli()) {
            // Format message and set template for CLI
            $message = "\t".(is_array($message) ? implode("\n\t", $message) : $message);
            $template = 'cli'.DIRECTORY_SEPARATOR.$template;
        }
        else {
            // Check for Output - we could get called before it's loaded
            if (isset($XY->output)) {
                // Use Output method
                $XY->output->setStatusHeader($status_code);
            }
            else {
                // Call header() directly as a generic 500
                header('HTTP/1.1 500 Internal Server Error', true, 500);
            }
            $message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
            $template = 'html'.DIRECTORY_SEPARATOR.$template;
        }

        (ob_get_level() <= $this->ob_level + 1) || ob_end_flush();

        // Check Router for an error (or 404) override
        $route = isset($XY->router) ? $XY->router->getErrorRoute($status_code == 404) : false;
        if ($route !== false) {
            // Insert arguments
            array_unshift($route['args'], $message);
            array_unshift($route['args'], $heading);

            // Ensure "routed" is not set
            if (isset($XY->routed)) {
                unset($XY->routed);
            }

            // Load the error Controller as "routed" and call the method
            if ($XY->load->controller($route, 'routed')) {
                // Display the output and exit
                $XY->output->_display();
                exit;
            }
        }

        // If the override didn't exit above, just display the generic error template
        ob_start();
        include(current($XY->view_paths).'errors'.DIRECTORY_SEPARATOR.$template.'.php');
        echo ob_get_clean();
        exit($exit_status);
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

        $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;

        if ($XY->isCli()) {
            $template = 'cli'.DIRECTORY_SEPARATOR.'error_php';
        }
        else {
            // For safety reasons we don't show the full file path in non-CLI requests
            $filepath = str_replace('\\', '/', $filepath);
            if (strpos($filepath, '/') !== false) {
                $x = explode('/', $filepath);
                $filepath = $x[count($x)-2].'/'.end($x);
            }

            $template = 'html'.DIRECTORY_SEPARATOR.'error_php';
        }

        (ob_get_level() <= $this->ob_level + 1) || ob_end_flush();
        ob_start();
        include(current($XY->view_paths).'errors'.DIRECTORY_SEPARATOR.$template.'.php');
        echo ob_get_clean();
    }
}

