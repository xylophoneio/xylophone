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

/**
 * Mock Xylophone Class
 *
 * Facilitates Unit Testing the Xylophone Core Class.
 */
class Xylophone extends \Xylophone\core\Xylophone
{
    /** @var    bool    Skip initialize() flag */
    public static $skip_init = false;

    /** @var    bool    Skip setHandlers() flag */
    public static $skip_handlers = false;

    /** @var    array   Relative path resolution bases (made public) */
    public $resolve_bases = array();

    /** @var    array   PHP version comparison results (made public) */
    public $is_php = array();

    /**
     * Initialize framework
     *
     * This overload allows us to conditionally run the real initialize method.
     *
     * @param   array   $init   Initialization parameters
     * @return  void
     */
    public function initialize($init)
    {
        // Call the real method unless we're skipping
        self::$skip_init || parent::initialize($init);
    }

    /**
     * Register autoloader, error, and shutdown handlers
     *
     * This overload allows us to conditionally run the real registerHandlers method.
     *
     * @return  void
     */
    public function registerHandlers()
    {
        // Call the real method unless we're skipping
        self::$skip_handlers || parent::registerHandlers();
    }

    /**
     * Get real path
     *
     * This abstraction of the realpath call allows overriding for unit testing
     *
     * @param   string  $path   Path to resolve
     * @return  string  Real path
     */
    protected function realpath($path)
    {
        // Just trim trailing slash since realpath() fails on VFS urls
        return rtrim($path, '\/');
    }
}

