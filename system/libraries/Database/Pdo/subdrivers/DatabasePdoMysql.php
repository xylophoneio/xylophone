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
 * PDO MySQL Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoMysql extends \Xylophone\libraries\Database\Pdo\DatabasePdo
{
    /** @var    bool    Compression flag */
    public $compress = false;

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
        global $XY;

        parent::initialize();

        if (empty($this->dsn)) {
            $this->dsn = 'mysql:host='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);
            empty($this->port) || $this->dsn .= ';port='.$this->port;
            empty($this->database) || $this->dsn .= ';dbname='.$this->database;
            empty($this->char_set) || $this->dsn .= ';charset='.$this->char_set;
        }
        elseif (!empty($this->char_set) && strpos($this->dsn, 'charset=', 6) === FALSE && $XY->isPhp('5.3.6')) {
            $this->dsn .= ';charset='.$this->char_set;
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
        global $XY;

        // Prior to PHP 5.3.6, even if the charset was supplied in the DSN
        // on connect - it was ignored. This is a work-around for the issue.
        // Reference: http://www.php.net/manual/en/ref.pdo-mysql.connection.php
        if (!$XY->isPhp('5.3.6') && !empty($this->char_set)) {
            $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES '.$this->char_set.
                (empty($this->dbcollat) ? '' : ' COLLATE '.$this->dbcollat);
        }

        if ($this->stricton) {
            if (empty($this->options[PDO::MYSQL_ATTR_INIT_COMMAND])) {
                $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET SESSION sql_mode="STRICT_ALL_TABLES"';
            }
            else {
                $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] .= ', @@session.sql_mode = "STRICT_ALL_TABLES"';
            }
        }

        if ($this->compress === true) {
            $this->options[PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        return parent::dbConnect($persistent);
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

        if ($prefix_limit === true && $this->dbprefix !== '') {
            return $sql.' LIKE \''.$this->escapeLikeStr($this->dbprefix).'%\'';
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
        return 'TRUNCATE '.$table;
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
}

