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
namespace Xylophone\libraries\Session\Native;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Native PHP session management driver
 *
 * This is the driver that uses the native PHP $_SESSION array through the Session driver library.
 *
 * @package     Xylophone
 * @subpackage	Libraries\Session\Native
 * @link        http://xylophone.io/user_guide/libraries/sessions.html
 */
class SessionNative extends \Xylophone\libraries\Session\Session
{
    /**
     * Initialize session
     *
     * @return  void
     */
    protected function initialize()
    {
        global $XY;

        // Set session name, if specified
        if ($this->cookie_name) {
            // Differentiate name from cookie driver with '_id' suffix
            $name = $this->cookie_prefix.$this->cookie_name.'_id';
            session_name($name);
        }

        // Set path, domain, and expiration
        $path = $this->cookie_path ? $this->cookie_path : '/';
        $domain = $this->cookie_domain ? $this->cookie_domain : '';
        $expire = 7200;
        if ($this->expiration !== false) {
            // Default to 2 years if expiration is "0"
            $expire = ($this->expiration == 0) ? (60*60*24*365*2) : $this->expiration;
        }
        $secure = (bool)$this->cookie_secure;
        $http = (bool)$this->cookie_httponly;
        session_set_cookie_params($this->expire_on_close ? 0 : $expire, $path, $domain, $secure, $http);

        // Start session
        session_start();

        // Check session expiration, ip, and agent
        $now = time();
        $destroy = false;
        if (isset($_SESSION['last_activity']) && (($_SESSION['last_activity'] + $expire) < $now ||
        $_SESSION['last_activity'] > $now)) {
            // Expired - destroy
            $XY->logger->debug('Session: Expired');
            $destroy = true;
        }
        elseif ($this->match_ip && isset($_SESSION['ip_address']) &&
        $_SESSION['ip_address'] !== $XY->input->ipAddress()) {
            // IP doesn't match - destroy
            $XY->logger->debug('Session: IP address mismatch');
            $destroy = true;
        }
        elseif ($this->match_useragent && isset($_SESSION['user_agent']) &&
        $_SESSION['user_agent'] !== trim(substr($XY->input->userAgent(), 0, 50))) {
            // Agent doesn't match - destroy
            $XY->logger->debug('Session: User Agent string mismatch');
            $destroy = true;
        }

        // Destroy expired or invalid session
        if ($destroy) {
            // Clear old session and start new
            $this->destroy();
            session_start();
        }

        // Check for update time
        if ($this->time_to_update && isset($_SESSION['last_activity'])
        && ($_SESSION['last_activity'] + $this->time_to_update) < $now) {
            // Changing the session ID amidst a series of AJAX calls causes problems
            if (!$XY->input->isAjaxRequest()) {
                // Regenerate ID, but don't destroy session
                $XY->logger->debug('Session: Regenerate ID');
                $this->regenerate(false);
            }
        }

        // Set activity time
        $_SESSION['last_activity'] = $now;

        // Set matching values as required
        if ($this->match_ip === true && !isset($_SESSION['ip_address'])) {
            // Store user IP address
            $_SESSION['ip_address'] = $XY->input->ipAddress();
        }

        if ($this->match_useragent === true && !isset($_SESSION['user_agent'])) {
            // Store user agent string
            $_SESSION['user_agent'] = trim(substr($XY->input->userAgent(), 0, 50));
        }

        // Make session ID available
        $_SESSION['session_id'] = session_id();

        // Set _SESSION as userdata
        $this->userdata =& $_SESSION;
    }

    /**
     * Save the session data
     *
     * @return  void
     */
    public function save()
    {
        // Nothing to do - changes to $_SESSION are automatically saved
    }

    /**
     * Destroy the current session
     *
     * @return  void
     */
    public function destroy()
    {
        // Cleanup session
        $_SESSION = array();
        $name = session_name();
        if (isset($_COOKIE[$name])) {
            // Clear session cookie
            $params = session_get_cookie_params();
            $this->doSetCookie($name, '', time() - 42000, $params['path'], $params['domain'], $params['secure'],
                $params['httponly']);
            unset($_COOKIE[$name]);
        }
        session_destroy();
    }

    /**
     * Regenerate the current session
     *
     * Regenerate the session id
     *
     * @param   bool    $destroy    Destroy session data flag (default: FALSE)
     * @return  void
     */
    public function regenerate($destroy = false)
    {
        // Just regenerate id, passing destroy flag
        session_regenerate_id($destroy);
        $_SESSION['session_id'] = session_id();
    }
}

