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
namespace Xylophone\libraries\Database\Mysql;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MySQL Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Mysql
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseMysql extends \Xylophone\libraries\Database\DbBase
{
    /** @var    bool    Compression flag */
    public $compress = false;

    /**
     * DELETE hack flag
     *
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var bool
     */
    public $delete_hack = true;

    /** @var    bool    Whether we're running in strict SQL mode */
    public $stricton = false;

    /** @var    string  Identifier escape character */
    protected $escape_char = '`';

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        empty($this->port) || $this->hostname .= ':'.$this->port;
    }

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        global $XY;

        $client_flags = ($this->compress === false) ? 0 : MYSQL_CLIENT_COMPRESS;

        $this->encrypt && $client_flags |= MYSQL_CLIENT_SSL;

        $this->conn_id = $persistent ?
            @mysql_pconnect($this->hostname, $this->username, $this->password, $client_flags) :
            @mysql_connect($this->hostname, $this->username, $this->password, TRUE, $client_flags);

        // Select the DB... assuming a database name is specified in the config file
        if ($this->database !== '' && !$this->dbSelect()) {
            $XY->logger->error('Unable to select database: '.$this->database);
            return $this->displayError('db_unable_to_select', $this->database);
        }

        if ($this->stricton && is_resource($this->conn_id)) {
            $this->simpleQuery('SET SESSION sql_mode="STRICT_ALL_TABLES"');
        }

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
        mysql_ping($this->conn_id) === false && $this->conn_id = false;
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

        if (@mysql_select_db($database, $this->conn_id)) {
            $this->database = $database;
            return true;
        }

        return false;
    }

    /**
     * Set client character set
     *
     * @param   string  $charset    Charset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    protected function dbSetCharset($charset)
    {
        return @mysql_set_charset($charset, $this->conn_id);
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        if (!$this->conn_id || ($version = @mysql_get_server_info($this->conn_id)) === false) {
            return false;
        }
        return $version;
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return @mysql_query($this->prepQuery($sql), $this->conn_id);
    }

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     *
     * @param   string  $sql    Query string
     * @return  string  Prepared query string
     */
    protected function prepQuery($sql)
    {
        // mysql_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ($this->delete_hack === true && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
            return trim($sql).' WHERE 1=1';
        }

        return $sql;
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        $this->simpleQuery('SET AUTOCOMMIT=0');
        return $this->simpleQuery('START TRANSACTION');
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        $this->simpleQuery('COMMIT');
        return $this->simpleQuery('SET AUTOCOMMIT=1');
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        $this->simpleQuery('ROLLBACK');
        return $this->simpleQuery('SET AUTOCOMMIT=1');
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        return is_resource($this->conn_id) ? mysql_real_escape_string($str, $this->conn_id) : addslashes($str);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @mysql_affected_rows($this->conn_id);
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        return @mysql_insert_id($this->conn_id);
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
        $sql = 'SHOW TABLES FROM '.$this->escapeIdentifiers($this->database);

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql." LIKE '".$this->escapeLikeStr($this->dbprefix)."%'";
        }

        return $sql;
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
        return 'SHOW COLUMNS FROM '.$this->protectIdentifiers($table, true, null, false);
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

        if (($query = $this->query('SHOW COLUMNS FROM '.$this->protectIdentifiers($table, true, null, false))) === false) {
            return false;
        }
        $query = $query->resultObject();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->Field;

            sscanf($query[$i]->Type, '%[a-z](%d)', $retval[$i]->type, $retval[$i]->max_length);

            $retval[$i]->default = $query[$i]->Default;
            $retval[$i]->primary_key = (int)($query[$i]->Key === 'PRI');
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
        return array('code' => mysql_errno($this->conn_id), 'message' => mysql_error($this->conn_id));
    }

    /**
     * FROM tables
     *
     * Groups tables in FROM clauses if needed, so there is no confusion
     * about operator precedence.
     *
     * @return  string  FROM clause
     */
    protected function dbFrom()
    {
        if (!empty($this->qb_join) && count($this->qb_from) > 1) {
            return '('.implode(', ', $this->qb_from).')';
        }

        return implode(', ', $this->qb_from);
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @mysql_close($this->conn_id);
    }
}

