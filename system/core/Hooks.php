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
 * bundled with this package in the files license.txt / license.rst.  It is
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
 * Hooks Class
 *
 * Provides a mechanism to extend the base system without hacking.
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/encryption.html
 */
class Hooks
{
    /** @var    bool    Determines whether hooks are enabled */
    public $enabled = false;

    /** @var    array   List of all hooks set in config/hooks.php */
    public $hooks = array();

    /** @var    bool    Determines whether hook is in progress, used to prevent infinte loops */
    protected $in_progress = false;

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // If hooks are not enabled in the config file there is nothing else to do
        if ($XY->config['enable_hooks']) {
            // Load hooks config and check for hooks
            $hooks = $XY->config->get('hooks.php', 'hook');
            if (is_array($hooks)) {
                $this->hooks =& $hooks;
                $this->enabled = true;
            }
        }

        $XY->logger->debug('Hooks Class Initialized');
    }

    /**
     * Call Hook
     *
     * @used-by Xylophone::play()
     *
     * @param   string  $which Hook name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function callHook($which = '')
    {
        global $XY;

        // Nothing to do if disabled, in progress, or no hook
        if (!$this->enabled || $this->in_progress || !isset($this->hooks[$which])) {
            return false;
        }

        // Iterate all calls for this hook
        $this->in_progress = true;
        foreach ((array)($this->hooks[$which]) as $hook) {
            $XY->load->controller($hook);
        }
        $this->in_progress = false;

        return true;
    }
}

