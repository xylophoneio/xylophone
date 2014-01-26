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
namespace Xylophone\libraries\DbForge\Postgre;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PostgresSQL Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Postgre
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgePostgre extends \Xylophone\libraries\DbForge\DbForge
{
    /** @var    array   UNSIGNED support */
    protected $db_unsigned = array(
        'INT2' => 'INTEGER',
        'SMALLINT' => 'INTEGER',
        'INT' => 'BIGINT',
        'INT4' => 'BIGINT',
        'INTEGER' => 'BIGINT',
        'INT8' => 'NUMERIC',
        'BIGINT' => 'NUMERIC',
        'REAL' => 'DOUBLE PRECISION',
        'FLOAT' => 'DOUBLE PRECISION'
    );

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

        if (version_compare($this->db->version(), '9.0', '>')) {
            $this->db_create_table_if = 'CREATE TABLE IF NOT EXISTS';
        }
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
        if (in_array($alter_type, array('DROP', 'ADD'), TRUE)) {
            return parent::alterTableQuery($alter_type, $table, $field);
        }

        $sql = 'ALTER TABLE '.$this->db->escapeIdentifiers($table);
        $sqls = array();
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            if ($field[$i]['_literal'] !== false) {
                return false;
            }

            if (version_compare($this->db->version(), '8', '>=') && isset($field[$i]['type'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ' TYPE '.$field[$i]['type'].$field[$i]['length'];
            }

            if (!empty($field[$i]['default'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ' SET DEFAULT '.$field[$i]['default'];
            }

            if (isset($field[$i]['null'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ($field[$i]['null'] === true ? ' DROP NOT NULL' : ' SET NOT NULL');
            }

            if (!empty($field[$i]['new_name'])) {
                $sqls[] = $sql.' RENAME COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                    ' TO '.$this->db->escapeIdentifiers($field[$i]['new_name']);
            }
        }

        return $sqls;
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
        // Reset field lenghts for data types that don't support it
        if (isset($attributes['CONSTRAINT']) && stripos($attributes['TYPE'], 'int') !== false) {
            $attributes['CONSTRAINT'] = null;
        }

        switch (strtoupper($attributes['TYPE'])) {
            case 'TINYINT':
                $attributes['TYPE'] = 'SMALLINT';
                $attributes['UNSIGNED'] = FALSE;
                return;
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = FALSE;
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
        if (!empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === true) {
            $field['type'] = ($field['type'] === 'NUMERIC') ? 'BIGSERIAL' : 'SERIAL';
        }
    }
}

