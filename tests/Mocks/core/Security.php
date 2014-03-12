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
namespace Mocks\core;

/**
 * Mock Input Class
 *
 * @package Mocks
 */
class Security extends \Xylophone\core\Security
{
    /** @var    string  Random Hash for protecting URLs (made public) */
    public $xss_hash = '';

    /** @var    string  Random hash for Cross Site Request Forgery protection cookie (made public) */
    public $csrf_hash = '';

    /**
     * Compact Exploded Words (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Compact string
     */
    public function compactExplodedWords($matches)
    {
        return parent::compactExplodedWords($matches);
    }

    /**
     * Remove Evil HTML Attributes (like event handlers and style) (made public)
     *
     * @param   string  $str        The string to check
     * @param   bool    $is_image   Whether the input is an image
     * @return  string  The string with the evil attributes removed
     */
    public function removeEvilAttributes($str, $is_image)
    {
        return parent::removeEvilAttributes($str, $is_image);
    }

    /**
     * Sanitize Naughty HTML (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Sanitized string
     */
    public function sanitizeNaughtyHtml($matches)
    {
        return parent::sanitizeNaughtyHtml($matches);
    }

    /**
     * JS Link Removal (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Cleaned string
     */
    public function jsLinkRemoval($matches)
    {
        return parent::jsLinkRemoval($matches);
    }

    /**
     * JS Image Removal (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Cleaned string
     */
    public function jsImgRemoval($matches)
    {
        return parent::jsImgRemoval($matches);
    }

    /**
     * Attribute Conversion (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Cleaned string
     */
    public function convertAttribute($matches)
    {
        return parent::convertAttribute($matches);
    }

    /**
     * Filter Attributes (made public)
     *
     * @param   string  $str    Input string
     * @return  string  Filtered string
     */
    public function filterAttributes($str)
    {
        return parent::filterAttributes($str);
    }

    /**
     * HTML Entity Decode Callback (made public)
     *
     * @param   array   $matches    Matches
     * @return  string  Cleaned string
     */
    public function decodeEntity($matches)
    {
        return parent::decodeEntity($matches);
    }

    /**
     * Validate URL entities (made public)
     *
     * @param   string  $str    Input string
     * @return  string  Validated string
     */
    public function validateEntities($str)
    {
        return parent::validateEntities($str);
    }

    /**
     * Do Never Allowed (made public)
     *
     * @param   string  $str    Input string
     * @return  string  Filtered string
     */
    public function doNeverAllowed($str)
    {
        return parent::doNeverAllowed($str);
    }

    /**
     * Set CSRF Hash and Cookie (made public)
     *
     * @return  string  CSRF hash string
     */
    public function csrfSetHash()
    {
        return parent::csrfSetHash();
    }
}

