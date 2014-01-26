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
namespace Xylophone\libraries\Database\Mysql;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MySQL Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Mysql
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultMysql extends \Xylophone\libraries\Database\DbResult
{
    /**
     * Constructor
     *
     * @param   object  $db     Database object
     * @return  void
     */
    public function __construct($db)
    {
        parent::__construct($db);

        // Required, due to mysql_data_seek() causing nightmares
        // with empty result sets
        $this->num_rows = @mysql_num_rows($this->result_id);
    }

    /**
     * Number of rows in the result set
     *
     * @return  int     Number of rows
     */
    public function numRows()
    {
        return $this->num_rows;
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return @mysql_num_fields($this->result_id);
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
        mysql_field_seek($this->result_id, 0);
        while ($field = mysql_fetch_field($this->result_id)) {
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
        for ($i = 0, $c = $this->numfields(); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = mysql_field_name($this->result_id, $i);
            $retval[$i]->type = mysql_field_type($this->result_id, $i);
            $retval[$i]->max_length = mysql_field_len($this->result_id, $i);
            $retval[$i]->primary_key = (int)(strpos(mysql_field_flags($this->result_id, $i), 'primary_key') !== false);
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
            mysql_free_result($this->result_id);
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
        return $this->num_rows ? @mysql_data_seek($this->result_id, $n) : false;
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
        return mysql_fetch_assoc($this->result_id);
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
        return mysql_fetch_object($this->result_id, $class_name);
    }
}

