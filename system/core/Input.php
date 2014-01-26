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
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/input.html
 */
class Input
{
    /** @var    string  IP address of the current user */
    public $ip_address = false;

    /** @var    string  User agent string */
    public $user_agent = false;

    /** @var    bool    Allow GET array or set to empty */
    protected $allow_get_array = true;

    /** @var    bool    Standardize new lines flag */
    protected $standardize_newlines = true;

    /** @var    bool    Enable XSS filtering for all GET, POST, and COOKIE data */
    protected $enable_xss = false;

    /** @var    bool    Enable CSRF cookie token */
    protected $enable_csrf = false;

    /** @var    array   List of all HTTP request headers */
    protected $headers = array();

    /** @var    array   Input stream data - parsed from php://input at runtime */
    protected $input_stream = null;

    /**
     * Constructor
     *
     * Determines whether to globally enable the XSS processing
     * and whether to allow the $_GET array.
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        $this->allow_get_array = ($XY->config['allow_get_array'] === true);
        $this->enable_xss = ($XY->config['global_xss_filtering'] === true);
        $this->enable_csrf = ($XY->config['csrf_protection'] === true);

        // Sanitize global arrays
        $this->sanitizeGlobals();
        $XY->logger->debug('Input Class Initialized');
    }

    /**
     * Fetch from array
     *
     * Internal method used to retrieve values from global arrays.
     *
     * @param   array   $array      Reference to $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param   string  $index      Index for item to be fetched from $array
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    protected function fetchFromArray(&$array, $index = '', $xss_clean = false)
    {
        global $XY;

        if (isset($array[$index])) {
            // Get index
            $value = $array[$index];
        }
        elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
            // Index contains array notation
            $value = $array;
            for ($i = 0; $i < $count; $i++) {
                $key = trim($matches[0][$i], '[]');

                // Empty notation will return the value as array
                if ($key === '') {
                    break;
                }

                if (!isset($value[$key])) {
                    return null;
                }
                $value = $value[$key];
            }
        }
        else {
            return null;
        }

        return $xss_clean ? $XY->security->xssClean($value) : $value;
    }

    /**
     * Fetch an item from the GET array
     *
     * @param   string  $index      Index for item to be fetched from $_GET
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function get($index = null, $xss_clean = false)
    {
        // Check if a field has been provided
        if ($index === null) {
            if (empty($_GET)) {
                return array();
            }
            $get = array();

            // loop through the full _GET array
            foreach (array_keys($_GET) as $key) {
                $get[$key] = $this->fetchFromArray($_GET, $key, $xss_clean);
            }
            return $get;
        }

        return $this->fetchFromArray($_GET, $index, $xss_clean);
    }

    /**
     * Fetch an item from the POST array
     *
     * @param   string  $index      Index for item to be fetched from $_POST
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function post($index = null, $xss_clean = false)
    {
        // Check if a field has been provided
        if ($index === null) {
            if (empty($_POST)) {
                return array();
            }
            $post = array();

            // Loop through the full _POST array and return it
            foreach (array_keys($_POST) as $key) {
                $post[$key] = $this->fetchFromArray($_POST, $key, $xss_clean);
            }
            return $post;
        }

        return $this->fetchFromArray($_POST, $index, $xss_clean);
    }

    /**
     * Fetch an item from POST data with fallback to GET
     *
     * @param   string  $index      Index for item to be fetched from $_POST or $_GET
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function postGet($index = '', $xss_clean = false)
    {
        return isset($_POST[$index]) ? $this->post($index, $xss_clean) : $this->get($index, $xss_clean);
    }

    /**
     * Fetch an item from GET data with fallback to POST
     *
     * @param   string  $index      Index for item to be fetched from $_GET or $_POST
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function getPost($index = '', $xss_clean = false)
    {
        return isset($_GET[$index]) ? $this->get($index, $xss_clean) : $this->post($index, $xss_clean);
    }

    /**
     * Fetch an item from the COOKIE array
     *
     * @param   string  $index      Index for item to be fetched from $_COOKIE
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function cookie($index = '', $xss_clean = false)
    {
        return $this->fetchFromArray($_COOKIE, $index, $xss_clean);
    }

    /**
     * Fetch an item from the SERVER array
     *
     * @param   string  $index      Index for item to be fetched from $_SERVER
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function server($index = '', $xss_clean = false)
    {
        return $this->fetchFromArray($_SERVER, $index, $xss_clean);
    }

    /**
     * Fetch an item from the php://input stream
     *
     * Useful when you need to access PUT, DELETE or PATCH request data.
     *
     * @param   string  $index      Index for item to be fetched
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed   Fetched value or array
     */
    public function inputStream($index = '', $xss_clean = false)
    {
        // The input stream can only be read once - check if we have already done so
        if (is_array($this->input_stream)) {
            return $this->fetchFromArray($this->input_stream, $index, $xss_clean);
        }

        // Parse the input stream in our cache var
        parse_str(file_get_contents('php://input'), $this->input_stream);
        if (!is_array($this->input_stream)) {
            $this->input_stream = array();
            return null;
        }

        return $this->fetchFromArray($this->input_stream, $index, $xss_clean);
    }

    /**
     * Set cookie
     *
     * Accepts an arbitrary number of parameters (up to 7) or an associative
     * array in the first parameter containing all the values.
     *
     * @param   string  $name       Cookie name or an array containing parameters
     * @param   string  $value      Cookie value
     * @param   int     $expire     Cookie expiration time in seconds
     * @param   string  $domain     Cookie domain (e.g.: '.yourdomain.com')
     * @param   string  $path       Cookie path (default: '/')
     * @param   string  $prefix     Cookie name prefix
     * @param   bool    $secure     Whether to only transfer cookies via SSL
     * @param   bool    $httponly   Whether to only makes the cookie accessible via HTTP (no javascript)
     * @return  void
     */
    public function setCookie($name, $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false, $httponly = false)
    {
        global $XY;

        if (is_array($name)) {
            // Always leave 'name' in last place, as the loop will break otherwise, due to $$item
            foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name') as $item) {
                isset($name[$item]) && $$item = $name[$item];
            }
        }

        $prefix === '' && $XY->config['cookie_prefix'] !== '' && $prefix = $XY->config['cookie_prefix'];
        $domain == '' && $XY->config['cookie_domain'] != '' && $domain = $XY->config['cookie_domain'];
        $path === '/' && $XY->config['cookie_path'] !== '/' && $path = $XY->config['cookie_path'];
        $secure === false && $XY->config['cookie_secure'] !== false && $secure = $XY->config['cookie_secure'];
        $httponly === false && $XY->config['cookie_httponly'] !== false && $httponly = $XY->config['cookie_httponly'];

        if (is_numeric($expire)) {
            $expire = ($expire > 0) ? time() + $expire : 0;
        }
        else {
            $expire = time() - 86500;
        }

        setcookie($prefix.$name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Fetch the IP Address
     *
     * Determines and validates the visitor's IP address.
     *
     * @return  string  IP address
     */
    public function ipAddress()
    {
        global $XY;

        if ($this->ip_address !== false) {
            return $this->ip_address;
        }
        $proxy_ips = $XY->config['proxy_ips'];
        empty($proxy_ips) || is_array($proxy_ips) || $proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
        $this->ip_address = $this->server('REMOTE_ADDR');

        if ($proxy_ips) {
            foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP') as $header) {
                if (($spoof = $this->server($header)) !== null) {
                    // Some proxies typically list the whole chain of IP
                    // addresses through which the client has reached us.
                    // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                    sscanf($spoof, '%[^,]', $spoof);
                    if ($this->validIp($spoof)) {
                        break;
                    }
                    $spoof = null;
                }
            }

            if ($spoof) {
                for ($i = 0, $c = count($proxy_ips); $i < $c; $i++) {
                    // Check if we have an IP address or a subnet
                    if (strpos($proxy_ips[$i], '/') === false) {
                        // An IP address (and not a subnet) is specified.
                        // We can compare right away.
                        if ($proxy_ips[$i] === $this->ip_address) {
                            $this->ip_address = $spoof;
                            break;
                        }

                        continue;
                    }

                    // We have a subnet ... now the heavy lifting begins
                    isset($separator) || $separator = $this->validIp($this->ip_address, 'ipv6') ? ':' : '.';

                    // If the proxy entry doesn't match the IP protocol - skip it
                    if (strpos($proxy_ips[$i], $separator) === false) {
                        continue;
                    }

                    // Convert the REMOTE_ADDR IP address to binary, if needed
                    if (!isset($ip, $sprintf)) {
                        if ($separator === ':') {
                            // Make sure we're have the "full" IPv6 format
                            $colons = str_repeat(':', 9 - substr_count($this->ip_address, ':'));
                            $ip = explode(':', str_replace('::', $colons, $this->ip_address));

                            for ($i = 0; $i < 8; $i++) {
                                $ip[$i] = intval($ip[$i], 16);
                            }

                            $sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
                        }
                        else {
                            $ip = explode('.', $this->ip_address);
                            $sprintf = '%08b%08b%08b%08b';
                        }

                        $ip = vsprintf($sprintf, $ip);
                    }

                    // Split the netmask length off the network address
                    sscanf($proxy_ips[$i], '%[^/]/%d', $netaddr, $masklen);

                    // Again, an IPv6 address is most likely in a compressed form
                    if ($separator === ':') {
                        $colons = str_repeat(':', 9 - substr_count($netaddr, ':'));
                        $netaddr = explode(':', str_replace('::', $colons, $netaddr));
                        for ($i = 0; $i < 8; $i++) {
                            $netaddr[$i] = intval($netaddr[$i], 16);
                        }
                    }
                    else {
                        $netaddr = explode('.', $netaddr);
                    }

                    // Convert to binary and finally compare
                    if (strncmp($ip, vsprintf($sprintf, $netaddr), $masklen) === 0) {
                        $this->ip_address = $spoof;
                        break;
                    }
                }
            }
        }

        $this->validIp($this->ip_address) || $this->ip_address = '0.0.0.0';
        return $this->ip_address;
    }

    /**
     * Validate IP Address
     *
     * @param   string  $ip     IP address
     * @param   string  $which  IP protocol: 'ipv4' or 'ipv6'
     * @return  bool    TRUE if valid, otherwise FALSE
     */
    public function validIp($ip, $which = '')
    {
        switch (strtolower($which)) {
            case 'ipv4':
                $which = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $which = FILTER_FLAG_IPV6;
                break;
            default:
                $which = null;
                break;
        }

        return (bool)filter_var($ip, FILTER_VALIDATE_IP, $which);
    }

    /**
     * Fetch User Agent string
     *
     * @return  mixed   User Agent string or NULL if it doesn't exist
     */
    public function user_agent()
    {
        if ($this->user_agent !== false) {
            return $this->user_agent;
        }
        return $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * Sanitize Globals
     *
     * Internal method serving for the following purposes:
     *
     *	- Unsets $_GET data (if query strings are not enabled)
     *	- Unsets all globals if register_globals is enabled
     *	- Cleans POST, COOKIE and SERVER data
     * 	- Standardizes newline characters to PHP_EOL
     *
     * @return  void
     */
    protected function sanitizeGlobals()
    {
        global $XY;

        // It would be "wrong" to unset any of these GLOBALS.
        $protected = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            'GLOBALS',
            'HTTP_RAW_POST_DATA',
            'system_folder',
            'application_folder',
            'BM',
            'EXT',
            'CFG',
            'URI',
            'RTR',
            'OUT',
            'IN'
        );

        // Unset globals for security.
        // This is effectively the same as register_globals = off
        // PHP 5.4 no longer has the register_globals functionality.
        if (!$XY->isPhp('5.4')) {
            foreach (array($_GET, $_POST, $_COOKIE) as $global) {
                if (is_array($global)) {
                    foreach ($global as $key => $val) {
                        if (!in_array($key, $protected)) {
                            global $$key;
                            $$key = null;
                        }
                    }
                }
                elseif (!in_array($global, $protected)) {
                    global $$global;
                    $$global = null;
                }
            }
        }

        // Is $_GET data allowed? If not we'll set the $_GET to an empty array
        if ($this->allow_get_array === false) {
            $_GET = array();
        }
        elseif (is_array($_GET) && count($_GET) > 0) {
            foreach ($_GET as $key => $val) {
                $_GET[$this->cleanInputKeys($key)] = $this->cleanInputData($val);
            }
        }

        // Clean $_POST Data
        if (is_array($_POST) && count($_POST) > 0) {
            foreach ($_POST as $key => $val) {
                $_POST[$this->cleanInputKeys($key)] = $this->cleanInputData($val);
            }
        }

        // Clean $_COOKIE Data
        if (is_array($_COOKIE) && count($_COOKIE) > 0) {
            // Also get rid of specially treated cookies that might be set by a server
            // or silly application, that are of no use to a CI application anyway
            // but that when present will trip our 'Disallowed Key Characters' alarm
            // http://www.ietf.org/rfc/rfc2109.txt
            // note that the key names below are single quoted strings, and are not PHP variables
            unset($_COOKIE['$Version']);
            unset($_COOKIE['$Path']);
            unset($_COOKIE['$Domain']);

            foreach ($_COOKIE as $key => $val) {
                if (($cookie_key = $this->cleanInputKeys($key)) !== false) {
                    $_COOKIE[$cookie_key] = $this->cleanInputData($val);
                }
                else {
                    unset($_COOKIE[$key]);
                }
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

        // CSRF Protection check
        $this->enable_csrf && !$XY->isCli() && $XY->security->csrf_verify();

        $XY->logger->debug('Global POST, GET and COOKIE data sanitized');
    }

    /**
     * Clean Input Data
     *
     * Internal method that aids in escaping data and
     * standardizing newline characters to PHP_EOL.
     *
     * @param	mixed   $str    Input string or array of strings
     * @return  midex   Cleaned string or array
     */
    protected function cleanInputData($str)
    {
        global $XY;

        if (is_array($str)) {
            $new_array = array();
            foreach (array_keys($str) as $key) {
                $new_array[$this->cleanInputKeys($key)] = $this->cleanInputData($str[$key]);
            }
            return $new_array;
        }

        // We strip slashes if magic quotes is on to keep things consistent
        // NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
        // it will probably not exist in future versions at all.
        !$XY->isPhp('5.4') && get_magic_quotes_gpc() && $str = stripslashes($str);

        // Clean UTF-8 if supported
        $XY->utf8->enabled && $str = $XY->utf8->clean_string($str);

        // Remove control characters
        $str = $XY->output->removeInvisibleCharacters($str);

        // Should we filter the input data?
        $this->enable_xss && $str = $XY->security->xssClean($str);

        // Standardize newlines if needed
        $this->standardize_newlines && $str = preg_replace('/(?:\r\n|[\r\n])/', PHP_EOL, $str);

        return $str;
    }

    /**
     * Clean Keys
     *
     * Internal method that helps to prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @param   string  $str    Input string
     * @param   string  $fatal  Whether an invalid key terminates the app
     * @return  mixed   Cleaned keys on succes, otherwise FALSE
     */
    protected function cleanInputKeys($str, $fatal = true)
    {
        global $XY;

        if (!preg_match('/^[a-z0-9:_\/|-]+$/i', $str)) {
            if (!$fatal) {
                return false;
            }
            $XY->output->setStatusHeader(503);
            echo 'Disallowed Key Characters.';
            exit(EXIT_USER_INPUT);
        }

        // Clean UTF-8 if supported
        $XY->utf8->enabled && $str = $XY->utf8->clean_string($str);

        return $str;
    }

    /**
     * Request Headers
     *
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  array   Headers
     */
    public function requestHeaders($xss_clean = false)
    {
        // If headers is already defined, return it immediately
        if (!empty($this->headers)) {
            return $this->headers;
        }

        // In Apache, you can simply call apache_request_headers()
        if (function_exists('apache_request_headers')) {
            return $this->headers = apache_request_headers();
        }

        $this->headers['Content-Type'] = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] :
            @getenv('CONTENT_TYPE');

        foreach ($_SERVER as $key => $val) {
            if (sscanf($key, 'HTTP_%s', $header) === 1) {
                // take SOME_HEADER and turn it into Some-Header
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));
                $this->headers[$header] = $this->fetchFromArray($_SERVER, $key, $xss_clean);
            }
        }

        return $this->headers;
    }

    /**
     * Get Request Header
     *
     * Returns the value of a single member of the headers class member
     *
     * @param   string	$index      Header name
     * @param   bool	$xss_clean  Whether to apply XSS filtering
     * @return  mixed   The requested header on success or FALSE on failure
     */
    public function getRequestHeader($index, $xss_clean = false)
    {
        global $XY;
        empty($this->headers) && $this->request_headers();
        if (isset($this->headers[$index])) {
            return null;
        }
        return $xss_clean ? $XY->security->xssClean($this->headers[$index]) : $this->headers[$index];
    }

    /**
     * Is AJAX request?
     *
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     * @return  bool    TRUE if AJAX, otherwise FALSE
     */
    public function isAjaxRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Get Request Method
     *
     * Return the request method
     *
     * @param   bool    $upper  Whether to return in upper or lower case (default: FALSE)
     * @return  string  Method string
     */
    public function method($upper = false)
    {
        return $upper ? strtoupper($this->server('REQUEST_METHOD')) : strtolower($this->server('REQUEST_METHOD'));
    }
}

