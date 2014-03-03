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
namespace Mocks\core;

class Input extends \Xylophone\core\Input
{
    /** @var    array   List of all HTTP request headers (made public) */
    public $headers;

    /** @var    string  Input stream source - to override during testing (made public) */
    public $input_source;

    /** @var    array   Input stream data - parsed from php://input at runtime (made public) */
    public $input_stream;

    /**
     * Fetch from array (made public)
     *
     * @param   array   $array      Reference to $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param   string  $index      Index for item to be fetched from $array
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function fetchFromArray(&$array, $index = '', $xss_clean = false)
    {
        return parent::fetchFromArray($array, $index, $xss_clean);
    }

    /**
     * Sanitize Globals (made public)
     *
     * @return  void
     */
    public function sanitizeGlobals()
    {
        return parent::sanitizeGlobals();
    }

    /**
     * Clean Input Data (made public)
     *
     * @param	mixed   $str    Input string or array of strings
     * @return  midex   Cleaned string or array
     */
    public function cleanInputData($str)
    {
        return parent::cleanInputData($str);
    }

    /**
     * Clean Input Keys (made public)
     *
     * @param   string  $str    Input string
     * @param   string  $fatal  Whether an invalid key terminates the app
     * @return  mixed   Cleaned keys on succes, otherwise FALSE
     */
    public function cleanInputKeys($str, $fatal = true)
    {
        return parent::cleanInputKeys($str, $fatal);
    }
}

