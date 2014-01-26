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
namespace Xylophone\libraries\Database\Postgre;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PostgresSQL Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Postgre
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePostgre extends \Xylophone\libraries\Database\DbBase
{
    /** @var    string  Database schema */
    public $schema = 'public';

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RANDOM()', 'RANDOM()');

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (!empty($this->dsn)) {
            return;
        }

        $this->dsn === '' || $this->dsn = '';

        // If UNIX sockets are used, we shouldn't set a port
        strpos($this->hostname, '/') === false || $this->port = '';
        $this->hostname === '' || $this->dsn = 'host='.$this->hostname.' ';
        !empty($this->port) && ctype_digit($this->port) && $this->dsn .= 'port='.$this->port.' ';
        if ($this->username !== '') {
            $this->dsn .= 'user='.$this->username.' ';

            // An empty password is valid!
            // $db['password'] = NULL must be done in order to ignore it.
            $this->password === null || $this->dsn .= "password='".$this->password."' ";
        }
        $this->database === '' || $this->dsn .= 'dbname='.$this->database.' ';

        // We don't have these options as elements in our standard configuration
        // array, but they might be set by parse_url() if the configuration was
        // provided via string. Example:
        // postgre://username:password@localhost:5432/database?connect_timeout=5&sslmode=1
        foreach (array('connect_timeout', 'options', 'sslmode', 'service') as $key) {
            isset($this->$key) && is_string($this->key) && $this->key !== '' &&
                $this->dsn .= $key.'=\''.$this->key.'\' ';
        }

        $this->dsn = rtrim($this->dsn);
    }

    /**
     * Database connection
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        if ($persistent === true && ($this->conn_id = @pg_pconnect($this->dsn))
        && pg_connection_status($this->conn_id) === PGSQL_CONNECTION_BAD && pg_ping($this->conn_id) === false) {
            return false;
        }
        else {
            $this->conn_id = @pg_connect($this->dsn);
        }

        $this->conn_id && !empty($this->schema) && $this->simple_query('SET search_path TO '.$this->schema.',public');

        return $this->conn_id;
    }

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     *
     * @return  void
     */
    public function reconnect()
    {
        pg_ping($this->conn_id) === false && $this->conn_id = false;
    }

    /**
     * Set client character set
     *
     * @param   string  $charset    Charset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    protected function dbSetCharset($charset)
    {
        return (pg_set_client_encoding($this->conn_id, $charset) === 0);
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        if (!$this->conn_id || ($pg_version = pg_version($this->conn_id)) === false) {
            return false;
        }

        // If PHP was compiled with PostgreSQL lib versions earlier than 7.4,
        // pg_version() won't return the server version and so we'll have to
        // fall back to running a query in order to get it.
        return isset($pg_version['server']) ? $pg_version['server'] : parent::dbVersion();
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return @pg_query($this->conn_id, $sql);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return (bool)@pg_query($this->conn_id, 'BEGIN');
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return (bool)@pg_query($this->conn_id, 'COMMIT');
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        return (bool)@pg_query($this->conn_id, 'ROLLBACK');
    }

    /**
     * Determines if a query is a "write" type.
     *
     * @param   string  $sql    Query string
     * @return  bool    TRUE if write query, otherwise FALSE
     */
    public function isWriteType($sql)
    {
        return (bool) preg_match('/^\s*"?(SET|INSERT(?![^\)]+\)\s+RETURNING)|UPDATE(?!.*\sRETURNING)|DELETE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s+/i', str_replace(array("\r\n", "\r", "\n"), ' ', $sql));
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        return pg_escape_string($this->conn_id, $str);
    }

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     *
     * @param   string  $str    String to escape
     * @return  mixed   Escaped string, boolean int, or 'NULL' string
     */
    public function escape($str)
    {
        global $XY;

        if ($XY->isPhp('5.4.4') && (is_string($str) || (is_object($str) && method_exists($str, '__toString')))) {
            return pg_escape_literal($this->conn_id, $str);
        }
        elseif (is_bool($str)) {
            return ($str) ? 'true' : 'false';
        }

        return parent::escape($str);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @pg_affected_rows($this->result_id);
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        $v = pg_version($this->conn_id);
        $v = isset($v['server']) ? $v['server'] : 0; // 'server' key is only available since PosgreSQL 7.4

        $table = (func_num_args() > 0) ? func_get_arg(0) : null;
        $column:= (func_num_args() > 1) ? func_get_arg(1) : null;

        if ($table === null && $v >= '8.1') {
            $sql = 'SELECT LASTVAL() AS ins_id';
        }
        elseif ($table !== null) {
            if ($column !== null && $v >= '8.0') {
                $sql = 'SELECT pg_get_serial_sequence(\''.$table.'\', \''.$column.'\') AS seq';
                $query = $this->query($sql);
                $query = $query->row();
                $seq = $query->seq;
            }
            else {
                // seq_name passed in table parameter
                $seq = $table;
            }

            $sql = 'SELECT CURRVAL(\''.$seq.'\') AS ins_id';
        }
        else {
            return pg_last_oid($this->result_id);
        }

        $query = $this->query($sql);
        $query = $query->row();
        return (int)$query->ins_id;
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
        $sql = 'SELECT "table_name" FROM "information_schema"."tables" WHERE "table_schema" = \''.$this->schema."'";

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql.' AND "table_name" LIKE \''.$this->escapeLikeStr($this->dbprefix)."%' ".
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
        return 'SELECT "column_name"
            FROM "information_schema"."columns"
            WHERE LOWER("table_name") = '.$this->escape(strtolower($table));
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

        $sql = 'SELECT "column_name", "data_type", "character_maximum_length", "numeric_precision", "column_default"
            FROM "information_schema"."columns"
            WHERE LOWER("table_name") = '.$this->escape(strtolower($table));

        if (($query = $this->query($sql)) === false) {
            return false;
        }
        $query = $query->resultObject();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->column_name;
            $retval[$i]->type = $query[$i]->data_type;
            $retval[$i]->max_length = ($query[$i]->character_maximum_length > 0) ?
                $query[$i]->character_maximum_length : $query[$i]->numeric_precision;
            $retval[$i]->default = $query[$i]->column_default;
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
        return array('code' => '', 'message' => pg_last_error($this->conn_id));
    }

    /**
     * ORDER BY
     *
     * @param   string  $orderby    Field name(s)
     * @param   string  $direction  Order direction: 'ASC', 'DESC' or 'RANDOM'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orderBy($orderby, $direction = '', $escape = NULL)
    {
        $direction = strtoupper(trim($direction));
        if ($direction === 'RANDOM') {
            if (!is_float($orderby) && ctype_digit((string) $orderby)) {
                $orderby = ($orderby > 1) ? (float)'0.'.$orderby : (float)$orderby;
            }

            if (is_float($orderby)) {
                $this->simpleQuery('SET SEED '.$orderby);
            }

            $orderby = $this->random_keyword[0];
            $direction = '';
            $escape = false;
        }

        return parent::orderBy($orderby, $direction, $escape);
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
     * Update_Batch statement
     *
     * Generates a platform-specific batch update string from the supplied data
     *
     * @param   string  $table  Table name
     * @param   array   $values SET values
     * @param   string  $index  WHERE key
     * @return  string  UPDATE string
     */
    protected function dbUpdateBatch($table, $values, $index)
    {
        $ids = array();
        foreach ($values as $key => $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                $field === $index || $final[$field][] = 'WHEN '.$val[$index].' THEN '.$val[$field];
            }
        }

        $cases = '';
        foreach ($final as $k => $v) {
            $cases .= $k.' = (CASE '.$index."\n".implode("\n", $v)."\n".'ELSE '.$k.' END), ';
        }

        $this->where($index.' IN('.implode(',', $ids).')', null, false);

        return 'UPDATE '.$table.' SET '.substr($cases, 0, -2).$this->compileWhereHaving('qb_where');
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
     * LIMIT
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param   string  $sql    Query string
     * @return  string  Query string with LIMIT clause
     */
    protected function dbLimit($sql)
    {
        return $sql.' LIMIT '.$this->qb_limit.($this->qb_offset ? ' OFFSET '.$this->qb_offset : '');
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @pg_close($this->conn_id);
    }
}

