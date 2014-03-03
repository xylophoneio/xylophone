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

// Errors on full!
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

// Set TESTPATH and set BASEPATH constant like index.php would
define('TESTPATH', __DIR__.'/');
define('BASEPATH', realpath(__DIR__.'/../').'/');

// Define default exit code
define('EXIT_XY', 88);

// Get vfsStream from includes or vendor dir
// Right now, includes setup assumes taking the vfsStream directory out of a Composer
// install and putting it in an includes directory as-is.
// Using visitor components requires additional includes.
$vfs_path = 'vfsStream/src/main/php/org/bovigo/vfs/';
if (@include_once $vfs_path.'vfsStream.php') {
    // Include all base source files so we don't need an autoloader
    include_once $vfs_path.'vfsStreamContent.php';
    include_once $vfs_path.'vfsStreamAbstractContent.php';
    include_once $vfs_path.'vfsStreamContainer.php';
    include_once $vfs_path.'vfsStreamContainerIterator.php';
    include_once $vfs_path.'vfsStreamDirectory.php';
    include_once $vfs_path.'vfsStreamException.php';
    include_once $vfs_path.'vfsStreamFile.php';
    include_once $vfs_path.'vfsStreamWrapper.php';
    include_once $vfs_path.'Quota.php';
}
else {
    // Use the Composer autoloader
	@include_once BASEPATH.'vendor/autoload.php';
}

// Alias the common top-level vfsStream classes for convenience
class_alias('org\bovigo\vfs\vfsStream', 'vfsStream');
class_alias('org\bovigo\vfs\vfsStreamFile', 'vfsStreamFile');
class_alias('org\bovigo\vfs\vfsStreamDirectory', 'vfsStreamDirectory');

// Set localhost "remote" IP
isset($_SERVER['REMOTE_ADDR']) OR $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Load our custom test case
include_once TESTPATH.'XyTestCase.php';

// Register our test autoloader
spl_autoload_register(function ($class) {
    // Break out namespace and class
    $parts = explode('\\', trim($class, '\\'));
    switch ($parts[0]) {
        case 'Xylophone':
            $path = 'system/';
            break;
        case 'Mocks':
            $path = 'tests/Mocks/';
            break;
        default:
            return;
    }

    // Remove top-level namespace
    array_shift($parts);

    // Include reassembled namespace as path to source file
    include_once BASEPATH.$path.implode('/', $parts).'.php';
});

