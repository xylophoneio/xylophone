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
namespace Xylophone\libraries\Database;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Result Class
 *
 * This is the platform-independent result class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResult
{
    /** @var    resource|object     Connection ID */
    public $conn_id = null;

    /** @var    resource|object     Result ID */
    public $result_id = null;

    /** @var    array   Result Array */
    public $result_array = array();

    /** @var    array   Result Object */
    public $result_object = array();

    /** @var    array   Custom Result Object */
    public $custom_result_object = array();

    /** @var    int     Current Row index */
    public $current_row = 0;

    /** @var    int     Number of rows */
    public $num_rows;

    /** @var    array   Row data */
    public $row_data;

    /**
     * Constructor
     *
     * @param   object  $db     Database object
     * @return  void
     */
    public function __construct($db)
    {
        if ($db) {
            $this->conn_id = $db->conn_id;
            $this->result_id = $db->result_id;
        }
    }

    /**
     * Copy a result
     *
     * Create a generic copy of the result object without the platform
     * specific driver so we can cache it, since the query result resource
     * ID won't be any good after reading the cache.
     *
     * @param   object  Result object to copy
     * @return  void
     */
    public function copy($result)
    {
        $this->conn_id = null;
        $this->result_id = null;
        $this->result_object = $result->resultObject();
        $this->result_array = $result->resultArray();
        $this->num_rows = $result->numRows();
    }

    /**
     * Number of rows in the result set
     *
     * @return  int     Number of rows
     */
    public function numRows()
    {
        if (is_int($this->num_rows)) {
            return $this->num_rows;
        }
        elseif (count($this->result_array) > 0) {
            return $this->num_rows = count($this->result_array);
        }
        elseif (count($this->result_object) > 0) {
            return $this->num_rows = count($this->result_object);
        }

        return $this->num_rows = count($this->result_array());
    }

    /**
     * Query result
     *
     * Acts as a wrapper function for the following functions.
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result object or array
     */
    public function result($type = 'object')
    {
        if ($type === 'array') {
            return $this->resultArray();
        }
        elseif ($type === 'object') {
            return $this->resultObject();
        }
        else {
            return $this->customResultObject($type);
        }
    }

    /**
     * Custom query result
     *
     * @param   string  $class_name Custom result class name
     * @return  array   Array of custom result objects
     */
    public function customResultObject($class_name)
    {
        if (isset($this->custom_result_object[$class_name])) {
            return $this->custom_result_object[$class_name];
        }
        elseif (!$this->result_id || $this->num_rows === 0) {
            return array();
        }

        // Don't fetch the result set again if we already have it
        $_data = NULL;
        if (($c = count($this->result_array)) > 0) {
            $_data = 'result_array';
        }
        elseif (($c = count($this->result_object)) > 0) {
            $_data = 'result_object';
        }

        if ($_data !== null) {
            for ($i = 0; $i < $c; $i++) {
                $this->custom_result_object[$class_name][$i] = new $class_name();

                foreach ($this->{$_data}[$i] as $key => $value) {
                    $this->custom_result_object[$class_name][$i]->$key = $value;
                }
            }

            return $this->custom_result_object[$class_name];
        }

        $this->data_seek(0);
        $this->custom_result_object[$class_name] = array();

        while ($row = $this->fetchObject($class_name)) {
            $this->custom_result_object[$class_name][] = $row;
        }

        return $this->custom_result_object[$class_name];
    }

    /**
     * Query result object
     *
     * @return  array   Array of result objects
     */
    public function resultObject()
    {
        if (count($this->result_object) > 0) {
            return $this->result_object;
        }

        // In the event that query caching is on, the result_id variable
        // will not be a valid resource so we'll simply return an empty
        // array.
        if (!$this->result_id || $this->num_rows === 0) {
            return array();
        }

        if (($c = count($this->result_array)) > 0) {
            for ($i = 0; $i < $c; $i++) {
                $this->result_object[$i] = (object)$this->result_array[$i];
            }

            return $this->result_object;
        }

        $this->data_seek(0);
        while ($row = $this->fetchObject()) {
            $this->result_object[] = $row;
        }

        return $this->result_object;
    }

    /**
     * Query result array
     *
     * @return  array   Array of result arrays
     */
    public function resultArray()
    {
        if (count($this->result_array) > 0) {
            return $this->result_array;
        }

        // In the event that query caching is on, the result_id variable
        // will not be a valid resource so we'll simply return an empty
        // array.
        if (!$this->result_id || $this->num_rows === 0) {
            return array();
        }

        if (($c = count($this->result_object)) > 0) {
            for ($i = 0; $i < $c; $i++) {
                $this->result_array[$i] = (array) $this->result_object[$i];
            }

            return $this->result_array;
        }

        $this->data_seek(0);
        while ($row = $this->fetchAssoc()) {
            $this->result_array[] = $row;
        }

        return $this->result_array;
    }

    /**
     * Fetch row data
     *
     * @param   mixed   $index  Row index
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Row object or array or NULL
     */
    public function row($index = 0, $type = 'object')
    {
        if (!is_numeric($index)) {
            // We cache the row data for subsequent uses
            is_array($this->row_data) || $this->row_data = $this->row_array(0);

            // array_key_exists() instead of isset() to allow for NULL values
            if (empty($this->row_data) || !array_key_exists($index, $this->row_data)) {
                return null;
            }

            return $this->row_data[$index];
        }

        if ($type === 'object') return $this->rowObject($index);
        elseif ($type === 'array') return $this->rowArray($index);
        else return $this->customRowObject($index, $type);
    }

    /**
     * Assign an item into a particular column slot
     *
     * @param   mixed   $key    Item key
     * @param   mixed   $value  Item value
     * @return  void
     */
    public function setRow($key, $value = null)
    {
        // We cache the row data for subsequent uses
        if (!is_array($this->row_data)) {
            $this->row_data = $this->row_array(0);
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->row_data[$k] = $v;
            }
            return;
        }

        if ($key !== '' && $value !== null) {
            $this->row_data[$key] = $value;
        }
    }

    /**
     * Fetch custom result row object
     *
     * @param   int     $index  Row index
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  object  Custom result row object
     */
    public function customRowObject($index, $type)
    {
        isset($this->custom_result_object[$type]) || $this->customResultObject($type);

        if (count($this->custom_result_object[$type]) === 0) {
            return null;
        }

        if ($index !== $this->current_row && isset($this->custom_result_object[$type][$index])) {
            $this->current_row = $index;
        }

        return $this->custom_result_object[$type][$this->current_row];
    }

    /**
     * Fetch result row object
     *
     * @param   int     $index  Row index
     * @return  object  Result row object
     */
    public function rowObject($index = 0)
    {
        $result = $this->resultObject();
        if (count($result) === 0) {
            return null;
        }

        if ($index !== $this->current_row && isset($result[$index])) {
            $this->current_row = $index;
        }

        return $result[$this->current_row];
    }

    /**
     * Fetch result row array
     *
     * @param   int     $index  Row index
     * @return  array   Result row array
     */
    public function rowArray($index = 0)
    {
        $result = $this->resultArray();
        if (count($result) === 0) {
            return null;
        }

        if ($index !== $this->current_row && isset($result[$index])) {
            $this->current_row = $index;
        }

        return $result[$this->current_row];
    }

    /**
     * Return the "first" row
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result row object or array or NULL
     */
    public function firstRow($type = 'object')
    {
        $result = $this->result($type);
        return (count($result) === 0) ? null : $result[0];
    }

    /**
     * Returns the "last" row
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result row object or array or NULL
     */
    public function lastRow($type = 'object')
    {
        $result = $this->result($type);
        return (count($result) === 0) ? null : $result[count($result) - 1];
    }

    /**
     * Returns the "next" row
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result row object or array or NULL
     */
    public function nextRow($type = 'object')
    {
        $result = $this->result($type);
        if (count($result) === 0) {
            return null;
        }

        return isset($result[$this->current_row + 1]) ? $result[++$this->current_row] : null;
    }

    /**
     * Returns the "previous" row
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result row object or array or NULL
     */
    public function previousRow($type = 'object')
    {
        $result = $this->result($type);
        if (count($result) === 0) {
            return null;
        }

        if (isset($result[$this->current_row - 1])) {
            --$this->current_row;
        }
        return $result[$this->current_row];
    }

    /**
     * Returns an unbuffered row and move pointer to next row
     *
     * @param   string  $type   'object', 'array' or a custom class name
     * @return  mixed   Result row object or array or NULL
     */
    public function unbufferedRow($type = 'object')
    {
        if ($type === 'array') {
            return $this->fetchAssoc();
        }
        elseif ($type === 'object') {
            return $this->fetchObject();
        }

        return $this->fetchObject($type);
    }

    /**
     * The following methods are normally overloaded by the identically named
     * methods in the platform-specific driver -- except when query caching
     * is used. When caching is enabled we do not load the other driver.
     * These functions are primarily here to prevent undefined function errors
     * when a cached result object is in use. They are not otherwise fully
     * operational due to the unavailability of the database resource IDs with
     * cached results.
     */

    /**
     * Number of fields in the result set
     *
     * Overriden by result driver classes.
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return 0;
    }

    /**
     * Fetch Field Names
     *
     * Generates an array of column names.
     * Overriden by result driver classes.
     *
     * @return  array   Field listing
     */
    public function listFields()
    {
        return array();
    }

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data.
     * Overriden by driver result classes.
     *
     * @return  array   Field meta-data
     */
    public function fieldData()
    {
        return array();
    }

    /**
     * Free the result
     *
     * Overriden by driver result classes.
     *
     * @return  void
     */
    public function freeResult()
    {
        $this->result_id = false;
    }

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset. We call
     * this internally before fetching results to make sure the
     * result set starts at zero.
     *
     * Overriden by driver result classes.
     *
     * @param   int     $n  Seek offset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dataSeek($n = 0)
    {
        return false;
    }

    /**
     * Result - associative array
     *
     * Returns the result set as an array.
     * Overriden by driver result classes.
     *
     * @return  array   Result array
     */
    protected function fetchAssoc()
    {
        return array();
    }

    /**
     * Result - object
     *
     * Returns the result set as an object.
     *
     * Overriden by driver result classes.
     *
     * @param   string  $class_name Result object class name
     * @return  object  Result object
     */
    protected function fetchObject($class_name = 'stdClass')
    {
        return array();
    }
}

