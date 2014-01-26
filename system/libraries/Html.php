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
namespace Xylophone\libraries;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xylophone HTML Library
 *
 * @package     Xylophone
 * @subpackage  libraries
 * @link        http://xylophone.io/user_guide/libraries/html.html
 */
class Html
{
    /** @var    array   Valid HTML document types */
    protected $doctypes = null;

    /**
     * Heading
     *
     * Generates an HTML heading tag.
     *
     * @param   string  $data   Content
     * @param   int     $level  Heading level
     * @param   mixed   $attrib Tag attributes
     * @return  string  Heading markup
     */
    public function heading($data = '', $level = '1', $attrib = '')
    {
        return '<h'.$level.$this->stringifyAttributes($attrib).'>'.$data.'</h'.$level.'>';
    }

    /**
     * Unordered List
     *
     * Generates an HTML unordered list from an single or multi-dimensional array.
     *
     * @param   array   $list   List items
     * @param   mixed   $attrib Tag attributes
     * @return  string  UL markup
     */
    public function ul($list, $attrib = '')
    {
        return $this->list('ul', $list, $attrib);
    }

    /**
     * Ordered List
     *
     * Generates an HTML ordered list from an single or multi-dimensional array.
     *
     * @param   array   $list   List items
     * @param   mixed   $attrib Tag attributes
     * @return  string  UL markup
     */
    public function ol($list, $attrib = '')
    {
        return $this->list('ol', $list, $attrib);
    }

    /**
     * Generate list markup
     *
     * Generates an HTML ordered or unordered list from an single or multi-dimensional array.
     *
     * @param   string  $type   List type
     * @param   array   $list   List items
     * @param   mixed   $attrib List tag attributes
     * @param   int     $depth  Indentation depth
     * @return  string  List markup
     */
    protected function list($type = 'ul', $list = array(), $attrib = '', $depth = 0)
    {
        // If an array wasn't submitted there's nothing to do...
        if (!is_array($list)) {
            return $list;
        }

        // Set the indentation based on the depth
        $out = str_repeat(' ', $depth);

        // Write the opening list tag
        $out .= '<'.$type.$this->stringifyAttributes($attrib).">\n";

        // Cycle through the list elements.  If an array is
        // encountered we will recursively call _list()
        static $_last_list_item = '';
        foreach ($list as $key => $val) {
            $_last_list_item = $key;

            $out .= str_repeat(' ', $depth + 2).'<li>';

            if (is_array($val)) {
                $out .= $_last_list_item."\n".$this->list($type, $val, '', $depth + 4).str_repeat(' ', $depth + 2);
            }
            else {
                $out .= $val;
            }

            $out .= "</li>\n";
        }

        // Set the indentation for the closing tag and apply it
        return $out.str_repeat(' ', $depth).'</'.$type.">\n";
    }

    /**
     * Break
     *
     * Generates HTML BR tags based on number supplied
     *
     * @param   int     $count  Number of times to repeat the tag
     * @return  string  BR markup
     */
    public function br($count = 1)
    {
        return str_repeat('<br />', $count);
    }

    /**
     * Image
     *
     * Generates an <img /> element
     *
     * @param   mixed   $src    Source URL string or array of attributes
     * @param   bool    $index  Whether to resolve URL relative to the index page
     * @param   mixed   $attrib Tag attributes
     * @return  string
     */
    public function img($src = '', $index = false, $attributes = '')
    {
        global $XY;

        // Convert source to array
        is_array($src) || $src = array('src' => $src);

        // If there is no alt attribute defined, set it to an empty string
        isset($src['alt']) || $src['alt'] = '';

        // Translate source attribute as necessary
        if (isset($src['src']) && strpos($src['src'], '://') === false) {
            $src['src'] = $index ? $XY->config->siteUrl($src['src']) :
                $XY->config->slashItem('base_url').$src['src'];
        }

        return '<img'.$this->stringifyAttributes($src).$this->stringifyAttributes($attrib).' />';
    }

    /**
     * Doctype
     *
     * Generates a page document type declaration
     *
     * Examples of valid options: html5, xhtml-11, xhtml-strict, xhtml-trans,
     * xhtml-frame, html4-strict, html4-trans, and html4-frame.
     * All values are saved in the doctypes config file.
     *
     * @param   string  $type   The doctype to be generated
     * @return  mixed   DOCTYPE markup on success, otherwise FALSE
     */
    public function doctype($type = 'xhtml1-strict')
    {
        global $XY;

        if ($this->doctypes === null) {
            $this->doctypes = $XY->config->get('doctypes.php', 'doctypes');
            is_array($this->doctypes) || $this->doctypes = array();
        }

        return isset($this->doctypes[$type]) ? $this->doctypes[$type] : false;
    }

    /**
     * Link
     *
     * Generates link to a CSS file
     *
     * @param   mixed   $href   Stylesheet href string or array of attributes
     * @param   string  $rel    Rel attribute
     * @param   string  $type   Link type
     * @param   string  $title  Link title
     * @param   string  $media  Media type
     * @param   bool    $index  Whether to resolve URL relative to the index page
     * @return  string  Link markup
     */
    public function linkTag($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '', $index = false)
    {
        global $XY;

        // Convert href to an array and fill in attributes
        is_array($href) || $href = array('href' => $href);
        isset($href['rel']) || $href['rel'] = $rel;
        isset($href['type']) || $href['type'] = $type;
        isset($href['title']) || $title == '' || $href['title'] = $title;
        isset($href['media']) || $media == '' || $href['media'] = $media;

        // Translate href attribute as necessary
        if (isset($href['href']) && strpos($href['href'], '://') === false) {
            $href['href'] = $index ? $XY->config->siteUrl($href['href']) :
                $XY->config->slashItem('base_url').$href['href'];
        }

        return '<link '.$this->stringifyAttributes($href)."/>\n";
    }

    /**
     * Generates meta tags from an array of key/values
     *
     * @param   array
     * @param   string
     * @param   string
     * @param   string
     * @return  string
     */
    public function meta($name = '', $content = '', $type = 'name', $newline = "\n")
    {
        // Since we allow the data to be passed as strings, a simple array
        // or a multidimensional one, we need to do a little prepping.

        // Convert name to array
        is_array($name) || $name = array('name' => $name);

        // Set attributes unless already multidimensional
        if (isset($name['name'])) {
            isset($name['content']) || $content === '' || $name['content'] = $content;
            isset($name['type']) || $name['type'] = $type;
            $name = array($name);
        }

        $out = '';
        foreach ($name as $meta) {
            $type = (!isset($meta['type']) || $meta['type'] === 'name') ? 'name' : 'http-equiv';
            $name = isset($meta['name']) ? $meta['name'] : '';
            $content = isset($meta['content']) ? $meta['content'] : '';
            $newline = isset($meta['newline']) ? $meta['newline'] : "\n";
            $out .= '<meta '.$type.'="'.$name.'" content="'.$content.'" />'.$newline;
        }

        return $out;
    }

    /**
     * Generates non-breaking space entities based on number supplied
     *
     * @param   int     $count  Number of times to repeat the tag
     * @return  string  Non-breaking space markup
     */
    public function nbs($count = 1)
    {
        return str_repeat('&nbsp;', $count);
    }

    /**
     * Stringify attributes for use in HTML tags.
     *
     * Helper function used to convert a string, array, or object
     * of attributes to a string.
     *
     * @param   mixed   $attrib Attributes string, array, or object
     * @param   bool    $js     Whether to generate Javascript string
     * @return  string  Attribute string
     */
    public function stringifyAttributes($attrib, $js = false)
    {
        $atts = null;

        if (empty($attrib)) {
            return $atts;
        }

        if (is_string($attrib)) {
            return ' '.$attrib;
        }

        if ($js) {
            // Make comma-separated list
            foreach ((array)$attrib as $key => $val) {
                $atts .= $key.'='.$val.',';
            }
            $atts = rtrim($atts, ',');
        }
        else {
            // Make HTML attributes
            foreach ((array)$attrib as $key => $val) {
                $atts .= ' '.$key.'="'.$val.'"';
            }
        }

        return $atts;
    }
}

