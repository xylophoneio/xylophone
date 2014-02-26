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
 * Language Class
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/language.html
 */
class Lang
{
    /** @var    array   List of translations */
    public $language = array();

    /** @var    array   List of loaded language files */
    public $is_loaded = array();

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;
        $XY->logger->debug('Language Class Initialized');
    }

    /**
     * Load a language file
     *
     * @param   mixed   $langfile   Language file name
     * @param   string  $idiom      Language name (english, etc.)
     * @param   bool    $return     Whether to return the loaded array of translations
     * @param   bool    $add_suffix Whether to add suffix to $langfile
     * @param   string  $alt_path   Alternative path to look for the language file
     * @return  mixed   Array containing translations, if $return is set to TRUE
     */
    public function load($langfile, $idiom = '', $return = false, $add_suffix = true, $alt_path = '')
    {
        global $XY;

        if (is_array($langfile)) {
            $return && $retval = array();

            foreach ($langfile as $lang) {
                $ret = $this->load($lang, $idiom, $return, $add_suffix, $alt_path);
                if (!$ret) {
                    return false;
                }
                $return && $retval[$lang] = $ret;
            }

            return $return ? $retval : true;
        }

        $langfile = str_replace('.php', '', $langfile);
        $add_suffix && $langfile = str_replace('_lang', '', $langfile).'_lang';
        $langfile .= '.php';

        if (empty($idiom) || !ctype_alpha($idiom)) {
            $idiom = empty($XY->config['language']) ? 'english' : $XY->config['language'];
        }

        if (!$return && isset($this->is_loaded[$langfile]) && $this->is_loaded[$langfile] === $idiom) {
            return true;
        }

        // Load the base file, so any others found can override it
        $path = 'language/'.$idiom.'/'.$langfile;
        $found = @include($XY->system_path.$path);

        // Do we have an alternative path to look in?
        if ($alt_path !== '') {
            $found |= @include($alt_path.$path);
        }
        else {
            foreach ($XY->ns_paths as $ns_path) {
                $found |= @include($ns_path.$path);
            }
        }

        $found || $XY->showError('Unable to load the requested language file: '.$path);

        if (!isset($lang) || !is_array($lang)) {
            $XY->logger->error('Language file contains no data: '.$path);
            if ($return) {
                return array();
            }
            return false;
        }

        if ($return) {
            return $lang;
        }

        $this->is_loaded[$langfile] = $idiom;
        $this->language = array_merge($this->language, $lang);

        $XY->logger->debug('Language file loaded: '.$path);
        return true;
    }

    /**
     * Language line
     *
     * Fetches a single line of text from the language array
     *
     * @param   string  $line       Language line key
     * @param   bool    $log_errors Whether to log an error message if the line is not found
     * @return  string  Translation
     */
    public function line($line, $log_errors = true)
    {
        global $XY;

        $value = ($line === '' || !isset($this->language[$line])) ? false : $this->language[$line];

        // Because killer robots like unicorns!
        if ($value === false && $log_errors === true) {
            $XY->logger->error('Could not find the language line "'.$line.'"');
        }

        return $value;
    }
}

