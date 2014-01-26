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
namespace Xylophone\libraries\Database\Cubrid;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CUBRID Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Cubrid
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseCubrid extends \Xylophone\libraries\Database\DbBase
{
    /** @var    bool    Auto-commit flag */
    public $auto_commit = true;

    /** @var    string  Identifier escape character */
    protected $escape_char = '`';

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RANDOM()', 'RANDOM(%d)');

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (preg_match('/^CUBRID:[^:]+(:[0-9][1-9]{0,4})?:[^:]+:[^:]*:[^:]*:(\?.+)?$/', $this->dsn, $matches)) {
            if (stripos($matches[2], 'autocommit=off') !== false) {
                $this->auto_commit = false;
            }
        }
        else {
            // If no port is defined by the user, use the default value
            empty($this->port) || $this->port = 33000;
        }
    }

    /**
     * Database connection
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        if (preg_match('/^CUBRID:[^:]+(:[0-9][1-9]{0,4})?:[^:]+:([^:]*):([^:]*):(\?.+)?$/', $this->dsn, $matches)) {
            $_temp = $persistent ? 'cubrid_pconnect_with_url' : 'cubrid_connect_with_url';
            $conn_id = ($matches[2] === '' && $matches[3] === '' && $this->username !== '' && $this->password !== '')
                ? $_temp($this->dsn, $this->username, $this->password)
                : $_temp($this->dsn);
        }
        else {
            $_temp = $persistent ? 'cubrid_pconnect' : 'cubrid_connect';
            $conn_id = ($this->username !== '')
                ? $_temp($this->hostname, $this->port, $this->database, $this->username, $this->password)
                : $_temp($this->hostname, $this->port, $this->database);
        }

        return $conn_id;
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
        cubrid_ping($this->conn_id) === false && $this->conn_id = false;
    }

    /**
     * Database version number
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        if (!$this->conn_id || ($version = cubrid_get_server_info($this->conn_id)) === false) {
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
        return @cubrid_query($sql, $this->conn_id);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        cubrid_get_autocommit($this->conn_id) && cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_FALSE);
        return true;
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        cubrid_commit($this->conn_id);

        if ($this->auto_commit && !cubrid_get_autocommit($this->conn_id)) {
            cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_TRUE);
        }

        return true;
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        cubrid_rollback($this->conn_id);

        if ($this->auto_commit && !cubrid_get_autocommit($this->conn_id)) {
            cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_TRUE);
        }

        return true;
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        if (function_exists('cubrid_real_escape_string') &&
                (is_resource($this->conn_id) ||
                 (get_resource_type($this->conn_id) === 'Unknown' && preg_match('/Resource id #/', strval($this->conn_id))))) {
            return cubrid_real_escape_string($str, $this->conn_id);
        }

        return addslashes($str);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @cubrid_affected_rows();
    }

    /**
     * Insert ID
     *
     * @return  int     Row ID of last inserted row
     */
    public function insertId()
    {
        return @cubrid_insert_id($this->conn_id);
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
        $sql = 'SHOW TABLES';

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql." LIKE '".$this->escapeLikeStr($this->dbprefix)."%'";
        }

        return $sql;
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

        $query = $this->query('SHOW COLUMNS FROM '.$this->protectIdentifiers($table, true, null, false));
        if ($query === false) {
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
        return array('code' => cubrid_errno($this->conn_id), 'message' => cubrid_error($this->conn_id));
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
        @cubrid_close($this->conn_id);
    }
}

