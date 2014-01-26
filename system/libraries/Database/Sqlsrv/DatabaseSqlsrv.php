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
namespace Xylophone\libraries\Database\Sqlsrv;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLSRV Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Sqlsrv
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseSqlsrv extends \Xylophone\libraries\Database\DbBase
{
    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('NEWID()', 'RAND(%d)');

    /**
     * Quoted identifier flag
     *
     * Whether to use SQL-92 standard quoted identifier
     * (double quotes) or brackets for identifier escaping.
     *
     * @var bool
     */
    protected $quoted_identifier = true;

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        $charset = in_array(strtolower($this->char_set), array('utf-8', 'utf8'), true) ? 'UTF-8' : SQLSRV_ENC_CHAR;

        $connection = array(
            'UID' => empty($this->username) ? '' : $this->username,
            'PWD' => empty($this->password) ? '' : $this->password,
            'Database' => $this->database,
            'ConnectionPooling' => ($persistent === true) ? 1 : 0,
            'CharacterSet' => $charset,
            'Encrypt' => ($this->encrypt === true) ? 1 : 0,
            'ReturnDatesAsStrings' => 1
        );

        // If the username and password are both empty, assume this is a
        // 'Windows Authentication Mode' connection.
        if (empty($connection['UID']) && empty($connection['PWD'])) {
            unset($connection['UID'], $connection['PWD']);
        }

        $this->conn_id = sqlsrv_connect($this->hostname, $connection);

        // Determine how identifiers are escaped
        $query = $this->query('SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi');
        $query = $query->rowArray();
        $this->quoted_identifier = empty($query) ? false : (bool)$query['qi'];
        $this->escape_char = $this->quoted_identifier ? '"' : array('[', ']');

        return $this->conn_id;
    }

    /**
     * Select the database
     *
     * @param   string  $database   Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbSelect($database = '')
    {
        $database === '' && $database = $this->database;

        if ($this->dbExecute('USE '.$this->escapeIdentifiers($database))) {
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
        return ($this->isWriteType($sql) && stripos($sql, 'INSERT') === false) ? sqlsrv_query($this->conn_id, $sql) :
            sqlsrv_query($this->conn_id, $sql, null, array('Scrollable' => SQLSRV_CURSOR_STATIC));
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return sqlsrv_begin_transaction($this->conn_id);
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return sqlsrv_commit($this->conn_id);
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function trans_rollback()
    {
        return sqlsrv_rollback($this->conn_id);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return sqlsrv_rows_affected($this->result_id);
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
        $query = $this->query('SELECT @@IDENTITY AS insert_id');
        $query = $query->row();
        return $query->insert_id;
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        if (!$this->conn_id || ($info = sqlsrv_server_info($this->conn_id)) === false) {
            return false;
        }

        return $info['SQLServerVersion'];
    }

    /**
     * List table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param   bool    $prefix_limit   Whether to limit by database prefix
     * @return  string  Table listing
     */
    protected function dbListTables($prefix_limit = false)
    {
        $sql = 'SELECT '.$this->escapeIdentifiers('name').' FROM '.$this->escapeIdentifiers('sysobjects').
            ' WHERE '.$this->escapeIdentifiers('type')." = 'U'";

        if ($prefix_limit === true && $this->dbprefix !== '') {
            $sql .= ' AND '.$this->escapeIdentifiers('name').' LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
                sprintf($this->escape_like_str, $this->escape_like_chr);
        }

        return $sql.' ORDER BY '.$this->escapeIdentifiers('name');
    }

    /**
     * List database table fields
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param   string  $table  Table name
     * @return  string  Table column listing
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
            $retval[$i]->max_length = ($query[$i]->CHARACTER_MAXIMUM_LENGTH > 0) ? $query[$i]->CHARACTER_MAXIMUM_LENGTH : $query[$i]->NUMERIC_PRECISION;
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
        $error = array('code' => '00000', 'message' => '');
        $sqlsrv_errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);

        if (!is_array($sqlsrv_errors)) {
            return $error;
        }

        $sqlsrv_error = array_shift($sqlsrv_errors);
        if (isset($sqlsrv_error['SQLSTATE'])) {
            $error['code'] = isset($sqlsrv_error['code']) ? $sqlsrv_error['SQLSTATE'].'/'.$sqlsrv_error['code'] :
                $sqlsrv_error['SQLSTATE'];
        }
        elseif (isset($sqlsrv_error['code'])) {
            $error['code'] = $sqlsrv_error['code'];
        }

        if (isset($sqlsrv_error['message'])) {
            $error['message'] = $sqlsrv_error['message'];
        }

        return $error;
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
        // As of SQL Server 2012 (11.0.*) OFFSET is supported
        if (version_compare($this->version(), '11', '>=')) {
            return $sql.' OFFSET '.(int)$this->qb_offset.' ROWS FETCH NEXT '.$this->qb_limit.' ROWS ONLY';
        }

        $limit = $this->qb_offset + $this->qb_limit;

        // An ORDER BY clause is required for ROW_NUMBER() to work
        if ($this->qb_offset && ! empty($this->qb_orderby)) {
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
                $field_regexp = ($this->quoted_identifier) ? '("[^\"]+")' : '(\[[^\]]+\])';
                for ($i = 0, $c = count($this->qb_select); $i < $c; $i++) {
                    $select[] = preg_match('/(?:\s|\.)'.$field_regexp.'$/i', $this->qb_select[$i], $m) ? $m[1] :
                        $this->qb_select[$i];
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

        return $this->db->displayError('db_unsupported_feature');
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @sqlsrv_close($this->conn_id);
    }
}

