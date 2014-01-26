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
namespace Xylophone\libraries\Database\Odbc;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ODBC Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Odbc
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseOdbc extends \Xylophone\libraries\Database\DbBase
{
    /** @var    string  Database schema */
    public $schema = 'public';

    /** @var    string  Identifier escape character (must be empty for ODBC) */
    protected $escape_char = '';

    /** @var    string  ESCAPE statement string */
    protected $like_escape_str = " {escape '%s'} ";

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RND()', 'RND(%d)');

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        // Legacy support for DSN in the hostname field
        empty($this->dsn) && $this->dsn = $this->hostname;
    }

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        return $persistent ? @odbc_pconnect($this->dsn, $this->username, $this->password) :
            @odbc_connect($this->dsn, $this->username, $this->password);
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return @odbc_exec($this->conn_id, $sql);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return odbc_autocommit($this->conn_id, false);
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        $ret = odbc_commit($this->conn_id);
        odbc_autocommit($this->conn_id, true);
        return $ret;
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        $ret = odbc_rollback($this->conn_id);
        odbc_autocommit($this->conn_id, true);
        return $ret;
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        global $XY;
        return $XY->output->removeInvisibleCharacters($str);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @odbc_num_rows($this->conn_id);
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        return $this->db->displayError('db_unsupported_feature');
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
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$this->schema."'";

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql." AND table_name LIKE '".$this->escapeLikeStr($this->dbprefix)."%' ".
                sprintf($this->_like_escape_str, $this->_like_escape_chr);
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
        return 'SHOW COLUMNS FROM '.$table;
    }

    /**
     * Field data query
     *
     * Generates a platform-specific query so that the column data can be retrieved
     *
     * @param   string  $table  Table name
     * @return  string  Query string
     */
    public function fieldData($table)
    {
        if ($table === '') {
            return $this->displayError('db_field_param_missing');
        }

        $query = $this->query('SELECT TOP 1 FROM '.$this->protectIdentifiers($table, true, null, false));
        return $query->fieldData();
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
        return array('code' => odbc_error($this->conn_id), 'message' => odbc_errormsg($this->conn_id));
    }

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param   string  $table  Table name
     * @param   array   $values Update data
     * @return  string  Query string
     */
    protected function dbUpdate($table, $values)
    {
        $this->qb_limit = false;
        $this->qb_orderby = array();
        return parent::dbUpdate($table, $values);
    }

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the TRUNCATE statement,
     * then this method maps to 'DELETE FROM table'
     *
     * @param   string  $table  Table name
     * @return  string  TRUNCATE string
     */
    protected function dbTruncate($table)
    {
        return 'DELETE FROM '.$table;
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
        $this->qb_limit = false;
        return parent::dbDelete($table);
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @odbc_close($this->conn_id);
    }
}

