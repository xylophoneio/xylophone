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
namespace Xylophone\libraries\Database\Pdo;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Database Driver Class
 *
 * Note: DbBase is an extender class that extends the
 * Database class, including query builder if configured.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Pdo
 * @link        http://xylophone.io/user_guide/database/
 */
class DatabasePdo extends \Xylophone\libraries\Database\DbBase
{
    /** @var    array   PDO Options */
    public $options = array();

    /**
     * Connect to database
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    protected function dbConnect($persistent = false)
    {
        $this->options[PDO::ATTR_PERSISTENT] = $persistent;

        // Connecting...
        try {
            return @new PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            if ($this->db_debug && empty($this->failover)) {
                $this->displayError($e->getMessage(), '', TRUE);
            }

            return FALSE;
        }
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        $this->conn_id || $this->initialize();

        // Not all subdrivers support the getAttribute() method
        try {
            return $this->conn_id->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (PDOException $e) {
            return parent::dbVersion();
        }
    }

    /**
     * Execute the query
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    protected function dbExecute($sql)
    {
        return $this->conn_id->query($sql);
    }

    /**
     * Begin Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransBegin()
    {
        return $this->conn_id->beginTransaction();
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransCommit()
    {
        return $this->conn_id->commit();
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbTransRollback()
    {
        return $this->conn_id->rollBack();
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        // Escape the string
        $str = $this->conn_id->quote($str);

        // If there are duplicated quotes, trim them away
        return ($str[0] === "'") ? substr($str, 1, -1) : $str;
    }

    /**
     * Affected Rows
     *
     * @return  int     Number of affected rows
     */
    public function affectedRows()
    {
        return is_object($this->result_id) ? $this->result_id->rowCount() : 0;
    }

    /**
     * Insert ID
     *
     * @param   string  $name   Sequence object name
     * @return  int     Row ID of last inserted row
     */
    public function insertId($name = null)
    {
        return $this->conn_id->lastInsertId($name);
    }

    /**
     * Field data query
     *
     * Generates a platform-specific query so that the column data can be retrieved
     *
     * @param   string  $table  Table name
     * @return  object  Field data
     */
    public function fieldData($table)
    {
        if ($table === '') {
            return $this->displayError('db_field_param_missing');
        }

        $query = $this->query('SELECT TOP 1 * FROM '.$this->protectIdentifiers($table, true, null, false));
        return $query->fieldData();
    }

    /**
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occured.
     *
     * @return  array   Error information
     */
    public function error()
    {
        $error = array('code' => '00000', 'message' => '');
        $pdo_error = $this->conn_id->errorInfo();

        if (empty($pdo_error[0])) {
            return $error;
        }

        $error['code'] = isset($pdo_error[1]) ? $pdo_error[0].'/'.$pdo_error[1] : $pdo_error[0];
        if (isset($pdo_error[2])) {
            $error['message'] = $pdo_error[2];
        }

        return $error;
    }

    /**
     * Update_Batch statement
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
            $cases .= $k.' = CASE '."\n";

            foreach ($v as $row) {
                $cases .= $row."\n";
            }

            $cases .= 'ELSE '.$k.' END, ';
        }

        $this->where($index.' IN('.implode(',', $ids).')', null, false);

        return 'UPDATE '.$table.' SET '.substr($cases, 0, -2).$this->dbCompileWhereHaving('qb_where');
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
        return 'TRUNCATE TABLE '.$table;
    }
}

