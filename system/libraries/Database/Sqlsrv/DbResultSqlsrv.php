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
namespace Xylophone\libraries\Database\Sqlsrv;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLSRV Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Sqlsrv
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultSqlsrv extends \Xylophone\libraries\Database\DbResult
{
    /**
     * Number of rows in the result set
     *
     * @return  int     Number of rows
     */
    public function numRows()
    {
        return is_int($this->num_rows)
            ? $this->num_rows
            : $this->num_rows = @sqlsrv_num_rows($this->result_id);
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return @sqlsrv_num_fields($this->result_id);
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
        foreach (sqlsrv_field_metadata($this->result_id) as $offset => $field) {
            $field_names[] = $field['Name'];
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
        foreach (sqlsrv_field_metadata($this->result_id) as $i => $field) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $field['Name'];
            $retval[$i]->type = $field['Type'];
            $retval[$i]->max_length = $field['Size'];
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
            sqlsrv_free_stmt($this->result_id);
            $this->result_id = false;
        }
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
        return sqlsrv_fetch_array($this->result_id, SQLSRV_FETCH_ASSOC);
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
        return sqlsrv_fetch_object($this->result_id, $class_name);
    }
}

