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
| Cache Driver
|----------------------------------------------------------------
| Driver to load for Cache library. Default choices are:
|   'apc'       Alternative PHP Cache
|   'file'      File-based caching
|   'memcached' Memcached caching
|   'redis'     Redis caching
|   'wincache'  Windows caching
*/
$driver = 'apc';

/*---------------------------------------------------------------
| Backup Cache Driver
|----------------------------------------------------------------
| In the case when the primary Cache driver is not supported, use
| this driver instead.
*/
$backup = 'file';

/*---------------------------------------------------------------
| Cache Directory Path
|----------------------------------------------------------------
| Leave this BLANK unless you would like to set something other than the default
| application/cache/ folder.  Use a full server path with trailing slash.
*/
$config['cache_path'] = '';

/*---------------------------------------------------------------
| Item Key Prefix
|----------------------------------------------------------------
| You can set this to a prefix for each item ID in the cache.
*/
$config['key_prefix'] = '';

