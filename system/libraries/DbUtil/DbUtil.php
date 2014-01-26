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
namespace Xylophone\libraries\DbUtil;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Utility Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database
 * @link        http://xylophone.io/user_guide/database/
 */
abstract class DbUtil
{
    /** @var    object  Database object */
    protected $db;

    /** @var    string  List databases statement */
    protected $db_list_databases = false;

    /** @var    string  OPTIMIZE TABLE statement */
    protected $db_optimize_table = false;

    /** @var    string  REPAIR TABLE statement */
    protected $db_repair_table = false;

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

        $XY->logger->debug('Database Utility Class Initialized');
    }

    /**
     * List databases
     *
     * @return  array   Database list
     */
    public function listDatabases()
    {
        // Is there a cached result?
        if (isset($this->db->data_cache['db_names'])) {
            return $this->db->data_cache['db_names'];
        }
        elseif ($this->db_list_databases === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        $this->db->data_cache['db_names'] = array();

        $query = $this->db->query($this->db_list_databases);
        if ($query === false) {
            return $this->db->data_cache['db_names'];
        }

        for ($i = 0, $query = $query->resultArray(), $c = count($query); $i < $c; $i++) {
            $this->db->data_cache['db_names'][] = current($query[$i]);
        }

        return $this->db->data_cache['db_names'];
    }

    /**
     * Determine if a particular database exists
     *
     * @param   string  $database   Database name
     * @return  bool    TRUE if exists, otherwise FALSE
     */
    public function databaseExists($database)
    {
        return in_array($database, $this->listDatabases());
    }

    /**
     * Optimize Table
     *
     * @param   string  $table  Table name
     * @return  mixed   Result array on success, otherwise FALSE
     */
    public function optimizeTable($table)
    {
        if ($this->db_optimize_table === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        $query = $this->db->query(sprintf($this->db_optimize_table, $this->db->escapeIdentifiers($table)));
        if ($query !== false) {
            $query = $query->resultArray();
            return current($query);
        }

        return false;
    }

    /**
     * Optimize Database
     *
     * @return  mixed   Result array or TRUE on success, otherwise FALSE
     */
    public function optimizeDatabase()
    {
        if ($this->db_optimize_table === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        $result = array();
        foreach ($this->db->listTables() as $table) {
            $res = $this->db->query(sprintf($this->db_optimize_table, $this->db->escapeIdentifiers($table)));
            if (is_bool($res)) {
                return $res;
            }

            // Build the result array...
            $res = $res->resultArray();
            $res = current($res);
            $key = str_replace($this->db->database.'.', '', current($res));
            $keys = array_keys($res);
            unset($res[$keys[0]]);

            $result[$key] = $res;
        }

        return $result;
    }

    /**
     * Repair Table
     *
     * @param   string  $table  Table name
     * @return  mixed   Result array or TRUE on success, otherwise FALSE
     */
    public function repairTable($table)
    {
        if ($this->db_repair_table === false) {
            return $this->db->displayError('db_unsupported_feature');
        }

        $query = $this->db->query(sprintf($this->db_repair_table, $this->db->escapeIdentifiers($table)));
        if (is_bool($query)) {
            return $query;
        }

        $query = $query->resultArray();
        return current($query);
    }

    /**
     * Generate CSV from a query result object
     *
     * @param   object  $query      Query result object
     * @param   string  $delim      Delimiter
     * @param   string  $newline    Newline character
     * @param   string  $enclosure  Enclosure
     * @return  string  Result CSV
     */
    public function csvFromResult($query, $delim = ',', $newline = "\n", $enclosure = '"')
    {
        global $XY;

        if (!is_object($query) || !method_exists($query, 'listFields')) {
            $XY->showError('You must submit a valid result object');
        }

        // First generate the headings from the table column names
        $out = '';
        foreach ($query->listFields() as $name) {
            $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $name).$enclosure.$delim;
        }

        $out = substr(rtrim($out), 0, -strlen($delim)).$newline;

        // Next blast through the result array and build out the rows
        while ($row = $query->unbufferedRow('array')) {
            foreach ($row as $item) {
                $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $item).$enclosure.$delim;
            }
            $out = substr(rtrim($out), 0, -strlen($delim)).$newline;
        }

        return $out;
    }

    /**
     * Generate XML data from a query result object
     *
     * @param   object  $query  Query result object
     * @param   array   $params Any preferences
     * @return  string  Result XML
     */
    public function xmlFromResult($query, $params = array())
    {
        global $XY;

        if (!is_object($query) || !method_exists($query, 'list_fields')) {
            $XY->showError('You must submit a valid result object');
        }

        // Set our default values
        foreach (array('root' => 'root', 'element' => 'element', 'newline' => "\n", 'tab' => "\t") as $key => $val) {
            isset($params[$key]) || $params[$key] = $val;
        }

        // Create variables for convenience
        extract($params);

        // Load XML library
        $XY->load->library('xml');

        // Generate the result
        $xml = '<'.$root.'>'.$newline;
        while ($row = $query->unbufferedRow()) {
            $xml .= $tab.'<'.$element.'>'.$newline;
            foreach ($row as $key => $val) {
                $xml .= $tab.$tab.'<'.$key.'>'.$XY->xml->convert($val).'</'.$key.'>'.$newline;
            }
            $xml .= $tab.'</'.$element.'>'.$newline;
        }

        return $xml.'</'.$root.'>'.$newline;
    }

    /**
     * Database Backup
     *
     * @param   array   $params Parameters
     * @return  mixed   Backup file data on success, otherwise FALSE
     */
    public function backup($params = array())
    {
        // If the parameters have not been submitted as an
        // array then we know that it is simply the table
        // name, which is a valid short cut.
        if (is_string($params)) {
            $params = array('tables' => $params);
        }

        // Set up our default preferences
        $prefs = array(
            'tables' => array(),
            'ignore' => array(),
            'filename' => '',
            'format' => 'gzip', // gzip, zip, txt
            'add_drop' => true,
            'add_insert' => true,
            'newline' => "\n",
            'foreign_key_checks' => true
        );

        // Did the user submit any preferences? If so set them....
        if (count($params) > 0) {
            foreach ($prefs as $key => $val) {
                isset($params[$key]) && $prefs[$key] = $params[$key];
            }
        }

        // Are we backing up a complete database or individual tables?
        // If no table names were submitted we'll fetch the entire table list
        if (count($prefs['tables']) === 0) {
            $prefs['tables'] = $this->db->listTables();
        }

        // Validate the format
        if (!in_array($prefs['format'], array('gzip', 'zip', 'txt'), true)) {
            $prefs['format'] = 'txt';
        }

        // Is the encoder supported? If not, we'll either issue an
        // error or use plain text depending on the debug settings
        if (($prefs['format'] === 'gzip' && ! @function_exists('gzencode'))
        || ($prefs['format'] === 'zip' && ! @function_exists('gzcompress'))) {
            if ($this->db->db_debug) {
                return $this->db->displayError('db_unsupported_compression');
            }

            $prefs['format'] = 'txt';
        }

        // What format was requested?
        if ($prefs['format'] === 'zip') {
            // Set the filename if not provided (only needed with Zip files)
            if ($prefs['filename'] === '') {
                $prefs['filename'] = (count($prefs['tables']) === 1 ? $prefs['tables'] : $this->db->database)
                    .date('Y-m-d_H-i', time()).'.sql';
            }
            else {
                // If they included the .zip file extension we'll remove it
                if (preg_match('|.+?\.zip$|', $prefs['filename'])) {
                    $prefs['filename'] = str_replace('.zip', '', $prefs['filename']);
                }

                // Tack on the ".sql" file extension if needed
                if (!preg_match('|.+?\.sql$|', $prefs['filename'])) {
                    $prefs['filename'] .= '.sql';
                }
            }

            // Load the Zip class and output it
            $XY->load->library('zip');
            $XY->zip->add_data($prefs['filename'], $this->dbBackup($prefs));
            return $XY->zip->getZip();
        }
        elseif ($prefs['format'] === 'txt') {
            // Text file requested
            return $this->dbBackup($prefs);
        }
        elseif ($prefs['format'] === 'gzip') {
            // Gzip requested
            return gzencode($this->dbBackup($prefs));
        }

        return false;
    }

    /**
     * Platform-specific database backup
     *
     * @param   array   $params Parameters
     * @return  mixed   Backup file data on success, otherwise FALSE
     */
    abstract protected function dbBackup($params);
}

