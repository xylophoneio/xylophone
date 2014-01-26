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
namespace Xylophone\libraries\Database\Sqlite3;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLite3 Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Sqlite3
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultSqlite3 extends \Xylophone\libraries\Database\DbResult
{
    /** @var    array   Data types */
    protected $data_types = array(
        SQLITE3_INTEGER => 'integer',
        SQLITE3_FLOAT => 'float',
        SQLITE3_TEXT => 'text',
        SQLITE3_BLOB => 'blob',
        SQLITE3_NULL => 'null'
    );

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return $this->result_id->numColumns();
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
        for ($i = 0, $c = $this->numFields(); $i < $c; $i++) {
            $field_names[] = $this->result_id->columnName($i);
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
        for ($i = 0, $c = $this->numFields(); $i < $this->numFields(); $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $this->result_id->columnName($i);
            $type = $this->result_id->columnType($i);
            $retval[$i]->type = isset($this->data_types[$type]) ? $this->data_types[$type] : $type;
            $retval[$i]->max_length = null;
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
            $this->result_id->finalize();
            $this->result_id = null;
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
        return $this->result_id->fetchArray(SQLITE3_ASSOC);
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
        // No native support for fetching rows as objects
        if (($row = $this->result_id->fetchArray(SQLITE3_ASSOC)) === false) {
            return false;
        }
        elseif ($class_name === 'stdClass') {
            return (object)$row;
        }

        $class_name = new $class_name();
        foreach (array_keys($row) as $key) {
            $class_name->$key = $row[$key];
        }

        return $class_name;
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
        // Only resetting to the start of the result set is supported
        return ($n > 0) ? false : $this->result_id->reset();
    }
}

