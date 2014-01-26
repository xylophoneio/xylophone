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
 * PDO CUBRID Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdoCubrid extends \Xylophone\libraries\Database\Pdo\DatabasePdo
{
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

        if (empty($this->dsn)) {
            $this->dsn = 'cubrid:host='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);

            empty($this->port) || $this->dsn .= ';port='.$this->port;
            empty($this->database) || $this->dsn .= ';dbname='.$this->database;
            empty($this->char_set) || $this->dsn .= ';charset='.$this->char_set;
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
     * Field data query
     *
     * Generates a platform-specific query so that the column data can be retrieved
     *
     * @param   string  $table  Table name
     * @return  string  Query string
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
                $field === $index || $final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
            }
        }

        $cases = '';
        foreach ($final as $k => $v) {
            $cases .= $k." = CASE \n".implode("\n", $v)."\n".'ELSE '.$k.' END), ';
        }

        $this->where($index.' IN('.implode(',', $ids).')', null, false);

        return 'UPDATE '.$table.' SET '.substr($cases, 0, -2).$this->compileWhereHaving('qb_where');
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

