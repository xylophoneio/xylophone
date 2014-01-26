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
namespace Xylophone\libraries\Database\Sqlite;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLite Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Sqlite
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseSqlite extends \Xylophone\libraries\Database\DbBase
{
    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RANDOM()', 'RANDOM()');

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        global $XY;

        $conn_id = $persistent ? @sqlite_popen($this->database, FILE_WRITE_MODE, $error) :
            @sqlite_open($this->database, FILE_WRITE_MODE, $error);

        if (!$conn_id) {
            $XY->logger->error($error);
            return $this->displayerror($error, '', true);
        }

        return $conn_id;
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        return sqlite_libversion();
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return $this->isWriteType($sql) ? @sqlite_exec($this->conn_id, $sql) : @sqlite_query($this->conn_id, $sql);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return $this->simpleQuery('BEGIN TRANSACTION');
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return $this->simpleQuery('COMMIT');
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        return $this->simpleQuery('ROLLBACK');
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        return sqlite_escape_string($str);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return sqlite_changes($this->conn_id);
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        return @sqlite_last_insert_rowid($this->conn_id);
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
        $sql = 'SELECT name FROM sqlite_master WHERE type=\'table\'';

        if ($prefix_limit !== false && $this->dbprefix != '') {
            return $sql.' AND \'name\' LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
                sprintf($this->like_escape_str, $this->_like_escape_chr);
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
        // Not supported
        return false;
    }

    /**
     * Returns an object with field data
     *
     * @param   string  $table  Table name
     * @return  object  Field data
     */
    public function fieldData($table = '')
    {
        if ($table === '') {
            return $this->displayError('db_field_param_missing');
        }

        if (($query = $this->query('PRAGMA TABLE_INFO('.$this->protectIdentifiers($table, true, null, false).')')) === false) {
            return false;
        }

        $query = $query->result_array();
        if (empty($query)) {
            return false;
        }

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]['name'];
            $retval[$i]->type = $query[$i]['type'];
            $retval[$i]->max_length = null;
            $retval[$i]->default = $query[$i]['dflt_value'];
            $retval[$i]->primary_key = isset($query[$i]['pk']) ? (int) $query[$i]['pk'] : 0;
        }

        return $retval;
    }

    /**
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occured.
     *
     * @return  array   Error information
     */
    public function error()
    {
        $error = array('code' => sqlite_last_error($this->conn_id));
        $error['message'] = sqlite_error_string($error['code']);
        return $error;
    }

    /**
     * Replace statement
     *
     * Generates a platform-specific replace string from the supplied data
     *
     * @param   string  $table  Table name
     * @param   array   $keys   Field names
     * @param   array   $values Values
     * @return  string  REPLACE string
     */
    protected function dbReplace($table, $keys, $values)
    {
        return 'INSERT OR '.parent::dbReplace($table, $keys, $values);
    }

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the TRUNCATE statement,
     * then this function maps to 'DELETE FROM table'
     *
     * @param   string  $table  Table name
     * @return  string  TRUNCATE string
     */
    protected function dbTruncate($table)
    {
        return 'DELETE FROM '.$table;
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @sqlite_close($this->conn_id);
    }
}

