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
namespace Xylophone\libraries\DbForge;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Forge Class
 *
 * @package     Xylophone
 * @subpackage  libraries/DbForge
 * @link        http://xylophone.io/user_guide/database/
 */
abstract class DbForge
{
    /** @var    object  Database object */
    protected $db;

    /** @var    array   Fields data */
    public $fields = array();

    /** @var    array   Keys data */
    public $keys = array();

    /** @var    array   Primary Keys data */
    public $primary_keys = array();

    /** @var    string  Database character set */
    public $db_char_set = '';

    /** @var    string  CREATE DATABASE statement */
    protected $db_create_database = 'CREATE DATABASE %s';

    /** @var    string  DROP DATABASE statement */
    protected $db_drop_database = 'DROP DATABASE %s';

    /** @var    string  CREATE TABLE statement */
    protected $db_create_table = "%s %s (%s\n)";

    /** @var    string  CREATE TABLE IF statement */
    protected $db_create_table_if = 'CREATE TABLE IF NOT EXISTS';

    /** @var    bool    Whether table keys are created from within the CREATE TABLE statement */
    protected $db_create_table_keys = false;

    /** @var    string  DROP TABLE IF EXISTS statement */
    protected $db_drop_table_if = 'DROP TABLE IF EXISTS';

    /** @var    string  RENAME TABLE statement */
    protected $db_rename_table = 'ALTER TABLE %s RENAME TO %s;';

    /** @var    bool|array  UNSIGNED support */
    protected $db_unsigned = true;

    /** @var    string  NULL value representation in CREATE/ALTER TABLE statements */
    protected $db_null = '';

    /** @var    string  DEFAULT value representation in CREATE/ALTER TABLE statements */
    protected $db_default = ' DEFAULT ';

    /**
     * Constructor
     *
     * @param   array   $config     Config params
     * @param   array   $extras     Extra config params
     * @return  void
     */
    public function __construct($config, $extras)
    {
        global $XY;

        if (isset($config['db'])) {
            // Use passed database object
            $this->db = $config['db'];
        }
        else {
            // Load db as necessary and use that
            isset($XY->db) || $XY->load->driver('database');
            $this->db = $XY->db;
        }

        $XY->logger->debug('Database Forge Class Initialized');
    }

    /**
     * Create database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function createDatabase($db_name)
    {
        if ($this->db_create_database === false) {
            return $this->db->displayError('db_unsupported_feature');
        }
        elseif (!$this->db->query(sprintf($this->db_create_database, $db_name, $this->db->char_set, $this->db->dbcollat))) {
            return $this->db->displayError('db_unable_to_drop');
        }

        if (!empty($this->db->data_cache['db_names'])) {
            $this->db->data_cache['db_names'][] = $db_name;
        }

        return true;
    }

    /**
     * Drop database
     *
     * @param   string  $db_name    Database name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dropDatabase($db_name)
    {
        global $XY;

        if ($db_name === '') {
            $XY->showError('A database name is required for that operation.');
            return false;
        }
        elseif ($this->db_drop_database === false) {
            return $this->db->displayError('db_unsupported_feature');
        }
        elseif (!$this->db->query(sprintf($this->db_drop_database, $db_name))) {
            return $this->db->displayError('db_unable_to_drop');
        }

        if (!empty($this->db->data_cache['db_names'])) {
            $key = array_search(strtolower($db_name), array_map('strtolower', $this->db->data_cache['db_names']), true);
            if ($key !== false) {
                unset($this->db->data_cache['db_names'][$key]);
            }
        }

        return true;
    }

    /**
     * Add Key
     *
     * @param   string  $key        Key name
     * @param   bool    $primary    Whether to make a primary key
     * @return  object  This object
     */
    public function addKey($key = '', $primary = false)
    {
        global $XY;

        if (empty($key)) {
            $XY->showError('Key information is required for that operation.');
        }

        if ($primary === true && is_array($key)) {
            foreach ($key as $one) {
                $this->addKey($one, $primary);
            }

            return $this;
        }

        if ($primary === true) {
            $this->primary_keys[] = $key;
        }
        else {
            $this->keys[] = $key;
        }

        return $this;
    }

    /**
     * Add Field
     *
     * @param   array   $field  Field name
     * @return  object  This object
     */
    public function addField($field = '')
    {
        global $XY;

        if (empty($field)) {
            $XY->showError('Field information is required.');
        }

        if (is_string($field)) {
            if ($field === 'id') {
                $this->addField(array(
                    'id' => array(
                        'type' => 'INT',
                        'constraint' => 9,
                        'auto_increment' => true
                    )
                ));
                $this->addKey('id', true);
            }
            else {
                if (strpos($field, ' ') === false) {
                    $XY->showError('Field information is required for that operation.');
                }

                $this->fields[] = $field;
            }
        }

        if (is_array($field)) {
            $this->fields = array_merge($this->fields, $field);
        }

        return $this;
    }

    /**
     * Create Table
     *
     * @param   string  $table          Table name
     * @param   bool    $if_not_exists  Whether to add IF NOT EXISTS condition
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function createTable($table = '', $if_not_exists = false)
    {
        global $XY;

        if ($table === '') {
            $XY->showError('A table name is required for that operation.');
        }
        else {
            $table = $this->db->dbprefix.$table;
        }

        if (count($this->fields) === 0) {
            $XY->showError('Field information is required.');
        }

        $sql = $this->createTableQuery($table, $if_not_exists);

        if (is_bool($sql)) {
            $this->reset();
            if ($sql === false) {
                return $this->db->displayError('db_unsupported_feature');
            }
        }

        if (($result = $this->db->query($sql)) !== false) {
            empty($this->db->data_cache['table_names']) || $this->db->data_cache['table_names'][] = $table;

            // Most databases don't support creating indexes from within the CREATE TABLE statement
            if (!empty($this->keys)) {
                for ($i = 0, $sqls = $this->processIndexes($table), $c = count($sqls); $i < $c; $i++) {
                    $this->db->query($sqls[$i]);
                }
            }
        }

        $this->reset();
        return $result;
    }

    /**
     * Create Table Query
     *
     * @param   string  $table          Table name
     * @param   bool    $if_not_exists  Whether to add IF NOT EXISTS condition
     * @return  mixed   TRUE if table exists, otherwise CREATE string
     */
    protected function createTableQuery($table, $if_not_exists)
    {
        if ($if_not_exists === true && $this->db_create_table_if === false) {
            if ($this->db->table_exists($table)) {
                return true;
            }
            else {
                $if_not_exists = false;
            }
        }

        $sql = ($if_not_exists) ? sprintf($this->db_create_table_if, $this->db->escape_identifiers($table)) :
            'CREATE TABLE';

        $columns = $this->processFields(true);
        for ($i = 0, $c = count($columns); $i < $c; $i++) {
            $columns[$i] = ($columns[$i]['_literal'] !== false) ?
                "\n\t".$columns[$i]['_literal'] : "\n\t".$this->processColumn($columns[$i]);
        }

        $columns = implode(',', $columns).$this->processPrimaryKeys($table);

        // Are indexes created from within the CREATE TABLE statement? (e.g. in MySQL)
        if ($this->db_create_table_keys === true) {
            $columns .= $this->processIndexes($table);
        }

        // db_create_table will usually have the following format: "%s %s (%s\n)"
        $sql = sprintf($this->db_create_table.';', $sql, $this->db->escapeIdentifiers($table), $columns);

        return $sql;
    }

    /**
     * Drop Table
     *
     * @param   string  $table          Table name
     * @param   bool    $if_not_exists  Whether to add IF EXISTS condition
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dropTable($table_name, $if_exists = false)
    {
        if ($table_name === '') {
            return $this->db->displayError('db_table_name_required');
        }

        $query = $this->dropTableQuery($this->db->dbprefix.$table_name, $if_exists);
        if ($query === false) {
            return $this->db->displayError('db_unsupported_feature');
        }
        elseif ($query === true) {
            return true;
        }

        $query = $this->db->query($query);

        // Update table list cache
        if ($query && ! empty($this->db->data_cache['table_names'])) {
            $key = array_search(strtolower($this->db->dbprefix.$table_name),
                array_map('strtolower', $this->db->data_cache['table_names']), true);
            if ($key !== false) {
                unset($this->db->data_cache['table_names'][$key]);
            }
        }

        return $query;
    }

    /**
     * Drop Table Query
     *
     * Generates a platform-specific DROP TABLE string
     *
     * @param   string  $table          Table name
     * @param   bool    $if_not_exists  Whether to add IF EXISTS condition
     * @return  mixed   TRUE if table doesn't exist, otherwise DROP string
     */
    protected function dropTableQuery($table, $if_exists)
    {
        $sql = 'DROP TABLE';

        if ($if_exists) {
            if ($this->db_drop_table_if === false) {
                if (!$this->db->table_exists($table)) {
                    return true;
                }
            }
            else {
                $sql = sprintf($this->db_drop_table_if, $this->db->escapeIdentifiers($table));
            }
        }

        return $sql.' '.$this->db->escapeIdentifiers($table);
    }

    /**
     * Rename Table
     *
     * @param   string  $table_name     Old table name
     * @param   string  $new_table_name New table name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function renameTable($table_name, $new_table_name)
    {
        global $XY;

        if ($table_name === '' || $new_table_name === '') {
            $XY->showError('A table name is required for that operation.');
            return false;
        }
        elseif ($this->db_rename_table === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        $result = $this->db->query(sprintf($this->db_rename_table,
            $this->db->escapeIdentifiers($this->db->dbprefix.$table_name),
            $this->db->escapeIdentifiers($this->db->dbprefix.$new_table_name))
        );

        if ($result && !empty($this->db->data_cache['table_names'])) {
            $key = array_search(strtolower($this->db->dbprefix.$table_name),
                array_map('strtolower', $this->db->data_cache['table_names']), true);
            if ($key !== false) {
                $this->db->data_cache['table_names'][$key] = $this->db->dbprefix.$new_table_name;
            }
        }

        return $result;
    }

    /**
     * Column Add
     *
     * @param   string  $table  Table name
     * @param   array   $field  Column definition
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function addColumn($table = '', $field = array())
    {
        global $XY;

        if ($table === '') {
            $XY->showError('A table name is required for that operation.');
        }

        // Work-around for literal column definitions
        if (!is_array($field)) {
            $field = array($field);
        }

        foreach (array_keys($field) as $k) {
            $this->addField(array($k => $field[$k]));
        }

        $sqls = $this->alterTableQuery('ADD', $this->db->dbprefix.$table, $this->processFields());
        $this->reset();
        if ($sqls === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        for ($i = 0, $c = count($sqls); $i < $c; $i++) {
            if ($this->db->query($sqls[$i]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Column Drop
     *
     * @param   string  $table  Table name
     * @param   string  $column Column name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dropColumn($table = '', $column = '')
    {
        global $XY;

        if ($table === '') {
            $XY->showError('A table name is required for that operation.');
        }

        if ($column === '') {
            $XY->showError('A column name is required for that operation.');
        }

        $sql = $this->alterTableQuery('DROP', $this->db->dbprefix.$table, $column);
        if ($sql === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        return $this->db->query($sql);
    }

    /**
     * Column Modify
     *
     * @param   string  $table  Table name
     * @param   string  $field  Column definition
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function modifyColumn($table = '', $field = array())
    {
        global $XY;

        if ($table === '') {
            $XY->showError('A table name is required for that operation.');
        }

        // Work-around for literal column definitions
        if (!is_array($field)) {
            $field = array($field);
        }

        foreach (array_keys($field) as $k) {
            $this->addField(array($k => $field[$k]));
        }

        if (count($this->fields) === 0) {
            $XY->showError('Field information is required.');
        }

        $sqls = $this->alterTableQuery('CHANGE', $this->db->dbprefix.$table, $this->processFields());
        $this->reset();
        if ($sqls === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        for ($i = 0, $c = count($sqls); $i < $c; $i++) {
            if ($this->db->query($sqls[$i]) === false) {
                return false;
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
        $sql = 'ALTER TABLE '.$this->db->escapeIdentifiers($table).' ';

        // DROP has everything it needs now.
        if ($alter_type === 'DROP') {
            return $sql.'DROP COLUMN '.$this->db->escapeIdentifiers($field);
        }

        $sql .= ($alter_type === 'ADD') ? 'ADD ' : $alter_type.' COLUMN ';

        $sqls = array();
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            $sqls[] = $sql.
                ($field[$i]['_literal'] !== false ? $field[$i]['_literal'] : $this->processColumn($field[$i]));
        }

        return $sqls;
    }

    /**
     * Process fields
     *
     * @param   bool    $create_table   Whether for a create table query
     * @return  array   Field definitions
     */
    protected function processFields($create_table = false)
    {
        $fields = array();

        foreach ($this->fields as $key => $attributes) {
            if (is_int($key) && !is_array($attributes)) {
                $fields[] = array('_literal' => $attributes);
                continue;
            }

            $attributes = array_change_key_case($attributes, CASE_UPPER);

            if ($create_table === true && empty($attributes['TYPE'])) {
                continue;
            }

            if (isset($attributes['TYPE'])) {
                $this->attrType($attributes);
                $this->attrUnsigned($attributes, $field);
            }

            $field = array(
                'name' => $key,
                'new_name' => isset($attributes['NAME']) ? $attributes['NAME'] : null,
                'type' => isset($attributes['TYPE']) ? $attributes['TYPE'] : null,
                'length' => '',
                'unsigned' => '',
                'null' => '',
                'unique' => '',
                'default' => '',
                'auto_increment' => '',
                '_literal' => false
            );

            if ($create_table === false) {
                if (isset($attributes['AFTER'])) {
                    $field['after'] = $attributes['AFTER'];
                }
                elseif (isset($attributes['FIRST'])) {
                    $field['first'] = (bool)$attributes['FIRST'];
                }
            }

            $this->attrDefault($attributes, $field);

            if (isset($attributes['NULL'])) {
                if ($attributes['NULL'] === true) {
                    $field['null'] = empty($this->db_null) ? '' : ' '.$this->db_null;
                }
                else {
                    $field['null'] = ' NOT NULL';
                }
            }
            elseif ($create_table === true) {
                $field['null'] = ' NOT NULL';
            }

            $this->attrAutoIncrement($attributes, $field);
            $this->attrUnique($attributes, $field);

            if (isset($attributes['TYPE']) && !empty($attributes['CONSTRAINT'])) {
                switch (strtoupper($attributes['TYPE'])) {
                    case 'ENUM':
                    case 'SET':
                        $attributes['CONSTRAINT'] = $this->db->escape($attributes['CONSTRAINT']);
                    default:
                        $field['length'] = is_array($attributes['CONSTRAINT']) ?
                            '(\''.implode('\',\'', $attributes['CONSTRAINT']).'\')' :
                            '('.$attributes['CONSTRAINT'].')';
                        break;
                }
            }

            $fields[] = $field;
        }

        return $fields;
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
            $field['length'].$field['unsigned'].$field['default'].$field['null'].
            $field['auto_increment'].$field['unique'];
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
        // Usually overriden by drivers
    }

    /**
     * Field attribute UNSIGNED
     *
     * Depending on the db_unsigned property value:
     *  - TRUE will always set $field['unsigned'] to 'UNSIGNED'
     *  - FALSE will always set $field['unsigned'] to ''
     *  - array(TYPE) will set $field['unsigned'] to 'UNSIGNED',
     *    if $attributes['TYPE'] is found in the array
     *  - array(TYPE => UTYPE) will change $field['type'],
     *    from TYPE to UTYPE in case of a match
     *
     * @param   array   $attributes Field attributes
     * @param   array   $field      Field definition
     * @return  void
     */
    protected function attrUnsigned(&$attributes, &$field)
    {
        if (empty($attributes['UNSIGNED']) || $attributes['UNSIGNED'] !== true) {
            return;
        }

        // Reset the attribute in order to avoid issues if we do type conversion
        $attributes['UNSIGNED'] = FALSE;

        if (is_array($this->db_unsigned)) {
            foreach (array_keys($this->db_unsigned) as $key) {
                if (is_int($key) && strcasecmp($attributes['TYPE'], $this->db_unsigned[$key]) === 0) {
                    $field['unsigned'] = ' UNSIGNED';
                    return;
                }
                elseif (is_string($key) && strcasecmp($attributes['TYPE'], $key) === 0) {
                    $field['type'] = $key;
                    return;
                }
            }

            return;
        }

        $field['unsigned'] = ($this->db_unsigned === TRUE) ? ' UNSIGNED' : '';
    }

    /**
     * Field attribute DEFAULT
     *
     * @param   array   $attributes Field attributes
     * @param   array   $field      Field definition
     * @return  void
     */
    protected function attrDefault(&$attributes, &$field)
    {
        if ($this->db_default === false) {
            return;
        }

        if (array_key_exists('DEFAULT', $attributes)) {
            if ($attributes['DEFAULT'] === null) {
                $field['default'] = empty($this->db_null) ? '' : $this->db_default.$this->db_null;

                // Override the NULL attribute if that's our default
                $attributes['NULL'] = null;
                $field['null'] = empty($this->db_null) ? '' : ' '.$this->db_null;
            }
            else {
                $field['default'] = $this->db_default.$this->db->escape($attributes['DEFAULT']);
            }
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
            $field['auto_increment'] = ' AUTO_INCREMENT';
        }
    }

    /**
     * Process primary keys
     *
     * @param   string  $table  Table name
     * @return  string  PRIMARY KEY clause
     */
    protected function processPrimaryKeys($table)
    {
        $sql = '';

        for ($i = 0, $c = count($this->primary_keys); $i < $c; $i++) {
            if (!isset($this->fields[$this->primary_keys[$i]])) {
                unset($this->primary_keys[$i]);
            }
        }

        if (count($this->primary_keys) > 0) {
            $sql .= ",\n\tCONSTRAINT ".$this->db->escapeIdentifiers('pk_'.$table).
                ' PRIMARY KEY('.implode(', ', $this->db->escapeIdentifiers($this->primary_keys)).')';
        }

        return $sql;
    }

    /**
     * Process indexes
     *
     * @param   string  $table  Table name
     * @return  array   INDEX clauses
     */
    protected function processIndexes($table)
    {
        $sqls = array();

        for ($i = 0, $c = count($this->keys); $i < $c; $i++) {
            if (is_array($this->keys[$i])) {
                for ($i2 = 0, $c2 = count($this->keys[$i]); $i2 < $c2; $i2++) {
                    if (!isset($this->fields[$this->keys[$i][$i2]])) {
                        unset($this->keys[$i][$i2]);
                        continue;
                    }
                }
            }
            elseif (!isset($this->fields[$this->keys[$i]])) {
                unset($this->keys[$i]);
                continue;
            }

            is_array($this->keys[$i]) || $this->keys[$i] = array($this->keys[$i]);

            $sqls[] = 'CREATE INDEX '.$this->db->escapeIdentifiers($table.'_'.implode('_', $this->keys[$i])).
                ' ON '.$this->db->escapeIdentifiers($table).
                ' ('.implode(', ', $this->db->escapeIdentifiers($this->keys[$i])).');';
        }

        return $sqls;
    }

    /**
     * Reset
     *
     * Resets table creation vars
     *
     * @return  void
     */
    protected function reset()
    {
        $this->fields = $this->keys = $this->primary_keys = array();
    }
}

