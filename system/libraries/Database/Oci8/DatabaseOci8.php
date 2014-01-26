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
 * Oracle Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Oci8
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseOci8 extends \Xylophone\libraries\Database\DbBase
{
    /** @var    resource    Statement ID */
    public $stmt_id;

    /** @var    resource    Cursor ID */
    public $curs_id;

    /** @var    int     Commit mode flag */
    public $commit_mode = OCI_COMMIT_ON_SUCCESS;

    /** @var    bool    Limit used flag */
    public $limit_used;

    /** @var    array   List of reserved identifiers that must NOT be escaped */
    protected $reserved_identifiers = array('*', 'rownum');

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('ASC', 'ASC'); // not currently supported

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

        $valid_dsns = array(
            'tns' => '/^\(DESCRIPTION=(\(.+\)){2,}\)$/', // TNS
            // Easy Connect string (Oracle 10g+)
            'ec' => '/^(\/\/)?[a-z0-9.:_-]+(:[1-9][0-9]{0,4})?(\/[a-z0-9$_]+)?(:[^\/])?(\/[a-z0-9$_]+)?$/i',
            'in' => '/^[a-z0-9$_]+$/i' // Instance name (defined in tnsnames.ora)
        );

        // Space characters don't have any effect when actually
        // connecting, but can be a hassle while validating the DSN.
        $this->dsn = str_replace(array("\n", "\r", "\t", ' '), '', $this->dsn);

        if ($this->dsn !== '') {
            foreach ($valid_dsns as $regexp) {
                if (preg_match($regexp, $this->dsn)) {
                    return;
                }
            }
        }

        // Legacy support for TNS in the hostname configuration field
        $this->hostname = str_replace(array("\n", "\r", "\t", ' '), '', $this->hostname);
        if (preg_match($valid_dsns['tns'], $this->hostname)) {
            $this->dsn = $this->hostname;
            return;
        }
        elseif ($this->hostname !== '' && strpos($this->hostname, '/') === false &&
        strpos($this->hostname, ':') === false &&
        ((!empty($this->port) && ctype_digit($this->port)) || $this->database !== '')) {
            // If the hostname field isn't empty, doesn't contain ':' and/or '/'
            // and if port and/or database aren't empty, then the hostname field
            // is most likely indeed just a hostname. Therefore we'll try and
            // build an Easy Connect string from these 3 settings, assuming
            // that the database field is a service name.
            $this->dsn = $this->hostname.((!empty($this->port) && ctype_digit($this->port)) ? ':'.$this->port : '').
                ($this->database !== '' ? '/'.ltrim($this->database, '/') : '');

            if (preg_match($valid_dsns['ec'], $this->dsn)) {
                return;
            }
        }

        // At this point, we can only try and validate the hostname and
        // database fields separately as DSNs.
        if (preg_match($valid_dsns['ec'], $this->hostname) || preg_match($valid_dsns['in'], $this->hostname)) {
            $this->dsn = $this->hostname;
            return;
        }

        $this->database = str_replace(array("\n", "\r", "\t", ' '), '', $this->database);
        foreach ($valid_dsns as $regexp) {
            if (preg_match($regexp, $this->database)) {
                return;
            }
        }

        // An empty string should work as well. PHP will try to use environment
        // variables to determine which Oracle instance to connect to.
        $this->dsn = '';
    }

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        if ($persistent) {
            return empty($this->char_set) ? @oci_pconnect($this->username, $this->password, $this->dsn)
                : @oci_pconnect($this->username, $this->password, $this->dsn, $this->char_set);
        }
        else {
            return empty($this->char_set) ? @oci_connect($this->username, $this->password, $this->dsn) :
                @oci_connect($this->username, $this->password, $this->dsn, $this->char_set);
        }
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        if (!$this->conn_id || ($version = oci_server_version($this->conn_id)) === false) {
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
        // Oracle must parse the query before it is run. All of the actions with
        // the query are based on the statement id returned by oci_parse().
        $this->stmt_id = false;
        $this->setStmtId($sql);
        oci_set_prefetch($this->stmt_id, 1000);
        return @oci_execute($this->stmt_id, $this->commit_mode);
    }

    /**
     * Generate a statement ID
     *
     * @param   string  $sql    Query string
     * @return  void
     */
    protected function setStmtId($sql)
    {
        if (!is_resource($this->stmt_id)) {
            $this->stmt_id = oci_parse($this->conn_id, $sql);
        }
    }

    /**
     * Get cursor
     *
     * Returns a cursor from the database
     *
     * @return  resource    Cursor resource
     */
    public function getCursor()
    {
        return $this->curs_id = oci_new_cursor($this->conn_id);
    }

    /**
     * Stored Procedure
     *
     * Executes a stored procedure
     *
     * params array keys
     * KEY  OPTIONAL    NOTES
     * name     no  The name of the parameter should be in :<param_name> format
     * value    no  The value of the parameter.  If this is an OUT or IN OUT parameter,
     *                  this should be a reference to a variable
     * type     yes The type of the parameter
     * length   yes The max size of the parameter
     *
     * @param   string  $package    Package name in which the stored procedure is in
     * @param   string  $procedure  Stored procedure name to execute
     * @param   array   $params     Parameters
     * @return  mixed   Result object on results, TRUE on success, otherwise FALSE
     */
    public function storedProcedure($package, $procedure, $params)
    {
        global $XY;

        if ($package === '' or $procedure === '' || !is_array($params)) {
            $XY->logger->error('Invalid query: '.$package.'.'.$procedure);
            return $this->displayError('db_invalid_query');
        }

        // Build the query string
        $sql = 'BEGIN '.$package.'.'.$procedure.'(';

        $have_cursor = false;
        foreach ($params as $param) {
            $sql .= $param['name'].',';

            if (isset($param['type']) && $param['type'] === OCI_B_CURSOR) {
                $have_cursor = true;
            }
        }
        $sql = trim($sql, ',').'); END;';

        $this->stmt_id = false;
        $this->setStmtId($sql);
        $this->bindParams($params);
        return $this->query($sql, false, $have_cursor);
    }

    /**
     * Bind parameters
     *
     * @param   array   $params Parameters
     * @return  void
     */
    protected function bindParams($params)
    {
        if (!is_array($params) || !is_resource($this->stmt_id)) {
            return;
        }

        foreach ($params as $param) {
            foreach (array('name', 'value', 'type', 'length') as $val) {
                isset($param[$val]) || $param[$val] = '';
            }

            oci_bind_by_name($this->stmt_id, $param['name'], $param['value'], $param['length'], $param['type']);
        }
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        global $XY;
        $this->commit_mode = ($XY->isPhp('5.3.2')) ? OCI_NO_AUTO_COMMIT : OCI_DEFAULT;
        return true;
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        $this->commit_mode = OCI_COMMIT_ON_SUCCESS;
        return oci_commit($this->conn_id);
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        $this->commit_mode = OCI_COMMIT_ON_SUCCESS;
        return oci_rollback($this->conn_id);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @oci_num_rows($this->stmt_id);
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        // Not supported in oracle
        return $this->displayError('db_unsupported_function');
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

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql.' WHERE "TABLE_NAME" LIKE \''.$this->escapeLikeStr($this->dbprefix)."%' ".
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
     * @return  string  Table column listing
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
     * @return  object  Field data
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
            if ($length === null) {
                $length = $query[$i]->DATA_LENGTH;
            }
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
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occured.
     *
     * @return  array   Error information
     */
    public function error()
    {
        // oci_error() returns an array that already contains the
        // 'code' and 'message' keys, so we can just return it.
        if (is_resource($this->curs_id)) {
            return oci_error($this->curs_id);
        }
        elseif (is_resource($this->stmt_id)) {
            return oci_error($this->stmt_id);
        }
        elseif (is_resource($this->conn_id)) {
            return oci_error($this->conn_id);
        }

        return oci_error();
    }

    /**
     * Insert batch statement
     *
     * Generates a platform-specific insert string from the supplied data
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
        $this->limit_used = true;
        return 'SELECT * FROM (SELECT inner_query.*, rownum rnum FROM ('.$sql.') inner_query WHERE rownum < '.
            ($this->qb_offset + $this->qb_limit + 1).')'.
            ($this->qb_offset ? ' WHERE rnum >= '.($this->qb_offset + 1): '');
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @oci_close($this->conn_id);
    }
}

