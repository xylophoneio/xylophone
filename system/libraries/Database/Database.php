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
namespace Xylophone\libraries\Database;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Library Class
 *
 * This is the platform-independent base DB implementation class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database
 * @link        http://xylophone.io/user_guide/database/
 */
abstract class Database
{
    /** @var    string  Data Source Name / Connect string */
    public $dsn = '';

    /** @var    string  Username */
    public $username = '';

    /** @var    string  Password */
    public $password = '';

    /** @var    string  Hostname */
    public $hostname = '';

    /** @var    string  Database name */
    public $database = '';

    /** @var    string  Database driver name */
    public $driver = 'mysqli';

    /** @var    string  Subdriver name */
    public $subdriver = '';

    /** @var    string  Table prefix */
    public $dbprefix = '';

    /** @var    string  Character set */
    public $char_set = 'utf8';

    /** @var    string  Collation */
    public $dbcollat = 'utf8_general_ci';

    /** @var    bool    Whether to automatically initialize the DB connection */
    public $autoinit = true;

    /** @var    mixed   Encryption flag/data */
    public $encrypt = false;

    /** @var    string  Swap Prefix */
    public $swap_pre = '';

    /** @var    int     Database port */
    public $port = '';

    /** @var    bool    Persistent connection flag */
    public $pconnect = false;

    /** @var    object|resource     Connection ID */
    public $conn_id = null;

    /** @var    object|resource     Result ID */
    public $result_id = null;

    /** @var    bool    Whether to display error messages */
    public $db_debug = false;

    /** @var    int     Benchmark time */
    public $benchmark = 0;

    /** @var    int     Executed queries count */
    public $query_count = 0;

    /** @var    string  Character used to identify values in a prepared statement */
    public $bind_marker = '?';

    /** @var    bool    Whether to keep an in-memory history of queries for debugging purposes */
    public $save_queries = true;

    /** @var    array   Queries list */
    public $queries = array();

    /** @var    array   A list of times that queries took to execute  */
    public $query_times = array();

    /** @var    array   An internal generic value cache */
    public $data_cache = array();

    /** @var    bool    Transaction enabled flag */
    public $trans_enabled = true;

    /** @var    bool    Strict transaction mode flag */
    public $trans_strict = true;

    /** @var    int     Transaction depth level */
    protected $trans_depth = 0;

    /** @var    bool    Whether a transaction rollback should occur */
    protected $trans_status = true;

    /** @var    bool    Whether transactions are in test mode (auto-fail) */
    protected $trans_test = false;

    /** @var    bool    Cache On flag */
    public $cache_on = false;

    /** @var    bool    Cache directory path */
    public $cachedir = '';

    /** @var    bool    Cache auto-delete flag */
    public $cache_autodel = false;

    /** @var    object  DB Cache object */
    public $cache;

    /** @var    bool    Protect identifiers flag */
    protected $protect_identifiers = true;

    /** @var    array   List of reserved identifiers that must NOT be escaped */
    protected $reserved_identifiers = array('*');

    /** @var    string  Identifier escape character */
    protected $escape_char = '"';

    /** @var    string  ESCAPE statement string */
    protected $like_escape_str = " ESCAPE '%s' ";

    /** @var    string  ESCAPE character */
    protected $like_escape_chr = '!';

    /** @var    array   ORDER BY random keyword */
    protected $random_keyword = array('RAND()', 'RAND(%d)');

    /** @var    string  COUNT string */
    protected $count_string = 'SELECT COUNT(*) AS ';

    /** @var    array   Query string operators */
    protected $operators = array();

    /** @var    array   Configuration data */
    protected $config = array();

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

        // Check for connection
        $active = isset($extras['active_group']) ? $extras['active_group'] : 'default';
        if (!isset($config[$active]) || !is_array($config[$active])) {
            $XY->showError('Your active database connection group ('.$active_group.
                ') is not valid. Please check your config/database.php file.');
        }

        // Save original config and get active group
        $this->config = $config;
        $group =& $config[$active];

        // Check for DSN string
        if (isset($group['dsn']) && strpos($params, '://')) !== false && ($dsn = @parse_url($group['dsn']))) {
            // Valid DSN - set params
            $this->dsn = $group['dsn'];
            $this->driver = $dsn['scheme'];
            $this->hostname = isset($dsn['host']) ? rawurldecode($dsn['host']) : '';
            $this->port = isset($dsn['port']) ? rawurldecode($dsn['port']) : '';
            $this->username = isset($dsn['user']) ? rawurldecode($dsn['user']) : '';
            $this->password = isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '';
            $this->database = isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : '';
            if (isset($dsn['query'])) {
                parse_str($dsn['query'], $query);
                foreach ($query as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
        else {
            // Set params from active group
            foreach ($group as $key => $val) {
                $this->$key = $val;
            }
        }

        // Initialize unless disabled
        $this->autoinit && $this->initialize();

        $XY->logger->debug('Database Driver Class Initialized');
    }

    /**
     * Initialize Database Settings
     *
     * @return  void
     */
    public function initialize()
    {
        global $XY;

        // If an established connection is available, then there's no need to
        // connect and select the database. Depending on the database driver,
        // conn_id can be either TRUE, a resource, or an object.
        if ($this->conn_id) {
            return;
        }

        // Connect to the database and set the connection ID
        $this->conn_id = $this->dbConnect($this->pconnect);

        // No connection resource? Check if there is a failover else throw an error
        if (!$this->conn_id) {
            // Check if there is a failover set
            if (!empty($this->failover) && is_array($this->failover)) {
                // Go over all the failovers
                foreach ($this->failover as $failover) {
                    // Replace the current settings with those of the failover
                    foreach ($failover as $key => $val) {
                        $this->$key = $val;
                    }

                    // Try to connect
                    $this->conn_id = $this->dbConnect($this->pconnect);

                    // If a connection is made break the foreach loop
                    if ($this->conn_id) {
                        break;
                    }
                }
            }

            // We still don't have a connection?
            if (!$this->conn_id) {
                $XY->logger->error('Unable to connect to the database');
                return $this->displayError('db_unable_to_connect');
            }
        }

        // Now we set the character set and that's all
        return $this->setCharset($this->char_set);
    }

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout.
     *
     * This is just a dummy method to allow drivers without such
     * functionality to not declare it, while others will override it.
     *
     * @return  void
     */
    public function reconnect()
    {
        // Nothing to do here by default
    }

    /**
     * Select database
     *
     * This is just a dummy method to allow drivers without such
     * functionality to not declare it, while others will override it.
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function dbSelect()
    {
        return true;
    }

    /**
     * Set client character set
     *
     * @param   string  $charset    Charset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function setCharset($charset)
    {
        global $XY;

        if (!$this->dbSetCharset($charset)) {
            $XY->logger->error('Unable to set database connection charset: '.$charset);
            return $this->displayError('db_unable_to_set_charset', $charset);
        }

        return true;
    }

    /**
     * The name of the platform in use (mysql, mssql, etc...)
     *
     * @return  string  Driver name
     */
    public function platform()
    {
        return $this->driver;
    }

    /**
     * Database version number
     *
     * Returns a string containing the version of the database being used.
     * Most drivers will override this method.
     *
     * @return  string  Database version string
     */
    public function version()
    {
        if (isset($this->data_cache['version'])) {
            return $this->data_cache['version'];
        }

        if (($ver = $this->dbVersion()) === false) {
            return $this->displayError('db_unsupported_function');
        }

        return ($this->data_cache['version'] = $ver);
    }

    /**
     * Platform-specific version number string
     *
     * @return  string  Database version string
     */
    protected function dbVersion()
    {
        return $this->query('SELECT VERSION() AS ver')->row()->ver;
    }

    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query. Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @param   string  $sql    SQL string
     * @param   array   $binds  An array of binding data
     * @param   bool    $return Whether to return an object
     * @return  mixed   Result object on results, TRUE on success, otherwise FALSE
     */
    public function query($sql, $binds = false, $return = null)
    {
        global $XY;

        if ($sql === '') {
            $XY->logger->error('Invalid query: '.$sql);
            return $this->displayError('db_invalid_query');
        }
        elseif (!is_bool($return)) {
            $return = !$this->isWriteType($sql);
        }

        // Verify table prefix and replace if necessary
        if ($this->dbprefix !== '' && $this->swap_pre !== '' && $this->dbprefix !== $this->swap_pre) {
            $sql = preg_replace('/(\W)'.$this->swap_pre.'(\S+?)/', '\\1'.$this->dbprefix.'\\2', $sql);
        }

        // Compile binds if needed
        $binds && $sql = $this->compileBinds($sql, $binds);

        // Is query caching enabled? If the query is a "read type" we will load
        // the caching class and return the previously cached query if it exists
        if ($this->cache_on === true && $return === true && $this->cacheInit()) {
            $cache = $this->cache->read($sql);
            if ($cache !== false) {
                return $cache;
            }
        }

        // Save the query for debugging and start the query timer
        $this->save_queries && $this->queries[] = $sql;
        $time_start = microtime(true);

        // Run the Query
        if (($this->result_id = $this->simpleQuery($sql)) === false) {
            $this->save_queries && $this->query_times[] = 0;

            // This will trigger a rollback if transactions are being used
            $this->trans_status = false;

            // Grab the error now, as we might run some additional queries before displaying the error
            $error = $this->error();

            // Log errors
            $XY->logger->error('Query error: '.$error['message'].' - Invalid query: '.$sql);

            if ($this->db_debug) {
                // We call this function in order to roll-back queries
                // if transactions are enabled. If we don't call this here
                // the error message will trigger an exit, causing the
                // transactions to remain in limbo.
                if ($this->trans_depth !== 0) {
                    do {
                        $this->transComplete();
                    } while ($this->trans_depth !== 0);
                }

                // Display errors
                return $this->displayError(array('Error Number: '.$error['code'], $error['message'], $sql));
            }

            return false;
        }

        // Stop and aggregate the query time results
        $time_end = microtime(true);
        $this->benchmark += $time_end - $time_start;

        // Add query time and increment query counter
        $this->save_queries && $this->query_times[] = $time_end - $time_start;
        $this->query_count++;

        // Will we have a result object instantiated? If not - we'll simply return TRUE
        if ($return !== true) {
            // If caching is enabled we'll auto-cleanup any existing files related to this particular URI
            if ($this->cache_on === true && $this->cache_autodel === true && $this->cacheInit()) {
                $this->cache->delete();
            }

            return true;
        }

        // Load and instantiate the result driver
        $res = $XY->loadClass('Database/drivers/'.$this->driver.'/DbResult'.ucfirst($this->driver),
            'libraries', $this);

        // Is query caching enabled? If so, we'll serialize the
        // result object and save it to a cache file.
        if ($this->cache_on === true && $this->cacheInit()) {
            // Create a generic copy of the result object and cache it.
            $cached = $XY->loadClass('Database/DbResult', 'libraries');
            $cached->copy($res);
            $this->cache->write($sql, $cached);
        }

        return $res;
    }

    /**
     * Simple Query
     * This is a simplified version of the query() function. Internally
     * we only use it when running transaction commands since they do
     * not require all the features of the main query() function.
     *
     * @param   string  $sql    Query string
     * @return  mixed
     */
    public function simpleQuery($sql)
    {
        $this->conn_id || $this->initialize();
        return $this->dbExecute($sql);
    }

    /**
     * Disable Transactions
     * This permits transactions to be disabled at run-time.
     *
     * @return  void
     */
    public function transOff()
    {
        $this->trans_enabled = false;
    }

    /**
     * Enable/disable Transaction Strict Mode
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all groups will be rolled back.
     * If strict mode is disabled, each group is treated autonomously, meaning
     * a failure of one group will not affect any others
     *
     * @param   bool    $mode   Whether transactions are enabled or not
     * @return  void
     */
    public function transStrict($mode = true)
    {
        $this->trans_strict = is_bool($mode) ? $mode : true;
    }

    /**
     * Begin Transaction
     *
     * @param   bool    $test_mode  Whether to automatically fail out of transaction
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function transBegin($test_mode = false)
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled || $this->trans_depth > 0) {
            return true;
        }

        // Reset the transaction test flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->trans_test = (bool)$test_mode;

        return $this->dbTransBegin();
    }

    /**
     * Commit Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function transCommit()
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled || $this->trans_depth > 0) {
            return true;
        }

        return $this->dbTransCommit();
    }

    /**
     * Rollback Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function transRollback()
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled || $this->trans_depth > 0) {
            return true;
        }

        return $this->dbTransRollback();
    }

    /**
     * Start Transaction
     *
     * @param   bool    $test_mode  Whether to run in test mode
     * @return  void
     */
    public function transStart($test_mode = false)
    {
        if (!$this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->trans_depth > 0) {
            ++$this->trans_depth;
            return;
        }

        $this->transBegin($test_mode);
        ++$this->trans_depth;
    }

    /**
     * Complete Transaction
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function transComplete()
    {
        global $XY;

        if (!$this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->trans_depth > 1) {
            --$this->trans_depth;
            return true;
        }
        else {
            $this->trans_depth = 0;
        }

        // The query() function will set this flag to FALSE in the event that a query failed
        if (!$this->trans_status || $this->trans_test) {
            // Rollback
            $this->transRollback();

            // If we are NOT running in strict mode, we will reset the
            // trans_status flag to permit subsequent groups of transactions
            $this->trans_strict || $this->trans_status = true;
            $XY->logger->debug('DB Transaction Failure');
            return false;
        }

        $this->transCommit();
        return true;
    }

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function transStatus()
    {
        return $this->trans_status;
    }

    /**
     * Compile Bindings
     *
     * @param   string  $sql    Query string
     * @param   array   $binds  Array of bind data
     * @return  string  Bound query string
     */
    public function compileBinds($sql, $binds)
    {
        if (empty($binds) |||empty($this->bind_marker) || strpos($sql, $this->bind_marker) === false) {
            return $sql;
        }
        elseif (!is_array($binds)) {
            $binds = array($binds);
            $bind_count = 1;
        }
        else {
            // Make sure we're using numeric keys
            $binds = array_values($binds);
            $bind_count = count($binds);
        }

        // We'll need the marker length later
        $ml = strlen($this->bind_marker);

        // Make sure not to replace a chunk inside a string that happens to match the bind marker
        if ($c = preg_match_all("/'[^']*'/i", $sql, $matches)) {
            $c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i',
                str_replace($matches[0], str_replace($this->bind_marker, str_repeat(' ', $ml), $matches[0]), $sql, $c),
                $matches, PREG_OFFSET_CAPTURE);

            // Bind values' count must match the count of markers in the query
            if ($bind_count !== $c) {
                return $sql;
            }
        }
        elseif (($c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count) {
            return $sql;
        }

        do {
            --$c;
            $sql = substr_replace($sql, $this->escape($binds[$c]), $matches[0][$c][1], $ml);
        } while ($c !== 0);

        return $sql;
    }

    /**
     * Determines if a query is a "write" type.
     *
     * @param   string  $sql    Query string
     * @return  bool    TRUE if write query, otherwise FALSE
     */
    public function isWriteType($sql)
    {
        return (bool)preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s+/i', $sql);
    }

    /**
     * Calculate the aggregate query elapsed time
     *
     * @param   int     $decimals   Number of decimal places
     * @return  int     Elapsed time
     */
    public function elapsedTime($decimals = 6)
    {
        return number_format($this->benchmark, $decimals);
    }

    /**
     * Returns the total number of queries
     *
     * @return  int     Number of queries
     */
    public function totalQueries()
    {
        return $this->query_count;
    }

    /**
     * Returns the last query that was executed
     *
     * @return  string  Query string
     */
    public function lastQuery()
    {
        return end($this->queries);
    }

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @param   string  $str    String to escape
     * @return  mixed   Escaped string, boolean int, or 'NULL' string
     */
    public function escape($str)
    {
        if (is_string($str) or (is_object($str) && method_exists($str, '__toString'))) {
            return "'".$this->escapeStr($str)."'";
        }
        elseif (is_bool($str)) {
            return ($str === false) ? 0 : 1;
        }
        elseif ($str === null) {
            return 'NULL';
        }

        return $str;
    }

    /**
     * Escape String
     *
     * @param   mixed   $str    String or array of strings to escape
     * @param   bool    $like   Whether or not the string will be used in a LIKE condition
     * @return  mixed   Escaped string or array of strings
     */
    public function escapeStr($str, $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escapeStr($val, $like);
            }

            return $str;
        }

        $str = $this->dbEscapeStr($str);

        // Escape LIKE condition wildcards
        if ($like === true) {
            return str_replace(array($this->like_escape_chr, '%', '_'), array(
                $this->like_escape_chr.$this->like_escape_chr,
                $this->like_escape_chr.'%',
                $this->like_escape_chr.'_'), $str);
        }

        return $str;
    }

    /**
     * Escape LIKE String
     *
     * Calls the individual driver for platform
     * specific escaping for LIKE conditions
     *
     * @param   mixed   $str    String or array of strings to escape
     * @return  mixed   Escaped string or array of strings
     */
    public function escapeLikeStr($str)
    {
        return $this->escapeStr($str, true);
    }

    /**
     * Platform-dependant string escape
     *
     * @param   string  $str    String to escape
     * @return  string  Escaped string
     */
    protected function dbEscapeStr($str)
    {
        global $XY;
        return str_replace("'", "''", $XY->output->removeInvisibleCharacters($str));
    }

    /**
     * Primary
     *
     * Retrieves the primary key. It assumes that the row in the first
     * position is the primary key
     *
     * @param   string  $table  Table name
     * @return  string  Primary key name
     */
    public function primary($table = '')
    {
        $fields = $this->listFields($table);
        return is_array($fields) ? current($fields) : false;
    }

    /**
     * "Count All" query
     *
     * Generates a platform-specific query string that counts all records in
     * the specified database
     *
     * @param   string  $table  Table name
     * @return  int     Record count
     */
    public function countAll($table = '')
    {
        if ($table === '') {
            return 0;
        }

        $query = $this->query($this->count_string.$this->escapeIdentifiers('numrows').
            ' FROM '.$this->protectIdentifiers($table, true, null, false));
        if ($query->num_rows() === 0) {
            return 0;
        }

        $query = $query->row();
        $this->resetSelect();
        return (int)$query->numrows;
    }

    /**
     * Returns an array of table names
     *
     * @param   string  $prefix_limit   Whether to limit by prefix
     * @return  array   Table names
     */
    public function listTables($prefix_limit = false)
    {
        // Is there a cached result?
        if (isset($this->data_cache['table_names'])) {
            return $this->data_cache['table_names'];
        }

        if (($sql = $this->dbListTables($prefix_limit)) === false) {
            return $this->displayError('db_unsupported_function');
        }

        $this->data_cache['table_names'] = array();
        $query = $this->query($sql);

        foreach ($query->resultArray() as $row) {
            // Do we know from which column to get the table name?
            if (!isset($key)) {
                if (isset($row['table_name'])) {
                    $key = 'table_name';
                }
                elseif (isset($row['TABLE_NAME'])) {
                    $key = 'TABLE_NAME';
                }
                else {
                    // We have no other choice but to just get the first element's key.
                    // Due to array_shift() accepting its argument by reference, if
                    // E_STRICT is on, this would trigger a warning. So we'll have to
                    // assign it first.
                    $key = array_keys($row);
                    $key = array_shift($key);
                }
            }

            $this->data_cache['table_names'][] = $row[$key];
        }

        return $this->data_cache['table_names'];
    }

    /**
     * Determine if a particular table exists
     *
     * @param   string  $table_name Table name
     * @return  bool    TRUE if exists, otherwise FALSE
     */
    public function tableExists($table_name)
    {
        return in_array($this->protectIdentifiers($table_name, true, false, false), $this->listTables());
    }

    /**
     * Fetch Field Names
     *
     * @param   string  $table  Table name
     * @return  array   Field names
     */
    public function listFields($table = '')
    {
        // Is there a cached result?
        if (isset($this->data_cache['field_names'][$table])) {
            return $this->data_cache['field_names'][$table];
        }

        if ($table === '') {
            return $this->displayError('db_field_param_missing');
        }

        if (($sql = $this->dbListFields($table)) === false) {
            return $this->displayError('db_unsupported_function');
        }

        $query = $this->query($sql);
        $this->data_cache['field_names'][$table] = array();

        foreach ($query->resultArray() as $row) {
            // Do we know from where to get the column's name?
            if (!isset($key)) {
                if (isset($row['column_name'])) {
                    $key = 'column_name';
                }
                elseif (isset($row['COLUMN_NAME'])) {
                    $key = 'COLUMN_NAME';
                }
                else {
                    // We have no other choice but to just get the first element's key.
                    $key = key($row);
                }
            }

            $this->data_cache['field_names'][$table][] = $row[$key];
        }

        return $this->data_cache['field_names'][$table];
    }

    /**
     * Determine if a particular field exists
     *
     * @param   string  $field_name Field name
     * @param   string  $table_name Table name
     * @return  bool    TRUE if exists, otherwise FALSE
     */
    public function fieldExists($field_name, $table_name)
    {
        return in_array($field_name, $this->listFields($table_name));
    }

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param   mixed   $item   Item to escape
     * @return  mixed   Escaped item
     */
    public function escapeIdentifiers($item)
    {
        if ($this->escape_char === '' || empty($item) || in_array($item, $this->reserved_identifiers)) {
            return $item;
        }
        elseif (is_array($item)) {
            foreach ($item as $key => $value) {
                $item[$key] = $this->escapeIdentifiers($value);
            }

            return $item;
        }
        // Avoid breaking functions and literal values inside queries
        elseif (ctype_digit($item) || $item[0] === "'" || ($this->escape_char !== '"' && $item[0] === '"') || strpos($item, '(') !== false) {
            return $item;
        }

        static $preg_ec = array();

        if (empty($preg_ec)) {
            if (is_array($this->escape_char)) {
                $preg_ec = array(
                    preg_quote($this->escape_char[0], '/'),
                    preg_quote($this->escape_char[1], '/'),
                    $this->escape_char[0],
                    $this->escape_char[1]
                );
            }
            else {
                $preg_ec[0] = $preg_ec[1] = preg_quote($this->escape_char, '/');
                $preg_ec[2] = $preg_ec[3] = $this->escape_char;
            }
        }

        foreach ($this->reserved_identifiers as $id) {
            if (strpos($item, '.'.$id) !== false) {
                return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?\./i', $preg_ec[2].'$1'.$preg_ec[3].'.', $item);
            }
        }

        return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?(\.)?/i', $preg_ec[2].'$1'.$preg_ec[3].'$2', $item);
    }

    /**
     * Generate an insert string
     *
     * @param   string  $table  Table name
     * @param   array   $data   Array data of key/values
     * @return  string  Query string
     */
    public function insertString($table, $data)
    {
        $fields = $values = array();

        foreach ($data as $key => $val) {
            $fields[] = $this->escapeIdentifiers($key);
            $values[] = $this->escape($val);
        }

        return $this->dbInsert($this->protectIdentifiers($table, true, null, false), $fields, $values);
    }

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @param   string  $table  Table name
     * @param   array   $keys   Insert keys
     * @param   array   $values Insert values
     * @return  string  Query string
     */
    protected function dbInsert($table, $keys, $values)
    {
        return 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
    }

    /**
     * Generate an update string
     *
     * @param   string  $table  Table name
     * @param   array   $data   Array data of key/values
     * @param   mixed   $where  The "where" statement
     * @return  string  Query string
     */
    public function updateString($table, $data, $where)
    {
        if (empty($where)) {
            return false;
        }

        $this->where($where);

        $fields = array();
        foreach ($data as $key => $val) {
            $fields[$this->protect_identifiers($key)] = $this->escape($val);
        }

        $sql = $this->dbUpdate($this->protectIdentifiers($table, true, null, false), $fields);
        $this->resetWrite();
        return $sql;
    }

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param   string  $table  Table name
     * @param   array   $values Update data
     * @return  string  Query string
     */
    protected function dbUpdate($table, $values)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key.' = '.$val;
        }

        return 'UPDATE '.$table.' SET '.implode(', ', $valstr).
            $this->compileWh('qb_where').
            $this->compileOrderBy().
            ($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');
    }

    /**
     * Tests whether the string has an SQL operator
     *
     * @param   string  $str    String to test
     * @return  bool    TRUE if has operator, otherwise FALSE
     */
    protected function hasOperator($str)
    {
        return (bool)preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i', trim($str));
    }

    /**
     * Returns the SQL string operator
     *
     * @param   string  $str    String to check
     * @return  string  Operator
     */
    protected function getOperator($str)
    {
        if (empty($this->operators)) {
            $_les = ($this->like_escape_str !== '') ?
                '\s+'.preg_quote(trim(sprintf($this->like_escape_str, $this->like_escape_chr)), '/') : '';
            $this->operators = array(
                '\s*(?:<|>|!)?=\s*',            // =, <=, >=, !=
                '\s*<>?\s*',                    // <, <>
                '\s*>\s*',                      // >
                '\s+IS NULL',                   // IS NULL
                '\s+IS NOT NULL',               // IS NOT NULL
                '\s+EXISTS\s*\([^\)]+\)',       // EXISTS(sql)
                '\s+NOT EXISTS\s*\([^\)]+\)',   // NOT EXISTS(sql)
                '\s+BETWEEN\s+\S+\s+AND\s+\S+', // BETWEEN value AND value
                '\s+IN\s*\([^\)]+\)',           // IN(list)
                '\s+NOT IN\s*\([^\)]+\)',       // NOT IN (list)
                '\s+LIKE\s+\S+'.$_les,          // LIKE 'expr'[ ESCAPE '%s']
                '\s+NOT LIKE\s+\S+'.$_les       // NOT LIKE 'expr'[ ESCAPE '%s']
            );
        }

        return preg_match('/'.implode('|', $this->operators).'/i', $str, $match) ? $match[0] : false;
    }

    /**
     * Enables a native PHP function to be run, using a platform agnostic wrapper.
     *
     * @param   string  $function   Function name
     * @return  mixed   Function result
     */
    public function callFunction($function)
    {
        $driver = ($this->driver === 'postgre') ? 'pg_' : $this->driver.'_';

        if (strpos($driver, $function) === false) {
            $function = $driver.$function;
        }

        if (!function_exists($function)) {
            return $this->displayError('db_unsupported_function');
        }

        return (func_num_args() > 1) ? call_user_func_array($function, array_slice(func_get_args(), 1)) :
            call_user_func($function);
    }

    /**
     * Set Cache Directory Path
     *
     * @param   string  $path   Path to the cache directory
     * @return  void
     */
    public function cacheSetPath($path = '')
    {
        $this->cachedir = $path;
    }

    /**
     * Enable Query Caching
     *
     * @return  bool    TRUE for enabled
     */
    public function cacheOn()
    {
        return $this->cache_on = true;
    }

    /**
     * Disable Query Caching
     *
     * @return  bool    FALSE for disabled
     */
    public function cacheOff()
    {
        return $this->cache_on = false;
    }

    /**
     * Delete the cache files associated with a particular URI
     *
     * @param   string  $segment_one    First URI segment
     * @param   string  $segment_two    Second URI segment
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function cacheDelete($segment_one = '', $segment_two = '')
    {
        return ($this->cacheInit()) ? $this->cache->delete($segment_one, $segment_two) : false;
    }

    /**
     * Delete All cache files
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function cacheDeleteAll()
    {
        return ($this->cacheInit()) ? $this->cache->deleteAll() : false;
    }

    /**
     * Initialize the Cache Class
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    protected function cacheInit()
    {
        global $XY;

        if (is_object($this->cache)) {
            return true;
        }

        $this->cache = $XY->loadClass('Database/DbCache', 'libraries', $this);
        if ($this->cache === null) {
            return $this->cacheOff();
        }

        return true;
    }

    /**
     * Close DB Connection
     *
     * @return  void
     */
    public function close()
    {
        if ($this->conn_id) {
            $this->dbClose();
            $this->conn_id = false;
        }
    }

    /**
     * Close DB Connection
     *
     * This method would be overriden by most of the drivers.
     *
     * @return  void
     */
    protected function dbClose()
    {
        $this->conn_id = false;
    }

    /**
     * Display an error message
     *
     * @param   string  $error  Error message
     * @param   string  $swap   Any "swap" values
     * @param   bool    $native Whether to localize the message
     * @return  string  The application/views/errors/error_db.php template
     */
    public function displayError($error = '', $swap = '', $native = false)
    {
        global $XY;

        if (!$this->db_debug) {
            return false;
        }

        $XY->lang->load('db');

        $heading = $XY->lang->line('db_error_heading');

        if ($native === true) {
            $message = (array)$error;
        }
        else {
            $message = is_array($error) ? $error : array(str_replace('%s', $swap, $LANG->line($error)));
        }

        // Find the most likely culprit of the error by going through
        // the backtrace until the source file is no longer in the
        // database folder.
        $trace = debug_backtrace();
        foreach ($trace as $call) {
            if (isset($call['file'], $call['class'])) {
                // We'll need this on Windows, as APPPATH and BASEPATH will always use forward slashes
                if (DIRECTORY_SEPARATOR !== '/') {
                    $call['file'] = str_replace('\\', '/', $call['file']);
                }

                if (strpos($call['file'], $XY->system_path.'libraries/Database') === false &&
                strpos($call['class'], 'Loader') === false) {
                    // Found it - use a relative path for safety
                    $message[] = 'Filename: '.str_replace(array($XY->app_path, $XY->system_path), '', $call['file']);
                    $message[] = 'Line Number: '.$call['line'];
                    break;
                }
            }
        }

        // TODO: make this exit with EXIT_DATABASE
        $XY->loadClass('Exceptions', 'core')->showError($heading, $message, 'error_db');
    }

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param   string  $item       Item to protect
     * @param   bool    $pre_single Whether to prefix single identifiers
     * @param   mixed   $escape     Whether to escape identifiers - default to config setting
     * @param   bool    $exists     Whether the field exists
     * @return  string
     */
    public function protectIdentifiers($item, $pre_single = false, $escape = null, $exists = true)
    {
        if (!is_bool($escape)) {
            $escape = $this->protect_identifiers;
        }

        if (is_array($item)) {
            $escaped_array = array();
            foreach ($item as $k => $v) {
                $escaped_array[$this->protect_identifiers($k)] =
                    $this->protectIdentifiers($v, $pre_single, $escape, $exists);
            }

            return $escaped_array;
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix. There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        //
        // Added exception for single quotes as well, we don't want to alter
        // literal strings. -- Narf
        if (strpos($item, '(') !== false || strpos($item, "'") !== false) {
            return $item;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/\s+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Note: strripos() is used in order to support spaces in table names
        if ($offset = strripos($item, ' AS ')) {
            $alias = $escape ? substr($item, $offset, 4).$this->escapeIdentifiers(substr($item, $offset + 4)) :
                substr($item, $offset);
            $item = substr($item, 0, $offset);
        }
        elseif ($offset = strrpos($item, ' ')) {
            $alias = $escape ? ' '.$this->escapeIdentifiers(substr($item, $offset + 1)) : substr($item, $offset);
            $item = substr($item, 0, $offset);
        }
        else {
            $alias = '';
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== false) {
            $parts = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified? If so,
            // we have nothing more to do other than escape the item
            if (in_array($parts[0], $this->qb_aliased_tables)) {
                if ($escape) {
                    foreach ($parts as $key => $val) {
                        if (!in_array($val, $this->reserved_identifiers)) {
                            $parts[$key] = $this->escapeIdentifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }

                return $item.$alias;
            }

            // Is there a table prefix defined in the config file? If not, no need to do anything
            if ($this->dbprefix !== '') {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3])) {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif (isset($parts[2])) {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if (!$exists) {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre !== '' && strpos($parts[$i], $this->swap_pre) === 0) {
                    $parts[$i] = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $parts[$i]);
                }
                // We only add the table prefix if it does not already exist
                elseif (strpos($parts[$i], $this->dbprefix) !== 0) {
                    $parts[$i] = $this->dbprefix.$parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($escape) {
                $item = $this->escapeIdentifiers($item);
            }

            return $item.$alias;
        }

        // Is there a table prefix? If not, no need to insert it
        if ($this->dbprefix !== '') {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre !== '' && strpos($item, $this->swap_pre) === 0) {
                $item = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $item);
            }
            // Do we prefix an item with no segments?
            elseif ($pre_single === true && strpos($item, $this->dbprefix) !== 0) {
                $item = $this->dbprefix.$item;
            }
        }

        if ($escape && !in_array($item, $this->reserved_identifiers)) {
            $item = $this->escapeIdentifiers($item);
        }

        return $item.$alias;
    }

    /**
     * Dummy method that allows Query Builder class to be disabled
     * and keep count_all() working.
     *
     * @return  void
     */
    protected function resetSelect()
    {
        // Nothing to do here by default
    }

    /**
     * Connect to database
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   bool    $persistent Whether to make persistent connection
     * @return  object  Database connection object
     */
    abstract protected function dbConnect($persistent = false);

    /**
     * Returns an object with field data
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   string  $table  Table name
     * @return  object  Field data
     */
    abstract public function fieldData($table = '');

    /**
     * Begin transaction
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function dbTransBegin();

    /**
     * Commit transaction
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function dbTransCommit();

    /**
     * Rollback transaction
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract public function dbTransRollback();

    /**
     * Number of rows affected by write query
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @return  int     Number of affected rows
     */
    abstract public function affectedRows();

    /**
     * Insert ID
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @return  int     Row ID of last inserted row
     */
    abstract public function insertId();

    /**
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occured.
     *
     * @return  array   Error information
     */
    abstract public function error();

    /**
     * Set client character set
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   string  $charset    Charset
     * @return  bool    TRUE on success, otherwise FALSE
     */
    abstract protected function dbSetCharset($charset);

    /**
     * Execute the query
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   string  $sql    SQL query
     * @return  mixed   Result resource when results, TRUE on succes, otherwise FALSE
     */
    abstract protected function dbExecute($sql);

    /**
     * List database tables
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   bool    $prefix_limit   Whether to limit by database prefix
     * @return  string  Table listing
     */
    abstract protected function dbListTables($prefix_limit = false);

    /**
     * List database table fields
     *
     * This abstract method MUST be overridden by the driver class.
     *
     * @param   string  $table  Table name
     * @return  string  Table field listing
     */
    abstract protected function dbListFields($table = '');
}

