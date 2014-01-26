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
namespace Xylophone\libraries\DbUtil\Mysql;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MySQL Database Utility Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Mysql
 * @link        http://xylophone.io/user_guide/database/
 */
class DbUtilMysql extends \Xylophone\libraries\DbUtil
{
    /** @var    string  List databases statement */
    protected $db_list_databases = 'SHOW DATABASES';

    /** @var    string  OPTIMIZE TABLE statement */
    protected $db_optimize_table = 'OPTIMIZE TABLE %s';

    /** @var    string  REPAIR TABLE statement */
    protected $db_repair_table = 'REPAIR TABLE %s';

    /**
     * Export
     *
     * @param   array   $params Parameters
     * @return  mixed   Backup file data on success, otherwise FALSE
     */
    protected function dbBackup($params)
    {
        if (count($params) === 0) {
            return false;
        }

        // Extract the prefs for simplicity
        extract($params);

        // Build the output
        $output = '';

        // Do we need to include a statement to disable foreign key checks?
        if ($foreign_key_checks === false) {
            $output .= 'SET foreign_key_checks = 0;'.$newline;
        }

        foreach ((array)$tables as $table) {
            // Is the table in the "ignore" list?
            if (in_array($table, (array) $ignore, true)) {
                continue;
            }

            // Get the table schema
            $query = $this->db->query('SHOW CREATE TABLE '.$this->db->escapeIdentifiers($this->db->database.'.'.$table));

            // No result means the table name was invalid
            if ($query === false) {
                continue;
            }

            // Write out the table schema
            $output .= '#'.$newline.'# TABLE STRUCTURE FOR: '.$table.$newline.'#'.$newline.$newline;

            if ($add_drop === true) {
                $output .= 'DROP TABLE IF EXISTS '.$this->db->protectIdentifiers($table).';'.$newline.$newline;
            }

            $i = 0;
            $result = $query->resultArray();
            foreach ($result[0] as $val) {
                if ($i++ % 2) {
                    $output .= $val.';'.$newline.$newline;
                }
            }

            // If inserts are not needed we're done...
            if ($add_insert === false) {
                continue;
            }

            // Grab all the data from the current table
            $query = $this->db->query('SELECT * FROM '.$this->db->protectIdentifiers($table));
            if ($query->numRows() === 0) {
                continue;
            }

            // Fetch the field names and determine if the field is an integer type.
            // We use this info to decide whether to surround the data with quotes or not
            $i = 0;
            $field_str = '';
            $is_int = array();
            while ($field = mysql_fetch_field($query->result_id)) {
                // Most versions of MySQL store timestamp as a string
                $is_int[$i] = in_array(strtolower(mysql_field_type($query->result_id, $i)),
                        array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'/*, 'timestamp'*/), true);

                // Create a string of field names
                $field_str .= $this->db->escapeIdentifiers($field->name).', ';
                $i++;
            }

            // Trim off the end comma
            $field_str = preg_replace('/, $/' , '', $field_str);

            // Build the insert string
            foreach ($query->resultArray() as $row) {
                $val_str = '';

                $i = 0;
                foreach ($row as $v) {
                    // Is the value NULL?
                    if ($v === null) {
                        $val_str .= 'NULL';
                    }
                    else {
                        // Escape the data if it's not an integer
                        $val_str .= ($is_int[$i] === false) ? $this->db->escape($v) : $v;
                    }

                    // Append a comma
                    $val_str .= ', ';
                    $i++;
                }

                // Remove the comma at the end of the string
                $val_str = preg_replace('/, $/' , '', $val_str);

                // Build the INSERT string
                $output .= 'INSERT INTO '.$this->db->protectIdentifiers($table).
                    ' ('.$field_str.') VALUES ('.$val_str.');'.$newline;
            }

            $output .= $newline.$newline;
        }

        // Do we need to include a statement to re-enable foreign key checks?
        if ($foreign_key_checks === false) {
            $output .= 'SET foreign_key_checks = 1;'.$newline;
        }

        return $output;
    }
}

