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
namespace Xylophone\libraries\Database\Mssql;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MS SQL Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Mssql
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseMssql extends \Xylophone\libraries\Database\DbBase
{
    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('NEWID()', 'RAND(%d)');

    /**
     * Whether to use SQL-92 standard quoted identifier
     * (double quotes) or brackets for identifier escaping.
     *
     * @var bool
     */
    protected $quoted_identifier = true;

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (!empty($this->port)) {
            $this->hostname .= (DIRECTORY_SEPARATOR === '\\' ? ',' : ':').$this->port;
        }
    }

    /**
     * Non-persistent database connection
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        global $XY;

        $this->conn_id = $persistent ?
            @mssql_pconnect($this->hostname, $this->username, $this->password) :
            @mssql_connect($this->hostname, $this->username, $this->password);

        if (!$this->conn_id) {
            return false;
        }

        // Select the DB... assuming a database name is specified in the config file
        if ($this->database !== '' && !$this->dbSelect()) {
            $XY->logger->error('Unable to select database: '.$this->database);
            return $this->displayError('db_unable_to_select', $this->database);
        }

        // Determine how identifiers are escaped
        $query = $this->query('SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi');
        $query = $query->rowArray();
        $this->quoted_identifier = empty($query) ? false : (bool)$query['qi'];
        $this->escape_char = ($this->quoted_identifier) ? '"' : array('[', ']');

        return $this->conn_id;
    }

    /**
     * Select database
     *
     * @param   string  $database   Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbSelect($database = '')
    {
        $database === '' && $database = $this->database;

        // Note: The brackets are required in the event that the DB name
        // contains reserved characters
        if (@mssql_select_db($this->escapeIdentifiers($database), $this->conn_id)) {
            $this->database = $database;
            return true;
        }

        return false;
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return @mssql_query($sql, $this->conn_id);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return $this->simpleQuery('BEGIN TRAN');
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return $this->simpleQuery('COMMIT TRAN');
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        return $this->simpleQuery('ROLLBACK TRAN');
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @mssql_rows_affected($this->conn_id);
    }

    /**
     * Insert ID
     *
     * Returns the last id created in the Identity column.
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        $query = version_compare($this->version(), '8', '>=') ? 'SELECT SCOPE_IDENTITY() AS last_id' :
            'SELECT @@IDENTITY AS last_id';

        $query = $this->query($query);
        $query = $query->row();
        return $query->last_id;
    }

    /**
     * Set client character set
     *
     * @param   string  $charset    Charset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    protected function dbSetCharset($charset)
    {
        return (@ini_set('mssql.charset', $charset) !== false);
    }

    /**
     * Version number query string
     *
     * @return  string  Database version query
     */
    protected function dbVersion()
    {
        return $this->query('SELECT @@VERSION AS ver')->row()->ver;
    }

    /**
     * Returns an array of table names
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param   string  $prefix_limit   Whether to limit by prefix
     * @return  array   Table names
     */
    protected function dbListTables($prefix_limit = false)
    {
        $sql = 'SELECT '.$this->escapeIdentifiers('name').
            ' FROM '.$this->escapeIdentifiers('sysobjects').
            ' WHERE '.$this->escapeIdentifiers('type')." = 'U'";

        if ($prefix_limit && $this->dbprefix !== '') {
            $sql .= ' AND '.$this->escapeIdentifiers('name')." LIKE '".$this->escapeLikeStr($this->dbprefix)."%' ".
                sprintf($this->like_escape_str, $this->like_escape_chr);
        }

        return $sql.' ORDER BY '.$this->escapeIdentifiers('name');
    }

    /**
     * List database table fields
     *
     * Generates a platform-specific query string so that the field names can be fetched
     *
     * @param   string  $table  Table name
     * @return  string  Table field listing
     */
    protected function dbListFields($table = '')
    {
        return 'SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.Columns
            WHERE UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));
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

        $sql = 'SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.Columns
            WHERE UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));

        if (($query = $this->query($sql)) === false) {
            return false;
        }
        $query = $query->resultObject();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->COLUMN_NAME;
            $retval[$i]->type = $query[$i]->DATA_TYPE;
            $retval[$i]->max_length = ($query[$i]->CHARACTER_MAXIMUM_LENGTH > 0) ?
                $query[$i]->CHARACTER_MAXIMUM_LENGTH : $query[$i]->NUMERIC_PRECISION;
            $retval[$i]->default = $query[$i]->COLUMN_DEFAULT;
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
        $query = $this->query('SELECT @@ERROR AS code');
        $query = $query->row();
        return array('code' => $query->code, 'message' => mssql_get_last_message());
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
        return 'TRUNCATE TABLE '.$table;
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
            return 'WITH ci_delete AS (SELECT TOP '.$this->qb_limit.' * FROM '.$table.
                $this->compileWhereHaving('qb_where').') DELETE FROM ci_delete';
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
        $limit = $this->qb_offset + $this->qb_limit;

        // As of SQL Server 2005 (9.0.*) ROW_NUMBER() is supported,
        // however an ORDER BY clause is required for it to work
        if (version_compare($this->version(), '9', '>=') && $this->qb_offset && ! empty($this->qb_orderby)) {
            $orderby = $this->compileOrderBy();

            // We have to strip the ORDER BY clause
            $sql = trim(substr($sql, 0, strrpos($sql, $orderby)));

            // Get the fields to select from our subquery, so that we can avoid
            // XY_rownum appearing in the actual results
            if (count($this->qb_select) === 0) {
                $select = '*'; // Inevitable
            }
            else {
                // Use only field names and their aliases, everything else is out of our scope.
                $select = array();
                $field_regexp = $this->quoted_identifier ? '("[^\"]+")' : '(\[[^\]]+\])';
                for ($i = 0, $c = count($this->qb_select); $i < $c; $i++) {
                    $select[] = preg_match('/(?:\s|\.)'.$field_regexp.'$/i', $this->qb_select[$i], $m)
                        ? $m[1] : $this->qb_select[$i];
                }
                $select = implode(', ', $select);
            }

            return 'SELECT '.$select." FROM (\n\n".
                preg_replace('/^(SELECT( DISTINCT)?)/i', '\\1 ROW_NUMBER() OVER('.trim($orderby).') AS '.
                $this->escapeIdentifiers('XY_rownum').', ', $sql)."\n\n) ".
                $this->escapeIdentifiers('XY_subquery')."\nWHERE ".
                $this->escapeIdentifiers('XY_rownum').' BETWEEN '.($this->qb_offset + 1).' AND '.$limit;
        }

        return preg_replace('/(^\SELECT (DISTINCT)?)/i','\\1 TOP '.$limit.' ', $sql);
    }

    /**
     * Insert batch statement
     *
     * Generates a platform-specific insert string from the supplied data.
     *
     * @param   string  $table  Table name
     * @param   array   $keys   Insert keys
     * @param   array   $values Insert values
     * @return  string  Query string
     */
    protected function dbInsert($table, $keys, $values)
    {
        // Multiple-value inserts are only supported as of SQL Server 2008
        if (version_compare($this->version(), '10', '>=')) {
            return parent::dbInsert($table, $keys, $values);
        }

        return $this->displayError('db_unsupported_feature');
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @mssql_close($this->conn_id);
    }
}

