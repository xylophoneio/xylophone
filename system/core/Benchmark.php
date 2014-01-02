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
 * bundled with this package in the files license.txt / license.rst.  It is
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
 * Benchmark Class
 *
 * This class enables you to mark points and calculate the time difference
 * between them. Memory consumption can also be displayed.
 *
 * @package     Xylophone
 * @subpackage  core
 * @author      Xylophone Dev Team
 * @link        http://xylophone.io/user_guide/libraries/benchmark.html
 */
class Benchmark
{
    /** @var    array   List of all benchmark markers */
    public $marker = array();

    /**
     * Set a benchmark marker
     *
     * Multiple calls to this function can be made so that several
     * execution points can be timed.
     *
     * @param   string  Marker name
     * @return  void
     */
    public function mark($name)
    {
        $this->marker[$name] = microtime(true);
    }

    /**
     * Elapsed time
     *
     * Calculates the time difference between two marked points.
     *
     * If the first parameter is empty this function instead returns the
     * {elapsed_time} pseudo-variable. This permits the full system
     * execution time to be shown in a template. The output class will
     * swap the real value for this variable.
     *
     * @param   string  A particular marked point
     * @param   string  A particular marked point
     * @param   int     Number of decimal places
     * @return  string  Calculated elapsed time on success, an '{elapsed_string}'
     *                  if $point1 is empty or an empty string if $point1 is not found.
     */
    public function elapsedTime($point1 = '', $point2 = '', $decimals = 4)
    {
        ($point1 !== '') || return '{elapsed_time}';
        isset($this->marker[$point1]) || return '';
        isset($this->marker[$point2]) || $this->marker[$point2] = microtime(true);
        return number_format($this->marker[$point2] - $this->marker[$point1], $decimals);
    }

    /**
     * Memory Usage
     *
     * Simply returns the {memory_usage} marker.
     *
     * This permits it to be put it anywhere in a template
     * without the memory being calculated until the end.
     * The output class will swap the real value for this variable.
     *
     * @return  string  '{memory_usage}'
     */
    public function memoryUsage()
    {
        return '{memory_usage}';
    }
}

