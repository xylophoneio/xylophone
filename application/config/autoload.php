<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Xylophone
 *
 * An open source HMVC application development framework for PHP 5.3 or newer
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
 * through the world wide web, please send an email to
 * licensing@xylophone.io so we can send you a copy immediately.
 *
 * @package     Xylophone
 * @author      Xylophone Dev Team
 * @copyright   Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://xylophone.io
 * @since       Version 1.0
 * @filesource
 */

/*-------------------------------------------------------------------
| AUTO-LOADER
| -------------------------------------------------------------------
| This file specifies which systems should be loaded by default.
|
| In order to keep the framework as light-weight as possible only the
| absolute minimal resources are loaded by default. For example,
| the database is not connected to automatically since no assumption
| is made regarding whether you intend to use it.  This file lets
| you globally define which systems you would like loaded with every
| request.
|
| -------------------------------------------------------------------
| Instructions
| -------------------------------------------------------------------
|
| These are the things you can load automatically:
|
| 1. Namespace paths
| 2. View paths
| 3. Language files
| 4. Custom config files
| 5. Libraries
| 6. Drivers
| 7. Models
|
*/

/*-------------------------------------------------------------------
|  Auto-load Namespace Paths
| -------------------------------------------------------------------
| These are namespaces to search when loading core, library, controller,
| and model classes, each with a path to the namespace root.
|
| Prototype:
|   $autoload['namespaces'] = array(
|       'SomeModule' => 'third_party/some_module',
|       'SharedLibs' => '/usr/local/shared/xylibs'
|   );
|
*/
$autoload['namespaces'] = array();


/*-------------------------------------------------------------------
|  Auto-load View Paths
| -------------------------------------------------------------------
| These are additional paths to search for view files that are not found in
| the application view folder.
|
| Prototype:
|   $autoload['view_paths'] = array('third_party/some_module/views', '/usr/local/shared/xylibs/views');
|
*/
$autoload['view_paths'] = array();


/*-------------------------------------------------------------------
|  Auto-load Config Files
| -------------------------------------------------------------------
| Prototype:
|   $autoload['config'] = array('config1', 'config2');
|
| NOTE: This item is intended for use ONLY if you have created custom
| config files.  Otherwise, leave it blank.
|
*/
$autoload['config'] = array();


/*-------------------------------------------------------------------
|  Auto-load Language files
| -------------------------------------------------------------------
| Prototype:
|	$autoload['language'] = array('lang1', 'lang2');
|
| NOTE: Do not include the "_lang" part of your file.  For example
| "xylophone_lang.php" would be referenced as array('xylophone');
|
*/
$autoload['language'] = array();


/*-------------------------------------------------------------------
|  Auto-load Libraries
| -------------------------------------------------------------------
| These are the classes located in the system/libraries folder
| or in your application/libraries folder.
|
| Prototype:
|   $autoload['libraries'] = array('database', 'email', 'xmlrpc');
*/
$autoload['libraries'] = array();


/*-------------------------------------------------------------------
|  Auto-load Drivers
| -------------------------------------------------------------------
| These classes are located in the system/libraries folder or in your
| application/libraries folder within their own subdirectory. They
| offer multiple interchangeable driver options.
|
| Prototype:
|   $autoload['drivers'] = array('session', 'cache');
*/
$autoload['drivers'] = array();


/*-------------------------------------------------------------------
|  Auto-load Models
| -------------------------------------------------------------------
| Prototype:
|   $autoload['model'] = array('first_model', 'second_model');
|
| You can also supply an alternative model name to be assigned
| in the controller:
|
|   $autoload['model'] = array('first_model' => 'first');
*/
$autoload['model'] = array();


