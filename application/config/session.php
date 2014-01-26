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
defined('BASEPATH') OR exit('No direct script access allowed');

/*---------------------------------------------------------------
| Session Driver
|----------------------------------------------------------------
| Driver to load for Session library. Default choices are 'cookie', which
| stores data in a browser cookie with optional database augmentation, and
| 'native', which uses native PHP sessions.
*/
$driver = 'cookie';

/*---------------------------------------------------------------
| Session Cookie Name
|----------------------------------------------------------------
| The name you want for the cookie, must contain only [0-9a-z_-] characters.
*/
$config['cookie_name'] = 'ci_session';

/*---------------------------------------------------------------
| Session Expiration
|----------------------------------------------------------------
| The number of SECONDS you want the session to last.
| By default sessions last 7200 seconds (two hours).
| Set to zero for no expiration.
*/
$config['expiration'] = 7200;

/*---------------------------------------------------------------
| Expire On Close
|----------------------------------------------------------------
| Whether to cause the session to expire automatically when the browser window
| is closed.
*/
$config['expire_on_close'] = false;

/*---------------------------------------------------------------
| Match IP Address
|----------------------------------------------------------------
| Whether to match the user's IP address when reading the session data
*/
$config['match_ip'] = false;

/*---------------------------------------------------------------
| Match User Agent
|----------------------------------------------------------------
| Whether to match the User Agent when reading the session data
*/
$config['match_useragent'] = true;

/*---------------------------------------------------------------
| Time To Update
|----------------------------------------------------------------
| How many seconds between Xylophone refreshing session information
*/
$config['time_to_update'] = 300;

/*---------------------------------------------------------------
| Cookie Encryption
|----------------------------------------------------------------
| Whether to encrypt the cookie when using the 'cookie' driver.
*/
$config['encrypt_cookie'] = false;

/*---------------------------------------------------------------
| Database Settings
|----------------------------------------------------------------
| When using the 'cookie' driver, set 'use_database' to TRUE to store
| session data in a database table instead of directly in the session
| cookie.
|
| Set 'table_name' to the name of the session database table
*/
$config['use_database'] = false;
$config['table_name'] = 'ci_sessions';

