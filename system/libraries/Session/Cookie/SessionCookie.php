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
namespace Xylophone\libraries\Session\Cookie;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cookie-based session management driver
 *
 * This is the classic Session functionality, as written by EllisLab, abstracted out to a driver.
 *
 * @package     Xylophone
 * @subpackage  Libraries\Session\Cookie
 * @link        http://xylophone.io/user_guide/libraries/sessions.html
 */
class SessionCookie extends \Xylophone\libraries\Session\Session
{
    /** @var    int     Current time */
    public $now;

    /** @var    bool    Data needs DB update flag */
    protected $data_dirty = false;

    /** @var    array   Default userdata keys */
    protected $defaults = array(
        'session_id' => null,
        'ip_address' => null,
        'user_agent' => null,
        'last_activity' => null
    );

    /**
     * Initialize session
     *
     * @return  void
     */
    public function initialize()
    {
        global $XY;

        if (empty($this->encryption_key)) {
            $XY->showError('In order to use the Session Cookie driver you must set an encryption key in your config file.');
        }

        // Do we need encryption? If so, load the encryption class
        $this->encrypt_cookie && $XY->load->library('encrypt');

        // Check for database
        if ($this->use_database && $this->table_name) {
            // Load database driver
            $XY->load->driver('database');

            // Register shutdown function
            register_shutdown_function(array($this, 'updateDb'));
        }

        // Set the "now" time. Can either be GMT or server time, based on the config prefs.
        // We use this to set the "last activity" time
        $this->now = $this->getTime();

        // Set the session length. If the session expiration is
        // set to zero we'll set the expiration two years from now.
        $this->expiration || $this->expiration = (60*60*24*365*2);

        // Set the cookie name
        $this->cookie_name = $this->cookie_prefix.$this->cookie_name;

        // Run the Session routine. If a session doesn't exist we'll
        // create a new one. If it does, we'll update it.
        if ($this->sessRead()) {
            $this->sessUpdate();
        }
        else {
            $this->sessCreate();
        }

        // Delete expired sessions if necessary
        $this->sessGc();
    }

    /**
     * Write the session data
     *
     * @return  void
     */
    public function save()
    {
        // Mark custom data as dirty so we know to update the DB
        $this->use_database && $this->data_dirty = true;

        // Write the cookie
        $this->setCookie();
    }

    /**
     * Destroy the current session
     *
     * @return  void
     */
    public function destroy()
    {
        global $XY;

        // Kill the session DB row
        if ($this->use_database && isset($this->userdata['session_id'])) {
            $XY->db->delete($this->table_name, array('session_id' => $this->userdata['session_id']));
            $this->data_dirty = false;
        }

        // Kill the cookie
        $this->setCookie($this->cookie_name, '', ($this->now - 31500000), $this->cookie_path,
            $this->cookie_domain, 0);

        // Kill session data
        $this->userdata = array();
    }

    /**
     * Regenerate the current session
     *
     * Regenerate the session id
     *
     * @param   bool    $destroy    Destroy session data flag (default: false)
     * @return  void
     */
    public function regenerate($destroy = false)
    {
        // Check destroy flag
        if ($destroy) {
            // Destroy old session and create new one
            $this->destroy();
            $this->sessCreate();
        }
        else {
            // Just force an update to recreate the id
            $this->sessUpdate(true);
        }
    }

    /**
     * Fetch the current session data if it exists
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    protected function sessRead()
    {
        global $XY;

        // Fetch the cookie
        $session = $XY->input->cookie($this->cookie_name);

        // No cookie? Goodbye cruel world!...
        if ($session === NULL) {
            $XY->logger->debug('A session cookie was not found.');
            return false;
        }

        $len = strlen($session) - 40;
        if ($len < 0) {
            $XY->logger->debug('The session cookie was not signed.');
            return false;
        }

        // Check cookie authentication
        $hmac = substr($session, $len);
        $session = substr($session, 0, $len);

        if ($hmac !== hash_hmac('sha1', $session, $this->encryption_key)) {
            $XY->logger->error('The session cookie data did not match what was expected.');
            $this->destroy();
            return false;
        }

        // Check for encryption
        if ($this->encrypt_cookie) {
            // Decrypt the cookie data
            $session = $XY->encrypt->decode($session);
        }

        // Unserialize the session array
        $session = @unserialize($session);

        // Is the session data we unserialized an array with the correct format?
        if (!is_array($session) ||
        !isset($session['session_id'], $session['ip_address'], $session['user_agent'], $session['last_activity'])) {
            $XY->logger->debug('Session: Wrong cookie data format');
            $this->destroy();
            return false;
        }

        // Is the session current?
        if (($session['last_activity'] + $this->expiration) < $this->now ||
        $session['last_activity'] > $this->now) {
            $XY->logger->debug('Session: Expired');
            $this->destroy();
            return false;
        }

        // Does the IP match?
        if ($this->match_ip && $session['ip_address'] !== $XY->input->ipAddress()) {
            $XY->logger->debug('Session: IP address mismatch');
            $this->destroy();
            return false;
        }

        // Does the User Agent Match?
        if ($this->match_useragent && trim($session['user_agent']) !== trim(substr($XY->input->userAgent(), 0, 120))) {
            $XY->logger->debug('Session: User Agent string mismatch');
            $this->destroy();
            return false;
        }

        // Is there a corresponding session in the DB?
        if ($this->use_database) {
            $XY->db->where('session_id', $session['session_id']);
            $this->match_ip && $XY->db->where('ip_address', $session['ip_address']);
            $this->match_useragent && $XY->db->where('user_agent', $session['user_agent']);

            // Is caching in effect? Turn it off
            $db_cache = $XY->db->cache_on;
            $XY->db->cacheOff();

            $query = $XY->db->limit(1)->get($this->table_name);

            // Turn caching back on if it was in effect
            $db_cache && $XY->db->cacheOn();

            // No result? Kill it!
            if (empty($query) || $query->numRows() === 0) {
                $XY->logger->debug('Session: No match found in our database');
                $this->destroy();
                return false;
            }

            // Is there custom data? If so, add it to the main session array
            $row = $query->row();
            if (!empty($row->user_data)) {
                $custom_data = unserialize(trim($row->user_data));
                is_array($custom_data) && $session = $session + $custom_data;
            }
        }

        // Session is valid!
        $this->userdata =& $session;
        return true;
    }

    /**
     * Create a new session
     *
     * @return  void
     */
    protected function sessCreate()
    {
        global $XY;

        // Initialize userdata
        $this->userdata = array(
            'session_id' => $this->makeSessId(),
            'ip_address' => $XY->input->ipAddress(),
            'user_agent' => trim(substr($XY->input->userAgent(), 0, 120)),
            'last_activity' => $this->now,
        );

        $XY->logger->debug('Session: Creating new session ('.$this->userdata['session_id'].')');

        // Add empty user_data field and save the data to the DB if configured
        $this->use_database && $XY->db->set('user_data', '')->insert($this->table_name, $this->userdata);

        // Write the cookie
        $this->setCookie();
    }

    /**
     * Update an existing session
     *
     * @param   bool    $force  Force update flag (default: false)
     * @return  void
     */
    protected function sessUpdate($force = false)
    {
        global $XY;

        // We only update the session every five minutes by default (unless forced)
        if (!$force && ($this->userdata['last_activity'] + $this->time_to_update) >= $this->now) {
            return;
        }

        // Update last activity to now
        $this->userdata['last_activity'] = $this->now;

        // Save the old session id so we know which DB record to update
        $old_sessid = $this->userdata['session_id'];

        // Changing the session ID during an AJAX call causes problems
        if (!$XY->input->isAjaxRequest()) {
            // Get new id
            $this->userdata['session_id'] = $this->makeSessId();
            $XY->logger->debug('Session: Regenerate ID');
        }

        // Check for database
        if ($this->use_database) {
            $XY->db->where('session_id', $old_sessid);
            $this->match_ip && $XY->db->where('ip_address', $XY->input->ipAddress());
            $this->match_useragent && $XY->db->where('user_agent', trim(substr($XY->input->userAgent(), 0, 120)));

            // Update the session ID and last_activity field in the DB
            $XY->db->update($this->table_name, array(
                'last_activity' => $this->now,
                'session_id' => $this->userdata['session_id']
            ));
        }

        // Write the cookie
        $this->setCookie();
    }

    /**
     * Update database with current data
     *
     * This gets called from the shutdown function and also
     * registered with PHP to run at the end of the request
     * so it's guaranteed to update even when a fatal error
     * occurs. The first call makes the update and clears the
     * dirty flag so it won't happen twice.
     *
     * @return  void
     */
    public function updateDb()
    {
        global $XY;

        // Check for database and dirty flag and unsaved
        if ($this->use_database && $this->data_dirty) {
            // Set up activity and data fields to be set
            // If we don't find custom data, user_data will remain an empty string
            $set = array(
                'last_activity' => $this->userdata['last_activity'],
                'user_data' => ''
            );

            // Get the custom userdata, leaving out the defaults (which get
            // stored in the cookie) and serialize it for storage
            $userdata = array_diff_key($this->userdata, $this->defaults);
            empty($userdata) || $set['user_data'] = serialize($userdata);

            // Reset query builder values.
            $XY->db->resetQuery();

            // Run the update query
            // Any time we change the session id, it gets updated immediately,
            // so our where clause below is always safe
            $XY->db->where('session_id', $this->userdata['session_id']);
            $this->match_ip && $XY->db->where('ip_address', $XY->input->ipAddress());
            $this->match_useragent && $XY->db->where('user_agent', trim(substr($XY->input->userAgent(), 0, 120)));
            $XY->db->update($this->table_name, $set);

            // Clear dirty flag to prevent double updates
            $this->data_dirty = false;

            $XY->logger->debug('Session Data Saved To DB');
        }
    }

    /**
     * Generate a new session id
     *
     * @return  string  Hashed session id
     */
    protected function makeSessId()
    {
        global $XY;

        $new_sessid = '';
        do {
            $new_sessid .= mt_rand();
        } while (strlen($new_sessid) < 32);

        // To make the session ID even more secure we'll combine it with the user's IP
        $new_sessid .= $XY->input->ipAddress();

        // Turn it into a hash and return
        return md5(uniqid($new_sessid, true));
    }

    /**
     * Get the "now" time
     *
     * @return  int     Time
     */
    protected function getTime()
    {
        if ($this->time_reference === 'local' || $this->time_reference === date_default_timezone_get()) {
            return time();
        }

        $datetime = new DateTime('now', new DateTimeZone($this->time_reference));
        sscanf($datetime->format('j-n-Y G:i:s'), '%d-%d-%d %d:%d:%d', $day, $month, $year, $hour, $minute, $second);

        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Write the session cookie
     *
     * @return  void
     */
    protected function setCookie()
    {
        global $XY;

        // Get userdata (only defaults if database)
        $cookie_data = $this->use_database ? array_intersect_key($this->userdata, $this->defaults) : $this->userdata;

        // Serialize and encrypt the userdata for the cookie
        $cookie_data = serialize($cookie_data);
        $this->encrypt_cookie && $cookie_data = $XY->encrypt->encode($cookie_data);

        // Require message authentication and set expiration
        $cookie_data .= hash_hmac('sha1', $cookie_data, $this->encryption_key);
        $expire = $this->expire_on_close ? 0 : $this->expiration + time();

        // Set the cookie
        $this->doSetCookie($this->cookie_name, $cookie_data, $expire, $this->cookie_path, $this->cookie_domain,
            $this->cookie_secure, $this->cookie_httponly);
    }

    /**
     * Garbage collection
     *
     * This deletes expired session rows from database
     * if the probability percentage is met
     *
     * @return  void
     */
    protected function sessGc()
    {
        global $XY;

        if (!$this->use_database) {
            return;
        }

        $probability = ini_get('session.gc_probability');
        $divisor = ini_get('session.gc_divisor');

        if (mt_rand(1, $divisor) <= $probability) {
            $expire = $this->now - $this->expiration;
            $XY->db->delete($this->table_name, 'last_activity < '.$expire);
            $XY->logger->debug('Session garbage collection performed.');
        }
    }
}

