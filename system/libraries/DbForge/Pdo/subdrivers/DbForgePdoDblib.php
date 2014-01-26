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
namespace Xylophone\libraries\DbForge\Pdo\subdrivers;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO DBLIB Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgePdoDblib extends \Xylophone\libraries\DbForge\Pdo\DbForgePdo
{
    /** @var    string  CREATE TABLE IF statement */
    protected $db_create_table_if = 'IF NOT EXISTS (SELECT * FROM sysobjects WHERE ID = object_id(N\'%s\') AND OBJECTPROPERTY(id, N\'IsUserTable\') = 1)\nCREATE TABLE';

    /** @var    string  DROP TABLE IF EXISTS statement */
    protected $db_drop_table_if = 'IF EXISTS (SELECT * FROM sysobjects WHERE ID = object_id(N\'%s\') AND OBJECTPROPERTY(id, N\'IsUserTable\') = 1)\nDROP TABLE';

    /** @var    array   UNSIGNED support */
    protected $db_unsigned = array(
        'TINYINT' => 'SMALLINT',
        'SMALLINT' => 'INT',
        'INT' => 'BIGINT',
        'REAL' => 'FLOAT'
    );

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
        if (in_array($alter_type, array('ADD', 'DROP'), TRUE)) {
            return parent::alterTableQuery($alter_type, $table, $field);
        }

        $sql = 'ALTER TABLE '.$this->db->escapeIdentifiers($table).' ALTER COLUMN ';
        $sqls = array();
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            $sqls[] = $sql.$this->processColumn($field[$i]);
        }

        return $sqls;
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
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = FALSE;
                return;
            case 'INTEGER':
                $attributes['TYPE'] = 'INT';
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
            $field['auto_increment'] = ' IDENTITY(1,1)';
        }
    }
}

