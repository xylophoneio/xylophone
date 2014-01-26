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
 * PDO ODBC Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoOdbc extends \Xylophone\libraries\Database\Pdo\DatabasePdo
{
    /** @var    string  Database schema */
    public $schema = 'public';

    /** @var    string  Identifier escape character (must be empty for ODBC) */
    protected $escape_char = '';

    /** @var    string  ESCAPE statement string */
    protected $like_escape_str = " {escape '%s'} ";

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RND()', 'RND(%d)');

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        parent::initialize();

        if (empty($this->dsn)) {
            $this->dsn = 'odbc:';

            // Pre-defined DSN
            if (empty($this->hostname) && empty($this->HOSTNAME) && empty($this->port) && empty($this->PORT)) {
                if (isset($this->DSN)) {
                    $this->dsn .= 'DSN='.$this->DSN;
                }
                elseif (!empty($this->database)) {
                    $this->dsn .= 'DSN='.$this->database;
                }

                return;
            }

            // If the DSN is not pre-configured - try to build an IBM DB2 connection string
            $this->dsn .= 'DRIVER='.(isset($this->DRIVER) ? '{'.$this->DRIVER.'}' : '{IBM DB2 ODBC DRIVER}').';';

            if (isset($this->DATABASE)) {
                $this->dsn .= 'DATABASE='.$this->DATABASE.';';
            }
            elseif (!empty($this->database)) {
                $this->dsn .= 'DATABASE='.$this->database.';';
            }

            if (isset($this->HOSTNAME)) {
                $this->dsn .= 'HOSTNAME='.$this->HOSTNAME.';';
            }
            else {
                $this->dsn .= 'HOSTNAME='.(empty($this->hostname) ? '127.0.0.1;' : $this->hostname.';');
            }

            if (isset($this->PORT)) {
                $this->dsn .= 'PORT='.$this->port.';';
            }
            elseif (!empty($this->port)) {
                $this->dsn .= ';PORT='.$this->port.';';
            }

            $this->dsn .= 'PROTOCOL='.(isset($this->PROTOCOL) ? $this->PROTOCOL.';' : 'TCPIP;');
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
        $sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema = \''.$this->schema.'\'';

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql.' AND table_name LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\' '.
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
        return 'SELECT column_name FROM information_schema.columns WHERE table_name = '.$this->escape($table);
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
        return preg_replace('/(^\SELECT (DISTINCT)?)/i','\\1 TOP '.$this->qb_limit.' ', $sql);
    }
}

