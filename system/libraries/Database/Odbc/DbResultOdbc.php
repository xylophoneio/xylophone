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
namespace Xylophone\libraries\Database\Odbc;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ODBC Database Result Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Odbc
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultOdbc extends \Xylophone\libraries\Database\DbResult
{
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
        elseif (($this->num_rows = @odbc_num_rows($this->result_id)) !== -1) {
            return $this->num_rows;
        }

        // Work-around for ODBC subdrivers that don't support num_rows()
        if (count($this->result_array) > 0) {
            return $this->num_rows = count($this->result_array);
        }
        elseif (count($this->result_object) > 0) {
            return $this->num_rows = count($this->result_object);
        }

        return $this->num_rows = count($this->resultArray());
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return @odbc_num_fields($this->result_id);
    }

    /**
     * Fetch Field Names
     *
     * Generates an array of column names
     *
     * @return  array   Field listing
     */
    public function listFields()
    {
        $field_names = array();
        $num_fields = $this->numFields();

        if ($num_fields > 0) {
            for ($i = 1; $i <= $num_fields; $i++) {
                $field_names[] = odbc_field_name($this->result_id, $i);
            }
        }

        return $field_names;
    }

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data
     *
     * @return  array   Field meta-data
     */
    public function fieldData()
    {
        $retval = array();
        for ($i = 0, $odbc_index = 1, $c = $this->numFields(); $i < $c; $i++, $odbc_index++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = odbc_field_name($this->result_id, $odbc_index);
            $retval[$i]->type = odbc_field_type($this->result_id, $odbc_index);
            $retval[$i]->max_length = odbc_field_len($this->result_id, $odbc_index);
            $retval[$i]->primary_key = 0;
            $retval[$i]->default = '';
        }

        return $retval;
    }

    /**
     * Free the result
     *
     * @return  void
     */
    public function freeResult()
    {
        if (is_resource($this->result_id)) {
            odbc_free_result($this->result_id);
            $this->result_id = false;
        }
    }

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     * Emulates the native odbc_fetch_array() function when
     * it is not available (odbc_fetch_array() requires unixODBC)
     *
     * @return  array   Result array
     */
    protected function fetchAssoc()
    {
        if (function_exists('odbc_fetch_array')) {
            return odbc_fetch_array($this->result_id);
        }

        $rownumber = 1;
        $rs = array();
        if (!odbc_fetch_into($this->result_id, $rs, $rownumber)) {
            return false;
        }

        $rs_assoc = array();
        foreach ($rs as $k => $v) {
            $field_name = odbc_field_name($this->result_id, $k+1);
            $rs_assoc[$field_name] = $v;
        }

        return $rs_assoc;
    }

    /**
     * Result - object
     *
     * Returns the result set as an object
     * Emulates the native odbc_fetch_object() function when
     * it is not available.
     *
     * @param   string  $class_name Result object class name
     * @return  object  Result object
     */
    protected function fetchObject($class_name = 'stdClass')
    {
        if (!function_exists('odbc_fetch_object')) {
            $row = odbc_fetch_object($this->result_id);

            if ($class_name === 'stdClass' or !$row) {
                return $row;
            }

            $class_name = new $class_name();
            foreach ($row as $key => $value) {
                $class_name->$key = $value;
            }

            return $class_name;
        }
        else {
            $rownumber = 1;
            $rs = array();
            if (!odbc_fetch_into($this->result_id, $rs, $rownumber)) {
                return false;
            }

            $row = new $class_name();
            foreach ($rs as $k => $v) {
                $field_name = odbc_field_name($this->result_id, $k+1);
                $row->$field_name = $v;
            }
        }
    }
}

