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
namespace Xylophone\libraries\DbForge\Ibase;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Interbase/Firebird Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Ibase
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgeIbase extends \Xylophone\libraries\DbForge\DbForge
{
    /** @var    string  CREATE TABLE IF statement */
    protected $db_create_table_if = false;

    /** @var    string  RENAME TABLE statement */
    protected $db_rename_table = false;

    /** @var    string  DROP TABLE IF statement */
    protected $db_drop_table_if = false;

    /** @var    array   UNSIGNED support */
    protected $db_unsigned = array(
        'SMALLINT' => 'INTEGER',
        'INTEGER' => 'INT64',
        'FLOAT' => 'DOUBLE PRECISION'
    );

    /** @var    string  NULL value representation in CREATE/ALTER TABLE statements */
    protected $db_null = 'NULL';

    /**
     * Create database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function createDatabase($db_name)
    {
        // Firebird databases are flat files, so a path is required
        // Hostname is needed for remote access
        empty($this->db->hostname) || $db_name = $this->hostname.':'.$db_name;
        return parent::createDatabase('"'.$db_name.'"');
    }

    /**
     * Drop database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dropDatabase($db_name = '')
    {
        if (!ibase_drop_db($this->conn_id)) {
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
        if (in_array($alter_type, array('DROP', 'ADD'), true)) {
            return parent::alterTableQuery($alter_type, $table, $field);
        }

        $sql = 'ALTER TABLE '.$this->db->escapeIdentifiers($table);
        $sqls = array();
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            if ($field[$i]['_literal'] !== false) {
                return false;
            }

            if (isset($field[$i]['type'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentififers($field[$i]['name']).
                    ' TYPE '.$field[$i]['type'].$field[$i]['length'];
            }

            if (!empty($field[$i]['default'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ' SET DEFAULT '.$field[$i]['default'];
            }

            if (isset($field[$i]['null'])) {
                $sqls[] = 'UPDATE "RDB$RELATION_FIELDS" SET "RDB$NULL_FLAG" = '.
                    ($field[$i]['null'] === true ? 'NULL' : '1').
                    ' WHERE "RDB$FIELD_NAME" = '.$this->db->escape($field[$i]['name']).
                    ' AND "RDB$RELATION_NAME" = '.$this->db->escape($table);
            }

            if (!empty($field[$i]['new_name'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ' TO '.$this->db->escapeIdentifiers($field[$i]['new_name']);
            }
        }

        return $sqls;
    }

    /**
     * Process column
     *
     * @param   array   $field  Field definition
     * @return  string  Column definition string
     */
    protected function processColumn($field)
    {
        return $this->db->escapeIdentifiers($field['name']).' '.$field['type'].
            $field['length'].$field['null'].$field['unique'].$field['default'];
    }

    /**
     * Field attribute TYPE
     *
     * Performs a data type mapping between different databases.
     *
     * @param   array   &$attributes    Field attributes
     * @return  void
     */
    protected function attrType(&$attributes)
    {
        switch (strtoupper($attributes['TYPE'])) {
            case 'TINYINT':
                $attributes['TYPE'] = 'SMALLINT';
                $attributes['UNSIGNED'] = FALSE;
                return;
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = FALSE;
                return;
            case 'INT':
                $attributes['TYPE'] = 'INTEGER';
                return;
            case 'BIGINT':
                $attributes['TYPE'] = 'INT64';
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
        // Not supported
    }
}

