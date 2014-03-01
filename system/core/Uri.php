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
namespace Xylophone\core;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * URI Class
 *
 * Parses URIs and determines routing
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/uri.html
 */

class Uri
{
    /** @var    string  Current URI string */
    public $uri_string = '';

    /** @var    string  Routed URI string */
    public $ruri_string = '';

    /** @var    array   List of URI segments */
    public $segments = array();

    /** @var    array   Re-indexed list of routed URI segments - starts at index 1 */
    public $rsegments = array();

    /** @var    string  Permitted URI characters */
    protected $perm_char = '';

    /** @var    bool    Whether query strings are enabled */
    protected $query_str = false;

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // Get config settings - we're likely to need these several times
        $this->perm_char = $XY->config['permitted_uri_chars'];
        $this->query_str = $XY->config['enable_query_strings'];
        $XY->logger->debug('URI Class Initialized');
    }

    /**
     * Load URI string and segments
     *
     * @used-by Router::__construct()
     *
     * @return  void
     */
    public function load()
    {
        global $XY;

        // Get protocol and check for AUTO
        $protocol = strtoupper($XY->config['uri_protocol']);
        if ($protocol === 'AUTO') {
            // Is the request coming from the command line?
            if ($XY->isCli()) {
                return $this->setUriString($this->parseArgv());
            }

            // Is there a PATH_INFO variable? This should be the easiest solution.
            if (isset($_SERVER['PATH_INFO'])) {
                return $this->setUriString($_SERVER['PATH_INFO']);
            }

            // Let's try REQUEST_URI then, this will work in most situations
            if (($uri = $this->parseRequestUri()) !== '') {
                return $this->setUriString($uri);
            }

            // No REQUEST_URI either?... What about QUERY_STRING?
            if (($uri = $this->parseQueryString()) !== '') {
                return $this->setUriString($uri);
            }

            // As a last ditch effort let's try using the $_GET array
            if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') !== '') {
                return $this->setUriString(key($_GET));
            }

            // We've exhausted all our options...
            $this->uri_string = '';
            return;
        }

        // Check for CLI
        if ($protocol === 'CLI') {
            return $this->setUriString($this->parseArgv());
        }

        // Check for a matching method
        $method = 'parse'.implode(array_map('ucfirst', array_map('strtolower', explode('_', $protocol))));
        if (method_exists($this, $method)) {
            return $this->setUriString($this->$method());
        }

        // Try a server or environment variable
        $this->setUriString(isset($_SERVER[$protocol]) ? $_SERVER[$protocol] : @getenv($protocol));
    }

    /**
     * Set routed uri and segments and re-index segments
     *
     * Re-indexes the segment arrays so that they start at 1 rather
     * than 0. Doing so makes it simpler to use methods like
     * segment(n) since there is a 1:1 relationship between the
     * segment array and the actual segments.
     *
     * @used-by Router::__construct()
     *
     * @return  void
     */
    public function setRuriString($uri)
    {
        // Set routed string and segments
        $this->ruri_string = $uri;
        $this->rsegments = $this->explodeSegments($this->ruri_string);

        // Re-index both segment arrays
        array_unshift($this->segments, null);
        array_unshift($this->rsegments, null);
        unset($this->segments[0]);
        unset($this->rsegments[0]);
    }

    /**
     * Filter URI
     *
     * Filters segments for malicious characters.
     *
     * @used-by URI::setUriString()
     * @used-by Router::parseQuery()
     *
     * @param   string  $uri    URI string
     * @return  string  Cleaned string
     */
    public function filterUri($str)
    {
        global $XY;

        // Nothing to do with an empty string
        if ($str === '') {
            return $str;
        }

        // Check for permitted characters and query string support
        if ($this->perm_chars && $this->query_str) {
            // preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to
            // maintain backwards compatibility as many are unaware of how characters in the permitted_uri_chars
            // will be parsed as a regex pattern
            $regex = '|^[' . str_replace(array('\\-', '\-'), '-', preg_quote($this->perm_chars, '-')) . ']+$|i';
            preg_match($regex, $str) || $XY->showError('The URI you submitted has disallowed characters.', 400);
        }

        // Convert programatic characters to entities and return
        $bad =  array('$',     '(',     ')',     '%28',   '%29');
        $good = array('&#36;', '&#40;', '&#41;', '&#40;', '&#41;');
        return str_replace($bad, $good, $str);
    }

    /**
     * Set URI String
     *
     * @used-by URI::load()
     *
     * @param   string  $str    URI string to set
     * @return  void
     */
    protected function setUriString($str)
    {
        global $XY;

        // Filter out control characters and trim slashes
        $this->uri_string = trim($XY->output->removeInvisibleCharacters($str, false), '/');

        // Remove suffix
        $suffix = (string)$XY->config['url_suffix'];
        if ($suffix !== '') {
            $slen = strlen($suffix);
            if (substr($this->uri_string, -$slen) === $suffix) {
                $this->uri_string = substr($this->uri_string, 0, -$slen);
            }
        }

        // Explode segments
        $this->segments = $this->explodeSegments($this->uri_string);
    }

    /**
     * Explode a URI into segments
     *
     * Filters segments with filterUri()
     *
     * @uses    URI::filterUri()
     * @used-by URI::setUriString()
     * @used-by URI::setRuriString()
     *
     * @param   string  $str    URI string
     * @return  array   Segment array
     */
    protected function explodeSegments($str)
    {
        $segments = array();
        foreach (explode('/', preg_replace('|/*(.+?)/*$|', '\\1', $str)) as $val) {
            // Filter segments for security
            (($val = trim($this->filterUri($val))) !== '') && $segments[] = $val;
        }
        return $segments;
    }

    /**
     * Parse REQUEST_URI
     *
     * Will parse REQUEST_URI and automatically detect the URI from it,
     * while fixing the query string if necessary.
     *
     * @used-by URI::load()
     *
     * @return  string
     */
    protected function parseRequestUri()
    {
        // Must have REQUEST_URI and SCRIPT_NAME
        if (!isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
            return '';
        }

        // Parse REQUEST_URI to get URI and query string
        $parts = parse_url($_SERVER['REQUEST_URI']);
        $query = isset($parts['query']) ? $parts['query'] : '';
        $uri = isset($parts['path']) ? rawurldecode($parts['path']) : '';

        // Strip script name and path from URI
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            $uri = (string)substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        }
        elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            $uri = (string)substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }

        // Ensure that where the URI is required to be in the query string (Nginx)
        // a correct URI is found, and fix the QUERY_STRING var and $_GET array.
        if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0) {
            $query = explode('?', $query, 2);
            $uri = rawurldecode($query[0]);
            $_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
        }
        else {
            $_SERVER['QUERY_STRING'] = $query;
        }
        parse_str($_SERVER['QUERY_STRING'], $_GET);

        // Return slash for an empty URI
        if ($uri === '/' || $uri === '') {
            return '/';
        }

        // Do some final cleaning of the URI and return it
        return $this->removeRelativeDirectory($uri);
    }

    /**
     * Parse QUERY_STRING
     *
     * Will parse QUERY_STRING and automatically detect the URI from it.
     *
     * @used-by URI::load()
     *
     * @return  string  URI string
     */
    protected function parseQueryString()
    {
        $uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
        if (trim($uri, '/') === '') {
            return '';
        }

        if (strncmp($uri, '/', 1) === 0) {
            $uri = explode('?', $uri, 2);
            $_SERVER['QUERY_STRING'] = isset($uri[1]) ? $uri[1] : '';
            $uri = rawurldecode($uri[0]);
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);

        return $this->removeRelativeDirectory($uri);
    }

    /**
     * Parse CLI arguments
     *
     * Take each command line argument and assume it is a URI segment.
     *
     * @used-by URI::load()
     *
     * @return  string  URI string
     */
    protected function parseArgv()
    {
        $args = array_slice($_SERVER['argv'], 1);
        return $args ? implode('/', $args) : '';
    }

    /**
     * Remove relative directory (../) and multi slashes (///)
     *
     * Do some final cleaning of the URI and return it, currently only used in self::_parse_request_uri()
     *
     * @param   string  $uri    URI string
     * @return  string  Cleaned string
     */
    protected function removeRelativeDirectory($uri)
    {
        $uris = array();
        $tok = strtok($uri, '/');
        while ($tok !== false) {
            if ((!empty($tok) || $tok === '0') && $tok !== '..') {
                $uris[] = $tok;
            }
            $tok = strtok('/');
        }
        return implode('/', $uris);
    }

    /**
     * Fetch URI Segment
     *
     * @param   int     $n          Segment index
     * @param   mixed   $no_result  What to return if the segment index is not found
     * @return  mixed   Segment if found, otherwise $no_result
     */
    public function segment($n, $no_result = null)
    {
        return isset($this->segments[$n]) ? $this->segments[$n] : $no_result;
    }

    /**
     * Fetch URI "routed" Segment
     *
     * Returns the re-routed URI segment (assuming routing rules are used)
     * based on the index provided. If there is no routing, will return
     * the same result as URI::segment().
     *
     * @param   int     $n          Segment index
     * @param   mixed   $no_result  What to return if the segment index is not found
     * @return  mixed   Segment if found, otherwise $no_result
     */
    public function rsegment($n, $no_result = null)
    {
        return isset($this->rsegments[$n]) ? $this->rsegments[$n] : $no_result;
    }

    /**
     * URI to assoc
     *
     * Generates an associative array of URI data starting at the supplied
     * segment index. For example, if this is your URI:
     *      example.com/user/search/name/joe/location/UK/gender/male
     * You can use this method to generate an array with this prototype:
     *      array (
     *          name => joe
     *          location => UK
     *          gender => male
     *      )
     *
     * @param   int     $start      Starting segment index (default: 3)
     * @param   array   $default    Default values
     * @return  array   Associative array of segments
     */
    public function uriToAssoc($start = 3, $default = array())
    {
        return $this->toAssoc($start, $default, $this->segments);
    }

    /**
     * Routed URI to assoc
     *
     * Identical to URI::uriToAssoc(), only it uses the re-routed
     * segment array.
     *
     * @param   int     $start      Starting segment index (default: 3)
     * @param   array   $default    Default values
     * @return  array   Associative array of segments
     */
    public function ruriToAssoc($start = 3, $default = array())
    {
        return $this->toAssoc($start, $default, $this->rsegments);
    }

    /**
     * Internal URI-to-assoc
     *
     * Generates a key/value pair from the URI string or routed URI string.
     *
     * @used-by URI::uriToAssoc()
     * @used-by URI::ruriToAssoc()
     *
     * @param   int     $start      Starting segment index (default: 3)
     * @param   array   $default    Default values
     * @param   array   $segments   Reference to segment array
     * @return  array   Associative array of segments
     */
    protected function toAssoc($start = 3, $default = array(), &$segments)
    {
        // Index must be numeric and within segment count
        if (!is_numeric($start)) {
            return $default;
        }
        if ($start >= $count($segments)) {
            return count($default) ? array_fill_keys($default, null) : array();
        }

        // Build associative array
        $key = null;
        $assoc = array();
        foreach (array_slice($segments, ($start - 1)) as $seg) {
            if ($key === null) {
                $key = $seg;
            }
            else {
                $assoc[$key] = $seg;
                $key = null;
            }
        }

        // Null any unpaired key
        is_null($key) || $assoc[$key] = null;

        // Apply any defaults
        foreach ($default as $val) {
            array_key_exists($val, $assoc) || $assoc[$val] = null;
        }

        return $assoc;
    }

    /**
     * Assoc to URI
     *
     * Generates a URI string from an associative array.
     *
     * @param   array   $assoc  Input array of key/value pairs
     * @return  string  URI string
     */
    public function assocToUri($assoc)
    {
        $temp = array();
        foreach ((array)$assoc as $key => $val) {
            $temp[] = $key;
            $temp[] = $val;
        }

        return implode('/', $temp);
    }

    /**
     * Slash segment
     *
     * Fetches a segment with a slash.
     *
     * @uses    URI::addSlash()
     *
     * @param   int     $n      Segment index
     * @param   string  $where  Where to add the slash ('trailing' or 'leading')
     * @return  string  Segment with slash
     */
    public function slashSegment($n, $where = 'trailing')
    {
        return $this->addSlash($n, $where, $this->segments);
    }

    /**
     * Slash routed segment
     *
     * Fetches a routed segment with a slash.
     *
     * @uses    URI::addSlash()
     *
     * @param   int     $n      Segment index
     * @param   string  $where  Where to add the slash ('trailing' or 'leading')
     * @return  string  Segment with slash
     */
    public function slashRsegment($n, $where = 'trailing')
    {
        return $this->addSlash($n, $where, $this->rsegments);
    }

    /**
     * Internal Slash segment
     *
     * Fetches a Segment and adds a slash to it.
     *
     * @used-by URI::slashSegment()
     * @used-by URI::slashRsegment()
     *
     * @param   int     $n          Segment index
     * @param   string  $where      Where to add the slash ('leading', 'trailing' or 'both')
     * @param   array   $segments   Reference to segment array
     * @return  string  Segment with slash
     */
    protected function addSlash($n, $where = 'trailing', &$segments)
    {
        $leading = ($where === 'trailing') ? '' : '/';
        $trailing = ($where === 'leading') ? '' : '/';
        return $leading.$segments[$n].$trailing;
    }
}

