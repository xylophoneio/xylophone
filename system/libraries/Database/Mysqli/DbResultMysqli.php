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
namespace Xylophone\libraries\Database\Mysqli;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MySQLi Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Mysqli
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultMysqli extends \Xylophone\libraries\Database\DbResult
{
    /**
     * Number of rows in the result set
     *
     * @return  int     Number of rows
     */
    public function numRows()
    {
        return is_int($this->num_rows) ? $this->num_rows : $this->num_rows = $this->result_id->num_rows;
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return $this->result_id->field_count;
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
        $this->result_id->field_seek(0);
        while ($field = $this->result_id->fetch_field()) {
            $field_names[] = $field->name;
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
        $field_data = $this->result_id->fetch_fields();
        for ($i = 0, $c = count($field_data); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $field_data[$i]->name;
            $retval[$i]->type = $field_data[$i]->type;
            $retval[$i]->max_length = $field_data[$i]->max_length;
            $retval[$i]->primary_key = (int) ($field_data[$i]->flags & 2);
            $retval[$i]->default = $field_data[$i]->def;
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
        if (is_object($this->result_id)) {
            $this->result_id->free();
            $this->result_id = false;
        }
    }

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset. We call
     * this internally before fetching results to make sure the
     * result set starts at zero.
     *
     * @param   int     $n  Seek offset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dataSeek($n = 0)
    {
        return $this->result_id->data_seek($n);
    }

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     *
     * @return  array   Result array
     */
    protected function fetchAssoc()
    {
        return $this->result_id->fetch_assoc();
    }

    /**
     * Result - object
     *
     * Returns the result set as an object
     *
     * @param   string  $class_name Result object class name
     * @return  object  Result object
     */
    protected function fetchObject($class_name = 'stdClass')
    {
        return $this->result_id->fetch_object($class_name);
    }
}

