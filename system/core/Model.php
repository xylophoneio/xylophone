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
 * Application Model Base Class
 *
 * This is the intended base class for all application models.
 * It provides a local reference to the global $XY framework object.
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/general/models.html
 */
class Model {
    /** @var    object  The Xylophone framework singleton */
    public $XY;

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;
        $this->XY = $XY;
    }

    /**
     * Get Magic Method
     *
     * Gives access to members of the global $XY object as if they were members
     * of this controller. Throwback to old non-HMVC CI days.
     *
     * @param   string  $key    Member name
     * @return  mixed   Global $XY member
     */
    public function __get($key)
    {
        if (isset($this->XY->$key)) {
            return $this->XY->$key;
        }
    }
}

