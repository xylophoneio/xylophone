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
 * PDO DBLIB Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoDblib extends \Xylophone\libraries\Database\Pdo\DatabasePdo
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
    protected $quoted_identifier;

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (empty($this->dsn)) {
            $this->dsn = $this->subdriver.':host='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);

            empty($this->port) || $this->dsn .= (DIRECTORY_SEPARATOR === '\\' ? ',' : ':').$this->port;
            empty($this->database) || $this->dsn .= ';dbname='.$this->database;
            empty($this->char_set) || $this->dsn .= ';charset='.$this->char_set;
            empty($this->appname) || $this->dsn .= ';appname='.$this->appname;
        }
        else {
            if (!empty($this->char_set) && strpos($this->dsn, 'charset=', 6) === false) {
                $this->dsn .= ';charset='.$this->char_set;
            }
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

        if (!is_object($this->conn_id)) {
            return $this->conn_id;
        }

        // Determine how identifiers are escaped
        $query = $this->query('SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi');
        $query = $query->rowArray();
        $this->quoted_identifier = empty($query) ? false : (bool)$query['qi'];
        $this->escape_char = $this->quoted_identifier ? '"' : array('[', ']');

        return $this->conn_id;
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
        return 'SELECT '.$this->escapeIdentifiers('name')
            .' FROM '.$this->escapeIdentifiers('sysobjects')
            .' WHERE '.$this->escapeIdentifiers('type')." = 'U'";

        if ($prefix_limit === true && $this->dbprefix !== '') {
            $sql .= ' AND '.$this->escapeIdentifiers('name').' LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
                sprintf($this->like_escape_str, $this->like_escape_chr);
        }

        return $sql.' ORDER BY '.$this->escapeIdentifiers('name');
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
        return 'SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.Columns
            WHERE UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));
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
}

