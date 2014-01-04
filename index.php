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

/*---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 * You can load different configurations depending on your current environment.
 * Setting the environment also influences things like logging and
 * error reporting.
 *
 * This can be set to anything, but the default values are "development",
 * "testing", and "production".
 *
 * NOTE: If you use a custom value, add it to the error reporting code below.
 */
$environment = isset($_SERVER['XY_ENV']) ? $_SERVER['XY_ENV'] : 'development');

/*---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch ($environment) {
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        break;
    case 'testing':
    case 'production':
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', 0);
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'The application environment is not set correctly.';
        exit(EXIT_ERROR);
}

/*---------------------------------------------------------------
 * APPLICATION NAMESPACE
 *---------------------------------------------------------------
 * If you want to use PHP namespaces for all Models, Controllers, and Libraries
 * in your application path, set the top-level namespace here. Each class must
 * then use this namespace and its containing folder as a sub-namespace.
 *
 * For example:
 * <?php
 * namespace MyApp\controllers;
 * class MyMainController
 * {
 *     ...
 * }
 */
$application_namespace = '';

/*---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 * If you want this front controller to use a different "application" folder
 * than the default one you can set its name here. The folder can also be
 * renamed or relocated anywhere on your server. If you do, use a full server
 * path. For more info please see the user guide:
 * http://xylophone.io/user_guide/general/managing_apps.html
 */
$application_folder = 'application';

/*---------------------------------------------------------------
 * NAMESPACE PATHS
 *---------------------------------------------------------------
 * Here you can specify additional namespaces to be searched for classes, and
 * the filesystem path to each one. The path may be absolute, relative to this
 * directory, or relative to the PHP include path.
 *
 * These will be searched after your application namespace, in the order
 * specified here, followed by any further namespaces added in your autoload.php
 * or by calling Xylophone::addNamespace().
 *
 * NOTE: If you override the Xylophone class, your class MUST be in your
 * application namespace/folder or one specified here.
 */
$namespace_paths = array();

//$namespace_paths['namespace'] = '/path/to/namespace/root';

/*---------------------------------------------------------------
 * VIEW FOLDER NAME
 *---------------------------------------------------------------
 * If you want to move the view folder out of the application folder set the
 * path to the folder here. The folder can be renamed and relocated anywhere on
 * your server. If blank, it will default to the standard location inside your
 * application folder. To use this, set the full server path to this folder.
 */
$view_folder = '';

/*---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same directory as this file.
 */
$system_path = 'system';

/*---------------------------------------------------------------
 * OVERRIDE CORE MODULES
 *---------------------------------------------------------------
 * If you want to override core modules, set this variable to TRUE.
 * This will cause the system to search your application path and
 * namespaces for core class overrides.
 */
$override_core = false;

/*---------------------------------------------------------------
 * LIBRARY SEARCH
 *---------------------------------------------------------------
 * Set this variable to TRUE If you want to load libraries from your
 * application or namespace paths, or override system libraries.
 * Otherwise, libraries will only be loaded from your system path.
 */
$library_search = false;

/*---------------------------------------------------------------
 * DEFAULT CONTROLLER
 *---------------------------------------------------------------
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a specific controller
 * class/function here. For most applications, you WILL NOT set your routing
 * here, but it's an option for those special instances where you might want to
 * override the standard routing in a specific front controller that shares a
 * common XY installation.
 *
 * IMPORTANT: If you set the routing here, NO OTHER controller will be callable.
 * In essence, this preference limits your application to ONE specific
 * controller. Leave the function name blank if you need to call functions
 * dynamically via the URI.
 *
 * Un-comment elements of the $routing array below to use this feature
 */
$routing = array();

// The directory name, relative to the "controllers" folder. Leave blank
// if your controller is not in a sub-folder within the "controllers" folder
// $routing['directory'] = '';

// The controller class file name. Example: mycontroller
// $routing['controller'] = '';

// The controller function you wish to be called.
// $routing['function']    = '';

/*---------------------------------------------------------------
 * CUSTOM CONFIG VALUES
 *---------------------------------------------------------------
 * The $config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $config array below to use this feature
 */
$config = array();

// $config['name_of_config_item'] = 'value of config item';

//---------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS. DO NOT EDIT BELOW THIS LINE
//---------------------------------------------------------------


// Set the current directory correctly for CLI requests
defined('STDIN') && chdir(dirname(__FILE__));

// Define BASEPATH for direct access restriction on application scripts
define('BASEPATH', __DIR__);

// Get base paths to resolve against
// We check absolute (or relative to this directory) and relative to includes
$resolve_bases = array('');
foreach (explode(PATH_SEPARATOR, get_include_path()) as $inc) {
    $resolve_bases[] = rtrim($inc, '\/').DIRECTORY_SEPARATOR;
}

// Resolve the system path
$resolved = false;
foreach ($resolve_bases as $base) {
    // Check against base
    if (is_dir($base.$system_path)) {
        // Found it - clean with realpath and add trailing slash
        $system_path = realpath($base.$system_path).DIRECTORY_SEPARATOR;
        $resolved = true;
        break;
    }
}
if (!$resolved) {
    // Alert user to misconfiguration
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your system folder path does not appear to be set correctly. Please fix it in the following file: '.
        basename(__FILE__);
    exit(EXIT_CONFIG);
}

// Load the bootstrap file and launch
require_once $system_path.'core'.DIRECTORY_SEPARATOR.'Xylophone.php';
$XY = Xylophone\core\Xylophone::instance(array(
    'environment' => $environment,
    'ns_paths' => array_merge(array($application_namespace => $application_folder), $namespace_paths),
    'view_paths' => array($view_folder),
    'system_path' => $system_path,
    'resolve_bases' => $resolve_bases,
    'override_core' => $override_core,
    'library_search' => $library_search
));
$XY->play($config, $routing);

