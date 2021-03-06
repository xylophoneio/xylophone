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
namespace Xylophone\libraries\Database\Pdo;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultPdo extends \Xylophone\libraries\Database\DbResult
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
        elseif (count($this->result_array) > 0) {
            return $this->num_rows = count($this->result_array);
        }
        elseif (count($this->result_object) > 0) {
            return $this->num_rows = count($this->result_object);
        }
        elseif (($num_rows = $this->result_id->rowCount()) > 0) {
            return $this->num_rows = $num_rows;
        }

        return $this->num_rows = count($this->result_array());
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        return $this->result_id->columnCount();
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
            $field_names[$i] = @$this->result_id->getColumnMeta();
            $field_names[$i] = $field_names[$i]['name'];
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
        try {
            $retval = array();

            for ($i = 0, $c = $this->numFields(); $i < $c; $i++) {
                $field = $this->result_id->getColumnMeta($i);

                $retval[$i] = new stdClass();
                $retval[$i]->name = $field['name'];
                $retval[$i]->type = $field['native_type'];
                $retval[$i]->max_length = ($field['len'] > 0) ? $field['len'] : null;
                $retval[$i]->primary_key = (int)(!empty($field['flags']) && in_array('primary_key', $field['flags'], true));
            }

            return $retval;
        } catch (Exception $e) {
            return $this->db->displayError('db_unsupported_feature');
        }
    }

    /**
     * Free the result
     *
     * @return  void
     */
    public function freeResult()
    {
        if (is_object($this->result_id)) {
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
        return $this->result_id->fetch(PDO::FETCH_ASSOC);
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
        return $this->result_id->fetchObject($class_name);
    }
}

