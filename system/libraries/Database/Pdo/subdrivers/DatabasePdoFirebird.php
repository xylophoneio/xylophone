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
 * PDO Firebird Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoFirebid extends \Xylophone\libraries\Database\Pdo\DatabasePdo
{
    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RAND()', 'RAND()');

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (empty($this->dsn)) {
            $this->dsn = 'firebird:';

            if (!empty($this->database)) {
                $this->dsn .= 'dbname='.$this->database;
            }
            elseif (!empty($this->hostname)) {
                $this->dsn .= 'dbname='.$this->hostname;
            }

            empty($this->char_set) || $this->dsn .= ';charset='.$this->char_set;
            empty($this->role) || $this->dsn .= ';role='.$this->role;
        }
        elseif (!empty($this->char_set) && strpos($this->dsn, 'charset=', 9) === false) {
            $this->dsn .= ';charset='.$this->char_set;
        }
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

        if ($prefix_limit === true && $this->dbprefix !== '') {
            return $sql.' AND "RDB$RELATION_NAME" LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
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
        return 'SELECT "RDB$FIELD_NAME" FROM "RDB$RELATION_FIELDS" WHERE "RDB$RELATION_NAME" = '.$this->escape($table);
    }

    /**
     * Returns an object with field data
     *
     * @param   string  $table  Table name
     * @return  string  Query string
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
            $select = 'FIRST '.$this->qb_limit.($this->qb_offset > 0 ? ' SKIP '.$this->qb_offset : '');
        }
        else {
            $select = 'ROWS '.
                ($this->qb_offset > 0 ? $this->qb_offset.' TO '.($this->qb_limit + $this->qb_offset) : $this->qb_limit);
        }

        return preg_replace('`SELECT`i', 'SELECT '.$select, $sql);
    }
}

