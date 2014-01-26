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
 * PDO SQL SRV Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoSqlsrv extends \Xylophone\libraries\Database\Pdo\DatabasePdo
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
            $this->dsn = 'sqlsrv:Server='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);
            empty($this->port) || $this->dsn .= ','.$this->port;
            empty($this->database) || $this->dsn .= ';Database='.$this->database;

            // Some custom options
            if (isset($this->QuotedId)) {
                $this->dsn .= ';QuotedId='.$this->QuotedId;
                $this->quoted_identifier = (bool)$this->QuotedId;
            }

            if (isset($this->ConnectionPooling)) {
                $this->dsn .= ';ConnectionPooling='.$this->ConnectionPooling;
            }

            $this->encrypt && $this->dsn .= ';Encrypt=1';
            isset($this->TraceOn) && $this->dsn .= ';TraceOn='.$this->TraceOn;
            isset($this->TrustServerCertificate) &&
                $this->dsn .= ';TrustServerCertificate='.$this->TrustServerCertificate;
            empty($this->APP) || $this->dsn .= ';APP='.$this->APP;
            empty($this->Failover_Partner) || $this->dsn .= ';Failover_Partner='.$this->Failover_Partner;
            empty($this->LoginTimeout) || $this->dsn .= ';LoginTimeout='.$this->LoginTimeout;
            empty($this->MultipleActiveResultSets) OR $this->dsn .= ';MultipleActiveResultSets='.
                $this->MultipleActiveResultSets;
            empty($this->TraceFile) || $this->dsn .= ';TraceFile='.$this->TraceFile;
            empty($this->WSID) || $this->dsn .= ';WSID='.$this->WSID;
        }
        elseif (preg_match('/QuotedId=(0|1)/', $this->dsn, $match)) {
            $this->quoted_identifier = (bool)$match[1];
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
        if (!empty($this->char_set) && preg_match('/utf[^8]*8/i', $this->char_set)) {
            $this->options[PDO::SQLSRV_ENCODING_UTF8] = 1;
        }

        $this->conn_id = parent::dbConnect($persistent);

        if (!is_object($this->conn_id) || is_bool($this->quoted_identifier)) {
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
        $sql = 'SELECT '.$this->escapeIdentifiers('name').
            ' FROM '.$this->escapeIdentifiers('sysobjects').
            ' WHERE '.$this->escapeIdentifiers('type').' = \'U\'';

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
        // As of SQL Server 2012 (11.0.*) OFFSET is supported
        if (version_compare($this->version(), '11', '>=')) {
            return $sql.' OFFSET '.(int) $this->qb_offset.' ROWS FETCH NEXT '.$this->qb_limit.' ROWS ONLY';
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
}

