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
 * PDO Oracle Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgePdoOci extends \Xylophone\libraries\DbForge\Pdo\DbForgePdo
{
    /** @var    string  CREATE DATABASE statement */
    protected $db_create_database = false;

    /** @var    string  DROP DATABASE statement */
    protected $db_drop_database = false;

    /** @var    string  CREATE TABLE IF statement */
    protected $db_create_table_if = 'CREATE TABLE IF NOT EXISTS';

    /** @var    array   UNSIGNED support */
    protected $db_unsigned = false;

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
        if ($alter_type === 'DROP') {
            return parent::alterTableQuery($alter_type, $table, $field);
        }
        elseif ($alter_type === 'CHANGE') {
            $alter_type = 'MODIFY';
        }

        $sql = 'ALTER TABLE '.$this->db->escapeIdentifiers($table);
        $sqls = array();
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            if ($field[$i]['_literal'] !== false) {
                $field[$i] = "\n\t".$field[$i]['_literal'];
            }
            else {
                $field[$i]['_literal'] = "\n\t".$this->processColumn($field[$i]);
                if ($alter_type === 'MODIFY' && ! empty($field[$i]['new_name'])) {
                    $sqls[] = $sql.' RENAME COLUMN '.$this->db->escapeIdentifiers($field[$i]['name']).
                        ' '.$this->db->escapeIdentifiers($field[$i]['new_name']);
                }
            }
        }

        $sql .= ' '.$alter_type.' ';
        $sql .= (count($field) === 1) ? $field[0] : '('.implode(',', $field).')';

        // RENAME COLUMN must be executed after MODIFY
        array_unshift($sqls, $sql);
        return $sql;
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
        // Not supported - sequences and triggers must be used instead
    }
}

