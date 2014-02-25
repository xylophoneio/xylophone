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
 * Exit Exception
 *
 * This exception may be thrown anywhere the application needs to exit with
 * a message and an error code.
 *
 * @package     Xylophone
 * @subpackage  core
 */
class ExitException extends \Exception
{
    /** @var    int     Header reponse code */
    public $response = 500;

    /** @var    string  Header message */
    public $header = 'Internal Server Error';

    /**
     * Constructor
     *
     * Sets exception parameters and clears any buffered output.
     *
     * @return  void
     */
    public function __construct($message, $code = 0, $response = null, $header = null)
    {
        global $XY;

        // Call parent constructor first
        parent::__construct($message, $code);

        // Set header values if provided
        $response === null || $this->response = $response;
        $header === null || $this->header = $header;

        // Clear output buffers
        $level = isset($XY->init_ob_level) ? $XY->init_ob_level : 0;
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }

    /**
     * Get header response code
     *
     * @return  int     Header response code
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get header message
     *
     * @return  string  Header message
     */
    public function getHeader()
    {
        return $this->header;
    }
}

