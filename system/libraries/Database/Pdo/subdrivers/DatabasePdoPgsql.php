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
 * PDO PostgresSQL Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoPgsql extends \Xylophone\libraries\Database\Pdo\DatabasePdo
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

        if (empty($this->dsn)) {
            $this->dsn = 'pgsql:host='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);
            empty($this->port) || $this->dsn .= ';port='.$this->port;
            empty($this->database) || $this->dsn .= ';dbname='.$this->database;
        }
    }

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        $this->conn_id = parent::dbConnect($persistent);

        if (is_object($this->conn_id) && !empty($this->schema)) {
            $this->simpleQuery('SET search_path TO '.$this->schema.',public');
        }

        return $this->conn_id;
    }

    /**
     * Insert ID
     *
     * @param   string  $name   Sequence object name
     * @return  int     Row ID of last inserted row
     */
    public function insertId($name = NULL)
    {
        if ($name === null && version_compare($this->version(), '8.1', '>=')) {
            $query = $this->query('SELECT LASTVAL() AS ins_id');
            $query = $query->row();
            return $query->ins_id;
        }

        return $this->conn_id->lastInsertId($name);
    }

    /**
     * Determines if a query is a "write" type.
     *
     * @param   string  $sql    Query string
     * @return  bool    TRUE if write query, otherwise FALSE
     */
    public function isWriteType($sql)
    {
        return (bool)preg_match('/^\s*"?(SET|INSERT(?![^\)]+\)\s+RETURNING)|UPDATE(?!.*\sRETURNING)|DELETE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s+/i', str_replace(array("\r\n", "\r", "\n"), ' ', $sql));
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
        if (is_bool($str)) {
            return $str ? 'TRUE' : 'FALSE';
        }

        return parent::escape($str);
    }

    /**
     * ORDER BY
     *
     * @param   string  $orderby    Field name(s)
     * @param   string  $direction  Order direction: 'ASC', 'DESC' or 'RANDOM'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orderBy($orderby, $direction = '', $escape = null)
    {
        $direction = strtoupper(trim($direction));
        if ($direction === 'RANDOM') {
            if (!is_float($orderby) && ctype_digit((string)$orderby)) {
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
     * List database tables
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param   bool    $prefix_limit   Whether to limit by database prefix
     * @return  string  Table listing
     */
    protected function dbListTables($prefix_limit = false)
    {
        $sql = 'SELECT "table_name" FROM "information_schema"."tables" WHERE "table_schema" = \''.$this->schema.'\'';

        if ($prefix_limit === true && $this->dbprefix !== '') {
            return $sql.' AND "table_name" LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
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
     * Update Batch statement
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
}

