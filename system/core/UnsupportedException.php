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
 * Resource Unsupported Exception
 *
 * This exception may be thrown from the constructor of any class which deems
 * itself unsupported by the environment. The class may specify an alternate
 * class to try loading in place of itself. The exception is mainly intended
 * for drivers such as Cache to swap out a different driver when the configured
 * one won't work.
 *
 * Loader will catch this exception and check for an alternate class, which it
 * will try to load instead. There is a hard limit of 5 attempts at loading a
 * class before Loader will quit trying, regardless of further exceptions, to
 * prevent infinite looping.
 *
 * @package     Xylophone
 * @subpackage  core
 */
class UnsupportedException extends \Exception
{
    /**  @var   string  Alternate class to load */
    public $alternate = '';

    /**
     * Constructor
     *
     * @param   string  $alternate  Alternate class name
     * @return  void
     */
    public function __construct($alternate = '')
    {
        // Call parent ctor and set alternate
        parent::__construct();
        $this->alternate = $alternate;
    }
}

