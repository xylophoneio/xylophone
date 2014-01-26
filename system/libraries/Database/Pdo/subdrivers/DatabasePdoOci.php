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
namespace Xylophone\libraries\Database\Pdo\subdrivers;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Oracle Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoOci extends \Xylophone\libraries\Database\Pdo\DatabasePdo
{
    /** @var    array   List of reserved identifiers that must NOT be escaped */
    protected $reserved_identifiers = array('*', 'rownum');

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('ASC', 'ASC'); // Currently not supported

    /** @var    string  COUNT string */
    protected $count_string = 'SELECT COUNT(1) AS ';

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (empty($this->dsn)) {
            $this->dsn = 'oci:dbname=';

            // Oracle has a slightly different PDO DSN format (Easy Connect),
            // which also supports pre-defined DSNs.
            if (empty($this->hostname) && empty($this->port)) {
                $this->dsn .= $this->database;
            }
            else {
                $this->dsn .= '//'.(empty($this->hostname) ? '127.0.0.1' : $this->hostname).
                    (empty($this->port) ? '' : ':'.$this->port).'/';
                empty($this->database) || $this->dsn .= $this->database;
            }

            empty($this->char_set) || $this->dsn .= ';charset='.$this->char_set;
        }
        elseif (!empty($this->char_set) && strpos($this->dsn, 'charset=', 4) === false) {
            $this->dsn .= ';charset='.$this->char_set;
        }
    }

    /**
     * List database tables
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param   bool    $prefix_limit   Whether to limit by database prefix
     * @return  string  Table listing
     */
    protected function dbListTables($prefix_limit = false)
    {
        $sql = 'SELECT "TABLE_NAME" FROM "ALL_TABLES"';

        if ($prefix_limit === true && $this->dbprefix !== '') {
            return $sql.' WHERE "TABLE_NAME" LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
                sprintf($this->like_escape_str, $this->like_escape_chr);
        }

        return $sql;
    }

    /**
     * List database table fields
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param   string  $table  Table name
     * @return  string  Table field listing
     */
    protected function dbListFields($table = '')
    {
        if (strpos($table, '.') !== false) {
            sscanf($table, '%[^.].%s', $owner, $table);
        }
        else {
            $owner = $this->username;
        }

        return 'SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS
            WHERE UPPER(OWNER) = '.$this->escape(strtoupper($owner)).'
            AND UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));
    }

    /**
     * Returns an object with field data
     *
     * @param   string  $table  Table name
     * @return  string  Query string
     */
    public function fieldData($table = '')
    {
        if ($table === '') {
            return $this->displayError('db_field_param_missing');
        }
        elseif (strpos($table, '.') !== false) {
            sscanf($table, '%[^.].%s', $owner, $table);
        }
        else {
            $owner = $this->username;
        }

        $sql = 'SELECT COLUMN_NAME, DATA_TYPE, CHAR_LENGTH, DATA_PRECISION, DATA_LENGTH, DATA_DEFAULT, NULLABLE
            FROM ALL_TAB_COLUMNS
            WHERE UPPER(OWNER) = '.$this->escape(strtoupper($owner)).'
            AND UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));

        if (($query = $this->query($sql)) === false) {
            return false;
        }
        $query = $query->resultObject();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->COLUMN_NAME;
            $retval[$i]->type = $query[$i]->DATA_TYPE;
            $length = ($query[$i]->CHAR_LENGTH > 0) ? $query[$i]->CHAR_LENGTH : $query[$i]->DATA_PRECISION;
            $length === NULL && $length = $query[$i]->DATA_LENGTH;
            $retval[$i]->max_length = $length;
            $default = $query[$i]->DATA_DEFAULT;
            if ($default === null && $query[$i]->NULLABLE === 'N') {
                $default = '';
            }
            $retval[$i]->default = $query[$i]->COLUMN_DEFAULT;
        }

        return $retval;
    }

    /**
     * Insert batch statement
     *
     * @param   string  $table  Table name
     * @param   array   $keys   Insert keys
     * @param   array   $values Insert values
     * @return  string  Query string
     */
    protected function dbInsert($table, $keys, $values)
    {
        $keys = implode(', ', $keys);
        $sql = "INSERT ALL\n";

        for ($i = 0, $c = count($values); $i < $c; $i++) {
            $sql .= ' INTO '.$table.' ('.$keys.') VALUES '.$values[$i]."\n";
        }

        return $sql.'SELECT * FROM dual';
    }

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param   string  $table  Table name
     * @return  string  DELETE string
     */
    protected function dbDelete($table)
    {
        if ($this->qb_limit) {
            $this->where('rownum <= ',$this->qb_limit, false);
            $this->qb_limit = false;
        }
        return parent::dbDelete($table);
    }

    /**
     * LIMIT
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param   string  $sql    Query string
     * @return  string  Query string with LIMIT clause
     */
    protected function dbLimit($sql)
    {
        return 'SELECT * FROM (SELECT inner_query.*, rownum rnum FROM ('.$sql.') inner_query WHERE rownum < '.
            ($this->qb_offset + $this->qb_limit + 1).')'.
            ($this->qb_offset ? ' WHERE rnum >= '.($this->qb_offset + 1): '');
    }
}

