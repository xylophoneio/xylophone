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
namespace Xylophone\libraries\DbForge\Sqlite3;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLite3 Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Sqlite3
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgeSqlite3 extends \Xylophone\libraries\DbForge\DbForge
{
    /** @var    bool    UNSIGNED support */
    protected $db_unsigned = false;

    /** @var    string  NULL value representation in CREATE/ALTER TABLE statements */
    protected $db_null = 'NULL';

    /**
     * Constructor
     *
     * @param   array   $config     Config params
     * @param   array   $extras     Extra config params
     * @return  void
     */
    public function __construct($config, $extras)
    {
        parent::__construct($config, $extras);

        if (version_compare($this->db->version(), '3.3', '<')) {
            $this->db_create_table_if = false;
        }
    }

    /**
     * Create database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function createDatabase($db_name = '')
    {
        // In SQLite, a database is created when you connect to the database.
        // We'll return TRUE so that an error isn't generated
        return true;
    }

    /**
     * Drop database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dropDatabase($db_name = '')
    {
        // In SQLite, a database is dropped when we delete a file
        if (@file_exists($this->db->database)) {
            // We need to close the pseudo-connection first
            $this->db->close();
            if (!@unlink($this->db->database)) {
                return $this->db->displayError('db_unable_to_drop');
            }
            elseif (!empty($this->db->data_cache['db_names'])) {
                $key = array_search(strtolower($this->db->database),
                    array_map('strtolower', $this->db->data_cache['db_names']), true);
                if ($key !== false) {
                    unset($this->db->data_cache['db_names'][$key]);
                }
            }

            return true;
        }

        return $this->db->displayError('db_unable_to_drop');
    }

    /**
     * ALTER TABLE Query
     *
     * @param   string  $alter_type ALTER type
     * @param   string  $table      Table name
     * @param   mixed   $field      Column definition
     * @return  mixed   ALTER string or array of strings
     */
    protected function alterTableQuery($alter_type, $table, $field)
    {
        if ($alter_type === 'DROP' || $alter_type === 'CHANGE') {
            // drop_column():
            //  BEGIN TRANSACTION;
            //  CREATE TEMPORARY TABLE t1_backup(a,b);
            //  INSERT INTO t1_backup SELECT a,b FROM t1;
            //  DROP TABLE t1;
            //  CREATE TABLE t1(a,b);
            //  INSERT INTO t1 SELECT a,b FROM t1_backup;
            //  DROP TABLE t1_backup;
            //  COMMIT;
            return false;
        }

        return parent::alterTableQuery($alter_type, $table, $field);
    }

    /**
     * Process column
     *
     * @param   array   $field  Field definition
     * @return  string  Column definition string
     */
    protected function processColumn($field)
    {
        return $this->db->escapeIdentifiers($field['name']).' '.$field['type'].$field['auto_increment'].
            $field['null'].$field['unique'].$field['default'];
    }

    /**
     * Field attribute TYPE
     *
     * Performs a data type mapping between different databases.
     *
     * @param   array   $attributes     Field attributes
     * @return  void
     */
    protected function attrType(&$attributes)
    {
        switch (strtoupper($attributes['TYPE'])) {
            case 'ENUM':
            case 'SET':
                $attributes['TYPE'] = 'TEXT';
                return;
            default: return;
        }
    }

    /**
     * Field attribute AUTO_INCREMENT
     *
     * @param   array   $attributes Field attributes
     * @param   array   $field      Field definition
     * @return  void
     */
    protected function attrAutoIncrement(&$attributes, &$field)
    {
        if (!empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === true &&
        stripos($field['type'], 'int') !== false) {
            $field['type'] = 'INTEGER PRIMARY KEY';
            $field['default'] = '';
            $field['null'] = '';
            $field['unique'] = '';
            $field['auto_increment'] = ' AUTOINCREMENT';
            $this->primary_keys = array();
        }
    }
}

