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
namespace Xylophone\libraries\Database\Ibase;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Firebird/Interbase Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Ibase
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultIbase extends \Xylophone\libraries\Database\DbResult
{
    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return @ibase_num_fields($this->result_id);
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
        for ($i = 0, $num_fields = $this->numFields(); $i < $num_fields; $i++) {
            $info = ibase_field_info($this->result_id, $i);
            $field_names[] = $info['name'];
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
        for ($i = 0, $c = $this->numFields(); $i < $c; $i++) {
            $info = ibase_field_info($this->result_id, $i);

            $retval[$i] = new stdClass();
            $retval[$i]->name = $info['name'];
            $retval[$i]->type = $info['type'];
            $retval[$i]->max_length = $info['length'];
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
        @ibase_free_result($this->result_id);
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
        return @ibase_fetch_assoc($this->result_id, IBASE_FETCH_BLOBS);
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
        $row = @ibase_fetch_object($this->result_id, IBASE_FETCH_BLOBS);

        if ($class_name === 'stdClass' || !$row) {
            return $row;
        }

        $class_name = new $class_name();
        foreach ($row as $key => $value) {
            $class_name->$key = $value;
        }

        return $class_name;
    }
}

