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
 * Security Class
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/libraries/security.html
 */
class Security
{
    /** @var    array   List of sanitize filename strings */
    public $filename_bad_chars = array(
        '../', '<!--', '-->', '<', '>',
        '\'', '"', '&', '$', '#',
        '{', '}', '[', ']', '=',
        ';', '?', '%20', '%22',
        '%3c',      // <
        '%253c',    // <
        '%3e',      // >
        '%0e',      // >
        '%28',      // (
        '%29',      // )
        '%2528',    // (
        '%26',      // &
        '%24',      // $
        '%3f',      // ?
        '%3b',      // ;
        '%3d'       // =
    );

    /** @var    string  Random Hash for protecting URLs  */
    protected $xss_hash = '';

    /** @var    string  Random hash for Cross Site Request Forgery protection cookie */
    protected $csrf_hash = '';

    /** @var    int     Cross Site Request Forgery cookie expiration - default two hours (in seconds) */
    protected $csrf_expire = 7200;

    /** @var    string  Cross Site Request Forgery cookie token name */
    protected $csrf_token_name = 'ci_csrf_token';

    /** @var    string  Cross Site Request Forgery cookie cookie name */
    protected $csrf_cookie_name = 'ci_csrf_token';

    /** @var    array   List of never allowed strings */
    protected $never_allowed_str = array(
        'document.cookie'   => '[removed]',
        'document.write'    => '[removed]',
        '.parentNode'       => '[removed]',
        '.innerHTML'        => '[removed]',
        'window.location'   => '[removed]',
        '-moz-binding'      => '[removed]',
        '<!--'              => '&lt;!--',
        '-->'               => '--&gt;',
        '<![CDATA['         => '&lt;![CDATA[',
        '<comment>'         => '&lt;comment&gt;'
    );

    /** @var    array   List of never allowed regex replacements */
    protected $never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:',             // IE, surprise!
        'Redirect\s+302',
        '(["\'])?data\s*:[^\1]*?base64[^\1]*?,[^\1]*?\1?'
    );

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // Is CSRF protection enabled?
        if ($XY->config['csrf_protection'] === true) {
            // CSRF config
            foreach (array('csrf_expire', 'csrf_token_name', 'csrf_cookie_name') as $key) {
                if (($val = config_item($key)) !== false) {
                    $this->$key = $val;
                }
            }

            // Append application specific cookie prefix
            ($prefix = $XY->config['cookie_prefix']) && $this->csrf_cookie_name = $prefix.$this->csrf_cookie_name;

            // Set the CSRF hash
            $this->csrfSetHash();
        }

        $XY->logger->debug('Security Class Initialized');
    }

    /**
     * CSRF Verify
     *
     * @return  object  $this
     */
    public function csrfVerify()
    {
        global $XY;

        // If it's not a POST request we will set the CSRF cookie
        strtoupper($_SERVER['REQUEST_METHOD']) === 'POST'|| return $this->csrfSetCookie();

        // Check if URI has been whitelisted from CSRF checks
        $exclude_uris = $XY->config['csrf_exclude_uris'];
        $exclude_uris && in_array($XY->uri->uriString(), $exclude_uris) && return $this;

        // Do the tokens exist in both the _POST and _COOKIE arrays and match?
        (isset($_POST[$this->csrf_token_name], $_COOKIE[$this->csrf_cookie_name]) &&
        $_POST[$this->csrf_token_name] === $_COOKIE[$this->csrf_cookie_name]) || $this->csrfShowError();

        // We kill this since we're done and we don't want to polute the _POST array
        unset($_POST[$this->csrf_token_name]);

        // Regenerate on every submission?
        if ($XY->config['csrf_regenerate']) {
            // Nothing should last forever
            unset($_COOKIE[$this->csrf_cookie_name]);
            $this->csrf_hash = '';
        }

        $this->csrfSetHash();
        $this->csrfSetCookie();

        $XY->logger->debug('CSRF token verified');
        return $this;
    }

    /**
     * CSRF Set Cookie
     *
     * @codeCoverageIgnore
     * @return  object  $this
     */
    public function csrfSetCookie()
    {
        global $XY;

        $expire = time() + $this->csrf_expire;
        $secure_cookie = (bool)$XY->config['cookie_secure'];
        $secure_cookie && !$XY->isHttps() && return false;

        setcookie($this->csrf_cookie_name, $this->csrf_hash, $expire, $XY->config['cookie_path'],
            $XY->config['cookie_domain'], $secure_cookie, $XY->config['cookie_httponly']);

        $XY->logger->debug('CRSF cookie Set');
        return $this;
    }

    /**
     * Show CSRF Error
     *
     * @return  void
     */
    public function csrfShowError()
    {
        global $XY;
        $XY->showError('The action you have requested is not allowed.');
    }

    /**
     * Get CSRF Hash
     *
     * @return  string  CSRF hash
     */
    public function getCsrfHash()
    {
        return $this->csrf_hash;
    }

    /**
     * Get CSRF Token Name
     *
     * @return  string  CSRF token name
     */
    public function getCsrfTokenName()
    {
        return $this->csrf_token_name;
    }

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented. This method does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts. Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: Should only be used to deal with data upon submission.
     *  It's not something that should be used for general
     *  runtime processing.
     *
     * @link    http://channel.bitflux.ch/wiki/XSS_Prevention
     *      Based in part on some code and ideas from Bitflux.
     *
     * @link    http://ha.ckers.org/xss.html
     *      To help develop this script I used this great list of
     *      vulnerabilities along with a few other hacks I've
     *      harvested from examining vulnerabilities in other programs.
     *
     * @param   mixed   Input data string or array
     * @param   bool    Whether the input is an image
     * @return  string
     */
    public function xssClean($str, $is_image = false)
    {
        global $XY;

        // Is the string an array?
        if (is_array($str)) {
            while (list($key) = each($str)) {
                $str[$key] = $this->xssClean($str[$key]);
            }

            return $str;
        }

        // Remove Invisible Characters and validate entities in URLs
        $str = $this->validateEntities($XY->output->removeInvisibleCharacters($str));

        // Decode just in case stuff like this is submitted:
        // <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
        // Use rawurldecode() so it does not remove plus signs
        $str = rawurldecode($str);

        // Convert character entities within tags to ASCII so our tests work
        // reliably. These are the ones that will pose security problems.
        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
        $str = preg_replace_callback('/<\w+.*/si', array($this, '_decode_entity'), $str);

        // Remove Invisible Characters Again!
        $str = $XY->output->removeInvisibleCharacters($str);

        // Convert all tabs to spaces to prevent strings like "ja	vascript".
        // We deal with spaces between characters later.
        $str = str_replace("\t", ' ', $str);

        // Capture converted string for later comparison
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->doNeverAllowed($str);

        // Make PHP tags safe, which will also convert XML tags
        if ($is_image) {
            // Images have PHP short opening and closing tags every so often so
            // we skip those and only do the long opening tags.
            $str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
        }
        else {
            $str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
        }

        // Compact any exploded words like "j a v a s c r i p t" which are
        // followed by a non-word character
        $words = array('javascript', 'expression', 'vbscript', 'script', 'base64',
            'applet', 'alert', 'document', 'write', 'cookie', 'window');
        $call = array($this, 'compactExplodedWords');
        foreach ($words as $word) {
            $word = implode('\s*', str_split($word)).'\s*';
            $str = preg_replace_callback('/('.substr($word, 0, -3).')(\W)/is', $call, $str);
        }

        // Remove disallowed Javascript in links or img tags
        $link_call = array($this, '_js_link_removal');
        $img_call = array($this, '_js_img_removal');
        do {
            $original = $str;
            preg_match('/<a/i', $str) && $str = preg_replace_callback('/<a\s+([^>]*?)(?:>|$)/si', $link_call, $str);
            preg_match('/<img/i', $str) && $str = preg_replace_callback('/<img\s+([^>]*?)(?:\s?\/?>|$)/si', $img_call, $str);
            preg_match('/script|xss/i', $str) && $str = preg_replace('/<\/*(?:script|xss).*?>/si', '[removed]', $str);
        } while ($original !== $str);
        unset($original);

        // Remove evil attributes such as style, onclick and xmlns
        $str = $this->removeEvilAttributes($str, $is_image);

        // Sanitize naughty HTML elements in the list by converting to entities
        // So <blink> becomes: &lt;blink&gt;
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('/<(\/*\s*)('.$naughty.')([^><]*)([><]*)/is',
            array($this, 'sanitizeNaughtyHtml'), $str);

        // Sanitize naughty scripting elements by converting parentheses to entities
        // So eval('some code'); becomes: eval&#40;'some code'&#41;
        $str = preg_replace('/(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)/si',
            '\\1\\2&#40;\\3&#41;', $str);

        // Final clean up in case something got through the above filters
        $str = $this->doNeverAllowed($str);

        // After all of the character conversion is done on images, return
        // whether any unwanted, likely XSS, code was found.
        $is_image && return ($str === $converted_string);

        $XY->logger->debug('XSS Filtering completed');
        return $str;
    }

    /**
     * XSS Hash
     *
     * Generates the XSS hash if needed and returns it.
     *
     * @return  string  XSS hash
     */
    public function xssHash()
    {
        $this->xss_hash !== '' || $this->xss_hash = md5(uniqid(mt_rand()));
        return $this->xss_hash;
    }

    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link    http://php.net/html-entity-decode
     *
     * @param   string  Input
     * @param   string  Character set
     * @return  string  Decoded string
     */
    public function entityDecode($str, $charset = null)
    {
        global $XY;

        strpos($str, '&') === FALSE && return $str;

        empty($charset) && $charset = $XY->config['charset'];

        do {
            $matches = $matches1 = 0;
            $str = preg_replace('/(&#x0*[0-9a-f]{2,5});?/iS', '$1;', $str, -1, $matches);
            $str = preg_replace('/(&#\d{2,4});?/S', '$1;', $str, -1, $matches1);
            $str = html_entity_decode($str, ENT_COMPAT, $charset);
        } while ($matches || $matches1);

        return $str;
    }

    /**
     * Sanitize Filename
     *
     * @param   string  Input file name
     * @param   bool    Whether to preserve paths
     * @return  string  Sanitized filename
     */
    public function sanitizeFilename($str, $relative_path = false)
    {
        global $XY;

        $bad = $this->filename_bad_chars;

        if (!$relative_path) {
            $bad[] = './';
            $bad[] = '/';
        }

        $str = $XY->output->removeInvisibleCharacters($str, false);

        do {
            $old = $str;
            $str = str_replace($bad, '', $str);
        } while ($old !== $str);

        return stripslashes($str);
    }

    /**
     * Strip Image Tags
     *
     * @param   string  Input string
     * @return  string  Stripped string
     */
    public function stripImageTags($str)
    {
        return preg_replace(array('/<img[\s\/]+.*?src\s*=\s*["\'](.+?)["\'].*?\>#', '#<img[\s\/]+.*?src\s*=\s*(.+?).*?\>/'), '\\1', $str);
    }

    /**
     * Compact Exploded Words
     *
     * Callback method for xssClean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Compact string
     */
    protected function compactExplodedWords($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

    /**
     * Remove Evil HTML Attributes (like event handlers and style)
     *
     * It removes the evil attribute and either:
     *
     *  - Everything up until a space. For example, everything between the pipes:
     *
     *  <code>
     *      <a |style=document.write('hello');alert('world');| class=link>
     *  </code>
     *
     *  - Everything inside the quotes. For example, everything between the pipes:
     *
     *  <code>
     *      <a |style="document.write('hello'); alert('world');"| class="link">
     *  </code>
     *
     * @used-by Security::xssClean()
     *
     * @param   string  The string to check
     * @param   bool    Whether the input is an image
     * @return  string  The string with the evil attributes removed
     */
    protected function removeEvilAttributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        $evil = array('on\w*', 'style', 'formaction');

        // Only remove xmlns if not an image, since Adobe Photoshop puts XML metadata into JFIF images
        $is_image || $evil[] = 'xmlns';

        $evil = implode('|', $evil_attributes);
        do {
            $count = 0;
            $attribs = array();

            // Find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all('/('.$evil.')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // Find occurrences of illegal attribute strings without quotes
            preg_match_all('/('.$evil.')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // Replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0) {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }
        } while ($count);

        return $str;
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback method for xssClean() to remove naughty HTML elements.
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Sanitized string
     */
    protected function sanitizeNaughtyHtml($matches)
    {
        // Encode opening brace and captured opening or closing brace to prevent recursive vectors
        return '&lt;'.$matches[1].$matches[2].$matches[3].
            str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
    }

    /**
     * JS Link Removal
     *
     * Callback method for xssClean() to sanitize links.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings.
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Cleaned string
     */
    protected function jsLinkRemoval($matches)
    {
        $replace = preg_replace('#href=.*?(?:alert\(|alert&\#40;|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
            '', $this->filterAttributes(str_replace(array('<', '>'), '', $matches[1])));
        return str_replace($matches[1], $replace, $matches[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback method for xssClean() to sanitize image tags.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings.
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Cleaned string
     */
    protected function jsImgRemoval($matches)
    {
        $filtered = $this->filterAttributes(str_replace(array('<', '>'), '', $matches[1]));
        $replace = preg_replace('#src=.*?(?:alert\(|alert&\#40;|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $filtered);
        return str_replace($matches[1], $replace, $matches[0]);
    }

    /**
     * Attribute Conversion
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Cleaned string
     */
    protected function convertAttribute($matches)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $matches[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety.
     *
     * @used-by Security::jsImgRemoval()
     * @used-by Security::jsLinkRemoval()
     *
     * @param   string  Input string
     * @return  string  Filtered string
     */
    protected function filterAttributes($str)
    {
        $out = '';
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }

        return $out;
    }

    /**
     * HTML Entity Decode Callback
     *
     * @used-by Security::xssClean()
     *
     * @param   array   Matches
     * @return  string  Cleaned string
     */
    protected function decodeEntity($matches)
    {
        global $XY;
        return $this->entityDecode($matches[0], strtoupper($XY->config['charset']));
    }

    /**
     * Validate URL entities
     *
     * @used-by Security::xssClean()
     *
     * @param   string  Input string
     * @return  string  Validated string
     */
    protected function validateEntities($str)
    {
        // Protect GET variables in URLs
        // 901119URL5918AMP18930PROTECT8198
        $str = preg_replace('/\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)/i', $this->xssHash().'\\1=\\2', $str);

        // Validate standard character entities by adding a semicolon if missing
        // to enable the conversion of entities to ASCII later
        $str = preg_replace('/(&\#?[0-9a-z]{2,})([\x00-\x20])*;?/i', '\\1;\\2', $str);

        // Validate UTF16 two byte encoding (x00) and add a semicolon if missing
        $str = preg_replace('/(&\#x?)([0-9A-F]+);?/i', '\\1\\2;', $str);

        // Un-Protect GET variables in URLs
        return str_replace($this->xssHash(), '&', $str);
    }

    /**
     * Do Never Allowed
     *
     * @used-by Security::xssClean()
     *
     * @param   string  Input string
     * @return  string  Filtered string
     */
    protected function doNeverAllowed($str)
    {
        $str = str_replace(array_keys($this->never_allowed_str), $this->never_allowed_str, $str);

        foreach ($this->never_allowed_regex as $regex) {
            $str = preg_replace('#'.$regex.'#is', '[removed]', $str);
        }

        return $str;
    }

    /**
     * Set CSRF Hash and Cookie
     *
     * @return  string  CSRF hash string
     */
    protected function csrfSetHash()
    {
        if ($this->csrf_hash === '') {
            // If the cookie exists we will use its value. We don't necessarily
            // want to regenerate it with each page load since a page could
            // contain embedded sub-pages causing this feature to fail
            if (isset($_COOKIE[$this->csrf_cookie_name]) &&
            preg_match('/^[0-9a-f]{32}$/iS', $_COOKIE[$this->csrf_cookie_name]) === 1) {
                return $this->csrf_hash = $_COOKIE[$this->csrf_cookie_name];
            }

            $this->csrf_hash = md5(uniqid(mt_rand(), true));
            $this->csrfSetCookie();
        }

        return $this->csrf_hash;
    }
}

