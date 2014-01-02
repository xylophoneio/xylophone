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
 * Logger Class
 *
 * Follows the PSR-3 logger interface standard
 *
 * @see         http://www.php-fig.org/psr/psr-3/
 *
 * @package     Xylophone
 * @subpackage  core
 * @link        http://xylophone.io/user_guide/general/errors.html
 */
class Logger
{
    /** @var    string  Path to save log files */
    protected $path;

    /** @var    int     Logging threshold */
    protected $threshold = 1;

    /** @var    array   Array of specific levels to log */
    protected $enabled_levels = array();

    /** @var    string  Format of timestamp for log files */
    protected $date_fmt = 'Y-m-d H:i:s';

    /** @var    string  Filename extension */
    protected $file_ext;

    /** @var    bool    Whether or not the logger can write to the log files */
    protected $enabled = true;

    /** @var    array   Predefined logging levels */
    protected $levels = array(
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8,
        'all' => 9
    );

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        global $XY;

        // Get path and extension
        $this->path = $XY->config['log_path'];
        $this->path || $this->path = $XY->app_path.'logs/';
        $this->file_ext = $XY->config['logfile_extension'];
        $this->file_ext = $this->file_ext ? ltrim($this->file_ext, '.') : 'php';

        // Make path if no present
        file_exists($this->path) || mkdir($this->path, DIR_WRITE_MODE, true);

        // Disable if path not writable
        $this->enabled = (is_dir($this->path) && $XY->isWritable($this->path));

        // Set logging threshold
        $threshold = $XY->config['log_threshold'];
        if (is_numeric($threshold)) {
            // Set threshold number directly
            $this->threshold = (int)$threshold;
        }
        elseif ($threshold === 'none') {
            // Disable logging
            $this->threshold = 0;
        }
        elseif (is_string($threshold) && isset($this->levels[$threshold])) {
            // Convert level name to number
            $this->threshold = $this->levels[$threshold];
        }
        elseif (is_array($threshold)) {
            // Set level names as keys and disable threshold
            $this->enabled_levels = array_flip($threshold);
            $this->threshold = 0;
        }

        // Override format if set
        $format = $XY->config['log_date_format'];
        $format && $this->date_fmt = $format;
    }

    /**
     * Log a message
     *
     * @param   string  Error level name
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function log($level, $msg, $context = null)
    {
        // Check enabled
        $this->enabled || return;

        // Check level
        $level = strtolower($level);
        isset($this->levels[$level]) || return;

        // Check threshold
        $this->levels[$level] <= $this->threshold || isset($this->enabled_levels[$level]) || return;

        // Set file path, check if exists, and open
        $filepath = $this->path.'log-'.date('Y-m-d').'.'.$this->file_ext;
        $newfile = !file_exists($filepath);
        $fp = @fopen($filepath, FOPEN_WRITE_CREATE) || return;

        // Add protection to new php files and compose message
        $message = ($newfile && $this->file_ext === 'php') ?
            "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n" : '';
        $message .= strtoupper($level).($level === 'info' ? '  - ' : ' - ').date($this->date_fmt).' --> '.$msg."\n";

        // Lock, write, unlock, close, and chmod if new
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
        $newfile && @chmod($filepath, FILE_WRITE_MODE);
    }

    /**
     * Log a debug message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function debug($msg, $context = null)
    {
        return $this->log('debug', $msg, $context);
    }

    /**
     * Log an info message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function info($msg, $context = null)
    {
        return $this->log('info', $msg, $context);
    }

    /**
     * Log a notice message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function notice($msg, $context = null)
    {
        return $this->log('notice', $msg, $context);
    }

    /**
     * Log a warning message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function warning($msg, $context = null)
    {
        return $this->log('warning', $msg, $context);
    }

    /**
     * Log an error message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function error($msg, $context = null)
    {
        return $this->log('error', $msg, $context);
    }

    /**
     * Log a critical message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function critical($msg, $context = null)
    {
        return $this->log('critical', $msg, $context);
    }

    /**
     * Log an alert message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function alert($msg, $context = null)
    {
        return $this->log('alert', $msg, $context);
    }

    /**
     * Log an emergency message
     *
     * @param   string  Error message
     * @param   array   Error context
     * @return  void
     */
    public function emergency($msg, $context = null)
    {
        return $this->log('emergency', $msg, $context);
    }
}

