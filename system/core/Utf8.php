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
 * Utf8 Class
 *
 * Provides support for UTF-8 environments
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylopnone.io/user_guide/libraries/utf8.html
 */
class Utf8
{
    /** @var    bool    Whether multibyte support is enabled */
    public $mb_enabled = false;

    /** @var    bool    Whether iconv is enabled */
    public $iconv_enabled = true;

    /** @var    bool    Whether UTF-8 support is enabled */
    public $enabled = false;

    /**
     * Constructor
     *
     * Determines if UTF-8 support is to be enabled.
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;
        $charset = strtoupper($XY->config['charset']);

        // Set internal encoding for multibyte string functions if necessary
        // and set a flag so we don't have to keep checking
        if (extension_loaded('mbstring')) {
            $this->mb_enabled = true;
            mb_internal_encoding($charset);
        }

        // Flag whether iconv is enabled
        $this->iconv_enabled = function_exists('iconv');

        // Enable UTF-8 if mbstring enabled, iconv installed, PCRE supports UTF-8,
        // and charset is UTF-8
        $this->enabled = ($this->mb_enabled && $this->iconv_enabled && @preg_match('/./u', 'é') === 1 &&
            $charset === 'UTF-8');
        $XY->logger->debug('Utf8 Class Initialized and '.($this->enabled ? 'Enabled' : 'Disabled'));
    }

    /**
     * Clean UTF-8 strings
     *
     * Ensures strings contain only valid UTF-8 characters.
     *
     * @uses    Utf8::isAscii()
     *
     * @param   string  $str    String to clean
     * @return  string  Cleaned string
     */
    public function cleanString($str)
    {
        // Convert string if enabled and not ASCII
        return (!$this->iconv_enabled || $this->isAscii($str)) ? $str : @iconv('UTF-8', 'UTF-8//IGNORE', $str);
    }

    /**
     * Remove ASCII control characters
     *
     * Removes all ASCII control characters except horizontal tabs, line feeds,
     * and carriage returns, as all others can cause problems in XML.
     *
     * @uses    Output::removeInvisibleCharacters()
     *
     * @param   string  $str    String to clean
     * @return  string  Cleaned string
     */
    public function safeAsciiForXml($str)
    {
        global $XY;
        return $XY->output->removeInvisibleCharacters($str, false);
    }

    /**
     * Convert to UTF-8
     *
     * Attempts to convert a string to UTF-8.
     *
     * @param   string  $str        Input string
     * @param   string  $encoding   Input encoding
     * @return  mixed   UTF-8 encoded string or FALSE on failure
     */
    public function convertToUtf8($str, $encoding) {
        // Check for iconv or MB
        if ($this->iconv_enabled) {
            return @iconv($encoding, 'UTF-8', $str);
        }
        elseif ($this->mb_enabled) {
            return @mb_convert_encoding($str, 'UTF-8', $encoding);
        }

        return false;
    }

    /**
     * Is ASCII?
     *
     * Tests if a string is standard 7-bit ASCII or not.
     *
     * @param   string  $str    String to check
     * @return  bool    TRUE if ASCII, otherwise FALSE
     */
    public function isAscii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) === 0);
    }
}

