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
 * @copyright   Copyright (c) 2013, Xylophone Team (http://xylophone.io/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://xylophone.io
 * @since       Version 1.0
 * @filesource
 */
namespace Mocks\core;

class Uri extends \Xylophone\core\Uri
{
    /**
     * Set URI String (made public)
     *
     * @param   string  $str    URI string to set
     * @return  void
     */
    public function setUriString($str)
    {
        parent::setUriString($str);
    }

    /**
     * Explode a URI into segments (made public)
     *
     * @param   string  $str    URI string
     * @return  array   Segment array
     */
    public function explodeSegments($str)
    {
        return parent::explodeSegments($str);
    }

    /**
     * Parse REQUEST_URI (made public)
     *
     * @return  string
     */
    public function parseRequestUri()
    {
        return parent::parseRequestUri();
    }

    /**
     * Parse QUERY_STRING (made public)
     *
     * @return  string  URI string
     */
    public function parseQueryString()
    {
        return parent::parseQueryString();
    }

    /**
     * Parse CLI arguments (made public)
     *
     * @return  string  URI string
     */
    public function parseArgv()
    {
        return parent::parseArgv();
    }

    /**
     * Remove relative directory (../) and multi slashes (///) (made public)
     *
     * @param   string  $uri    URI string
     * @return  string  Cleaned string
     */
    public function removeRelativeDirectory($uri)
    {
        return parent::removeRelativeDirectory($uri);
    }

    /**
     * Internal URI-to-assoc (made public)
     *
     * @param   int     $start      Starting segment index
     * @param   array   $default    Default values
     * @param   array   $segments   Reference to segment array
     * @return  array   Associative array of segments
     */
    public function toAssoc($start, $default, &$segments)
    {
        return parent::toAssoc($start, $default, $segments);
    }

    /**
     * Internal Slash segment (made public)
     *
     * @param   int     $n          Segment index
     * @param   string  $where      Where to add the slash ('leading', 'trailing' or 'both')
     * @param   array   $segments   Reference to segment array
     * @return  string  Segment with slash
     */
    public function addSlash($n, $where, &$segments)
    {
        return parent::addSlash($n, $where, $segments);
    }
}

