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
namespace Xylophone\libraries\Database\Oci8;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oracle Database Result Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Oci8
 * @link        http://xylophone.io/user_guide/database/
 */
class DbResultOci8 extends \Xylophone\libraries\Database\DbResult
{
    /** @var    resource    Statement ID */
    public $stmt_id;

    /** @var    resource    Cursor ID */
    public $curs_id;

    /** @var    bool    Limit used flag */
    public $limit_used;

    /** @var    int     Commit mode flag */
    public $commit_mode;

    /**
     * Constructor
     *
     * @param   object  $db     Database object
     * @return  void
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->stmt_id = $driver_object->stmt_id;
        $this->curs_id = $driver_object->curs_id;
        $this->limit_used = $driver_object->limit_used;
        $this->commit_mode =& $driver_object->commit_mode;
        $driver_object->stmt_id = false;
    }

    /**
     * Number of fields in the result set
     *
     * @return  int     Number of fields
     */
    public function numFields()
    {
        $count = @oci_num_fields($this->stmt_id);

        // if we used a limit we subtract it
        return $this->limit_used ? $count - 1 : $count;
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
        for ($c = 1, $fieldCount = $this->numFields(); $c <= $fieldCount; $c++) {
            $field_names[] = oci_field_name($this->stmt_id, $c);
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
        for ($c = 1, $fieldCount = $this->numFields(); $c <= $fieldCount; $c++) {
            $F = new stdClass();
            $F->name = oci_field_name($this->stmt_id, $c);
            $F->type = oci_field_type($this->stmt_id, $c);
            $F->max_length = oci_field_size($this->stmt_id, $c);
            $retval[] = $F;
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
            oci_free_statement($this->result_id);
            $this->result_id = false;
        }

        if (is_resource($this->stmt_id)) {
            oci_free_statement($this->stmt_id);
        }

        if (is_resource($this->curs_id)) {
            oci_cancel($this->curs_id);
            $this->curs_id = null;
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
        $id = $this->curs_id ? $this->curs_id : $this->stmt_id;
        return @oci_fetch_assoc($id);
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
        $row = $this->curs_id ? oci_fetch_object($this->curs_id) : oci_fetch_object($this->stmt_id);

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

