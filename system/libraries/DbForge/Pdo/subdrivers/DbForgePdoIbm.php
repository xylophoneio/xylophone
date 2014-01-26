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
 * PDO IBM DB2 Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge/Pdo/subdrivers
 * @link        http://xylophone.io/user_guide/database/
 */
class DbForgePdoIbm extends \Xylophone\libraries\DbForge\Pdo\DbForgePdo
{
    /** @var    string  RENAME TABLE statement */
    protected $db_rename_table = 'RENAME TABLE %s TO %s';

    /** @var    array   UNSIGNED support */
    protected $db_unsigned = array(
        'SMALLINT' => 'INTEGER',
        'INT' => 'BIGINT',
        'INTEGER' => 'BIGINT'
    );

    /** @var    string  DEFAULT value representation in CREATE/ALTER TABLE statements */
    protected $db_default = false;

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
        if ($alter_type === 'CHANGE') {
            $alter_type = 'MODIFY';
        }

        return parent::alterTableQuery($alter_type, $table, $field);
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
     * Field attribute UNIQUE
     *
     * @param   array   $attributes Field attributes
     * @param   array   $field      Field definition
     * @return  void
     */
    protected function attrUnique(&$attributes, &$field)
    {
        if (!empty($attributes['UNIQUE']) && $attributes['UNIQUE'] === true) {
            $field['unique'] = ' UNIQUE';

            // UNIQUE must be used with NOT NULL
            $field['null'] = ' NOT NULL';
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

