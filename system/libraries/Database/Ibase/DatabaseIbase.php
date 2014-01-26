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
namespace Xylophone\libraries\Database\Ibase;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Firebird/Interbase Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Ibase
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabaseIbase extends \Xylophone\libraries\Database\DbBase
{
    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RAND()', 'RAND()');

    /** @var    resource    IBase Transaction status flag */
    protected $ibase_trans;

    /**
     * Non-persistent database connection
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        return $persistent ?
            @ibase_pconnect($this->hostname.':'.$this->database, $this->username, $this->password, $this->char_set) :
            @ibase_connect($this->hostname.':'.$this->database, $this->username, $this->password, $this->char_set);
    }

    /**
     * Database version number
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        if (($service = ibase_service_attach($this->hostname, $this->username, $this->password))) {
            $version = ibase_server_info($service, IBASE_SVC_SERVER_VERSION);

            // Don't keep the service open
            ibase_service_detach($service);
            return $version;
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
        return @ibase_query($this->conn_id, $sql);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        $this->ibase_trans = @ibase_trans($this->conn_id);
        return true;
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return @ibase_commit($this->ibase_trans);
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        return @ibase_rollback($this->ibase_trans);
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return @ibase_affected_rows($this->conn_id);
    }

    /**
     * Insert ID
     *
     * @param   string  $generator_name Generator name
     * @param   int     $inc_by         Increment by
     * @return  int     Row ID of last inserted row
     */
    public function insertId($generator_name, $inc_by = 0)
    {
        // If a generator hasn't been used before it will return 0
        return ibase_gen_id('"'.$generator_name.'"', $inc_by);
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
        $sql = 'SELECT "RDB$RELATION_NAME" FROM "RDB$RELATIONS" WHERE "RDB$RELATION_NAME" NOT LIKE \'RDB$%\' AND "RDB$RELATION_NAME" NOT LIKE \'MON$%\'';

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql.' AND "RDB$RELATION_NAME" LIKE \''.$this->escapeLikeStr($this->dbprefix)."%' "
                .sprintf($this->like_escape_str, $this->like_escape_chr);
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
        return 'SELECT "RDB$FIELD_NAME" FROM "RDB$RELATION_FIELDS" WHERE "RDB$RELATION_NAME" = '.$this->escape($table);
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

        $sql = 'SELECT "rfields"."RDB$FIELD_NAME" AS "name",
            CASE "fields"."RDB$FIELD_TYPE"
                WHEN 7 THEN \'SMALLINT\'
                WHEN 8 THEN \'INTEGER\'
                WHEN 9 THEN \'QUAD\'
                WHEN 10 THEN \'FLOAT\'
                WHEN 11 THEN \'DFLOAT\'
                WHEN 12 THEN \'DATE\'
                WHEN 13 THEN \'TIME\'
                WHEN 14 THEN \'CHAR\'
                WHEN 16 THEN \'INT64\'
                WHEN 27 THEN \'DOUBLE\'
                WHEN 35 THEN \'TIMESTAMP\'
                WHEN 37 THEN \'VARCHAR\'
                WHEN 40 THEN \'CSTRING\'
                WHEN 261 THEN \'BLOB\'
                ELSE NULL
                END AS "type",
            "fields"."RDB$FIELD_LENGTH" AS "max_length",
            "rfields"."RDB$DEFAULT_VALUE" AS "default"
                FROM "RDB$RELATION_FIELDS" "rfields"
                JOIN "RDB$FIELDS" "fields" ON "rfields"."RDB$FIELD_SOURCE" = "fields"."RDB$FIELD_NAME"
                WHERE "rfields"."RDB$RELATION_NAME" = '.$this->escape($table).'
                ORDER BY "rfields"."RDB$FIELD_POSITION"';

        return (($query = $this->query($sql)) !== false) ? $query->resultObject() : false;
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
        return array('code' => ibase_errcode(), 'message' => ibase_errmsg());
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
     * LIMIT
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param   string  $sql    Query string
     * @return  string  Query string with LIMIT clause
     */
    protected function dbLimit($sql)
    {
        // Limit clause depends on if Interbase or Firebird
        if (stripos($this->version(), 'firebird') !== false) {
            $select = 'FIRST '.$this->qb_limit.($this->qb_offset ? ' SKIP '.$this->qb_offset : '');
        }
        else {
            $select = 'ROWS '.
                ($this->qb_offset ? $this->qb_offset.' TO '.($this->qb_limit + $this->qb_offset) : $this->qb_limit);
        }

        return preg_replace('`SELECT`i', 'SELECT '.$select, $sql, 1);
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    protected function dbClose()
    {
        @ibase_close($this->conn_id);
    }
}

