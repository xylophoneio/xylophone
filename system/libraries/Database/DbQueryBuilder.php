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
 * Database Query Builder Class
 *
 * This is the platform-independent base Query Builder implementation class.
 *
 * @package     Xylophone
 * @subpackage  libraries/Database
 * @link        http://xylophone.io/user_guide/database/
 */
class DbQueryBuilder extends Database
{
    /** @var    bool    Return DELETE SQL flag */
    protected $return_delete_sql = false;

    /** @var    bool    Reset DELETE data flag */
    protected $reset_delete_data = false;

    /** @var    array   SELECT data */
    protected $qb_select = array();

    /** @var    bool    DISTINCT flag */
    protected $qb_distinct = false;

    /** @var    array FROM data */
    protected $qb_from = array();

    /** @var    array   JOIN data */
    protected $qb_join = array();

    /** @var    array   WHERE data */
    protected $qb_where = array();

    /** @var    array   GROUP BY data */
    protected $qb_groupby = array();

    /** @var    array   HAVING data */
    protected $qb_having = array();

    /** @var    array   Keys */
    protected $qb_key = array();

    /** @var    int     LIMIT data */
    protected $qb_limit = false;

    /** @var    int     OFFSET data */
    protected $qb_offset = false;

    /** @var    array   ORDER BY data */
    protected $qb_orderby = array();

    /** @var    array   Data sets */
    protected $qb_set = array();

    /** @var    array   Aliased tables list */
    protected $qb_aliased_tables = array();

    /** @var    bool    WHERE group started flag */
    protected $qb_where_group_started = false;

    /** @var    int     WHERE group count */
    protected $qb_where_group_count = 0;

    /** @var    array   No escape data */
    protected $qb_no_escape = array();

    /** @var    array   Literal escape characters */
    protected $qb_literals = array();

    /** @var    bool    Caching flag */
    protected $qb_caching = false;

    /** @var    array   Cache exists list */
    protected $qb_cache_exists = array();

    /** @var    array   Cache SELECT data */
    protected $qb_cache_select = array();

    /** @var    array   Cache FROM data */
    protected $qb_cache_from = array();

    /** @var    array   Cache JOIN data */
    protected $qb_cache_join = array();

    /** @var    array   Cache WHERE data */
    protected $qb_cache_where = array();

    /** @var    array   Cache GROUP BY data */
    protected $qb_cache_groupby = array();

    /** @var    array   Cache HAVING data */
    protected $qb_cache_having = array();

    /** @var    array   Cache ORDER BY data */
    protected $qb_cache_orderby = array();

    /** @var    array   Cache data sets */
    protected $qb_cache_set = array();

    /** @var    array   Cache no escape data */
    protected $qb_cache_no_escape = array();

    /**
     * Select
     *
     * Generates the SELECT portion of the query
     *
     * @param   mixed   $select SELECT string or array of field names
     * @param   mixed
     * @return  object  This object
     */
    public function select($select = '*', $escape = null)
    {
        is_string($select) && $select = explode(',', $select);

        // If the escape value was not set will will base it on the global setting
        is_bool($escape) || $escape = $this->protect_identifiers;

        foreach ($select as $val) {
            $val = trim($val);

            if ($val !== '') {
                $this->qb_select[] = $val;
                $this->qb_no_escape[] = $escape;

                if ($this->qb_caching) {
                    $this->qb_cache_select[] = $val;
                    $this->qb_cache_exists[] = 'select';
                    $this->qb_cache_no_escape[] = $escape;
                }
            }
        }

        return $this;
    }

    /**
     * Select Max
     *
     * Generates a SELECT MAX(field) portion of a query
     *
     * @param   string  $select Field name
     * @param   string  $alias  Field alias
     * @return  object  This object
     */
    public function selectMax($select = '', $alias = '')
    {
        return $this->minMaxAvgSum($select, $alias, 'MAX');
    }

    /**
     * Select Min
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @param   string  $select Field name
     * @param   string  $alias  Field alias
     * @return  object  This object
     */
    public function selectMin($select = '', $alias = '')
    {
        return $this->minMaxAvgSum($select, $alias, 'MIN');
    }

    /**
     * Select Average
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @param   string  $select Field name
     * @param   string  $alias  Field alias
     * @return  object  This object
     */
    public function selectAvg($select = '', $alias = '')
    {
        return $this->minMaxAvgSum($select, $alias, 'AVG');
    }

    /**
     * Select Sum
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @param   string  $select Field name
     * @param   string  $alias  Field alias
     * @return  object  This object
     */
    public function selectSum($select = '', $alias = '')
    {
        return $this->minMaxAvgSum($select, $alias, 'SUM');
    }

    /**
     * SELECT [MAX|MIN|AVG|SUM]()
     *
     * @used-by DbQueryBuilder::selectMax()
     * @used-by DbQueryBuilder::selectMin()
     * @used-by DbQueryBuilder::selectAvg()
     * @used-by DbQueryBuilder::selectSum()
     *
     * @param   string  $select Field name
     * @param   string  $alias  Field alias
     * @param   string  $type   Function type
     * @return  object  This object
     */
    protected function minMaxAvgSum($select = '', $alias = '', $type = 'MAX')
    {
        global $XY;

        if (!is_string($select) || $select === '') {
            $this->displayError('db_invalid_query');
        }

        $type = strtoupper($type);
        in_array($type, array('MAX', 'MIN', 'AVG', 'SUM')) || $XY->showError('Invalid function type: '.$type);

        $alias === '' && $alias = $this->createAliasFromTable(trim($select));
        $sql = $type.'('.$this->protectIdentifiers(trim($select)).') AS '.
            $this->escapeIdentifiers(trim($alias));

        $this->qb_select[] = $sql;
        $this->qb_no_escape[] = null;

        if ($this->qb_caching) {
            $this->qb_cache_select[] = $sql;
            $this->qb_cache_exists[] = 'select';
        }

        return $this;
    }

    /**
     * Determines the alias name based on the table
     *
     * @used-by DbQueryBuilder::minMaxAvgSum()
     *
     * @param   string  $item   Item to alias
     * @return  string  Alias
     */
    protected function createAliasFromTable($item)
    {
        if (strpos($item, '.') !== false) {
            $item = explode('.', $item);
            return end($item);
        }

        return $item;
    }

    /**
     * DISTINCT
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @param   bool    $val    Whether to add distinct or not
     * @return  object  This object
     */
    public function distinct($val = true)
    {
        $this->qb_distinct = is_bool($val) ? $val : true;
        return $this;
    }

    /**
     * FROM
     *
     * Generates the FROM portion of the query
     *
     * @param   mixed   $from   Table name or array of names
     * @return  object  This object
     */
    public function from($from)
    {
        foreach ((array)$from as $val) {
            $vals = strpos($val, ',') === false ? array($val) : explode(',', $val);
            foreach ($vals as $v) {
                $v = trim($v);
                $this->trackAliases($v);

                $this->qb_from[] = $v = $this->protectIdentifiers($v, true, null, false);

                if ($this->qb_caching) {
                    $this->qb_cache_from[] = $v;
                    $this->qb_cache_exists[] = 'from';
                }
            }
        }

        return $this;
    }

    /**
     * JOIN
     *
     * Generates the JOIN portion of the query
     *
     * @param   string  $table  Table name
     * @param   string  $cond   Join condition
     * @param   string  $type   Type of join
     * @param   string  $escape Whether to escape identifiers
     * @return  object  This object
     */
    public function join($table, $cond, $type = '', $escape = null)
    {
        if ($type !== '') {
            $type = strtoupper(trim($type));
            if (!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), true)) {
                $type = '';
            }
            else {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist. We use this information
        // in the protect_identifiers to know whether to add a table prefix
        $this->trackAliases($table);

        is_bool($escape) || $escape = $this->protect_identifiers;

        // Split multiple conditions
        if ($escape === true && preg_match_all('/\sAND\s|\sOR\s/i', $cond, $m, PREG_OFFSET_CAPTURE)) {
            $newcond = '';
            $m[0][] = array('', strlen($cond));

            for ($i = 0, $c = count($m[0]), $s = 0; $i < $c; $s = $m[0][$i][1] + strlen($m[0][$i][0]), $i++) {
                $temp = substr($cond, $s, ($m[0][$i][1] - $s));
                $newcond .= preg_match("/([\[\]\w\.'-]+)(\s*[^\"\[`'\w]+\s*)(.+)/i", $temp, $match)
                    ? $this->protectIdentifiers($match[1]).$match[2].$this->protectIdentifiers($match[3])
                    : $temp;
                $newcond .= $m[0][$i][0];
            }

            $cond = ' ON '.$newcond;
        }
        // Split apart the condition and protect the identifiers
        elseif ($escape === true && preg_match("/([\[\]\w\.'-]+)(\s*[^\"\[`'\w]+\s*)(.+)/i", $cond, $match)) {
            $cond = ' ON '.$this->protectIdentifiers($match[1]).$match[2].$this->protectIdentifiers($match[3]);
        }
        elseif (!$this->hasOperator($cond)) {
            $cond = ' USING ('.($escape ? $this->escapeIdentifiers($cond) : $cond).')';
        }
        else {
            $cond = ' ON '.$cond;
        }

        // Do we want to escape the table name?
        if ($escape === true) {
            $table = $this->protectIdentifiers($table, true, null, false);
        }

        // Assemble the JOIN statement
        $this->qb_join[] = $join = $type.'JOIN '.$table.$cond;

        if ($this->qb_caching) {
            $this->qb_cache_join[] = $join;
            $this->qb_cache_exists[] = 'join';
        }

        return $this;
    }

    /**
     * WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   mixed   $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function where($key, $value = null, $escape = null)
    {
        return $this->andOrWhereHaving('qb_where', $key, $value, 'AND ', $escape);
    }

    /**
     * OR WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   mixed   $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orWhere($key, $value = null, $escape = null)
    {
        return $this->andOrWhereHaving('qb_where', $key, $value, 'OR ', $escape);
    }

    /**
     * WHERE, HAVING
     *
     * @used-by DbQueryBuilder::where()
     * @used-by DbQueryBuilder::orWhere()
     * @used-by DbQueryBuilder::having()
     * @used-by DbQueryBuilder::orHaving()
     *
     * @param   string  $qb_key 'qb_where' or 'qb_having'
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   mixed   $value  Value
     * @param   string  $type   Connection type: 'OR ' or 'AND '
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    protected function andOrWhereHaving($qb_key, $key, $value = null, $type = 'AND ', $escape = null)
    {
        $qb_cache_key = ($qb_key === 'qb_having') ? 'qb_cache_having' : 'qb_cache_where';

        is_array($key) || $key = array($key => $value);

        // If the escape value was not set will will base it on the global setting
        is_bool($escape) || $escape = $this->protect_identifiers;

        foreach ($key as $k => $v) {
            $prefix = (count($this->$qb_key) === 0 && count($this->$qb_cache_key) === 0)
                ? $this->groupGetType('')
                : $this->groupGetType($type);

            if ($v !== null) {
                $escape === true && $v = ' '.$this->escape($v);
                $this->hasOperator($k) || $k .= ' = ';
            }
            elseif (!$this->hasOperator($k)) {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            }

            $this->{$qb_key}[] = array('condition' => $prefix.$k.$v, 'escape' => $escape);
            if ($this->qb_caching) {
                $this->$qb_cache_key[] = array('condition' => $prefix.$k.$v, 'escape' => $escape);
                $this->qb_cache_exists[] = substr($qb_key, 3);
            }
        }

        return $this;
    }

    /**
     * WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param   string  $key    Field name
     * @param   array   $values Values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function whereIn($key = null, $values = null, $escape = null)
    {
        return $this->andOrWhereNotIn($key, $values, false, 'AND ', $escape);
    }

    /**
     * OR WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param   string  $key    Field name
     * @param   array   $values Values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orWhereIn($key = null, $values = null, $escape = null)
    {
        return $this->andOrWhereNotIn($key, $values, false, 'OR ', $escape);
    }

    /**
     * WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param   string  $key    Field name
     * @param   array   $values Values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function whereNotIn($key = null, $values = null, $escape = null)
    {
        return $this->andOrWhereNotIn($key, $values, true, 'AND ', $escape);
    }

    /**
     * OR WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param   string  $key    Field name
     * @param   array   $values Values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orWhereNotIn($key = null, $values = null, $escape = null)
    {
        return $this->andOrWhereNotIn($key, $values, true, 'OR ', $escape);
    }

    /**
     * Internal WHERE IN
     *
     * @used-by DbQueryBuilder::whereIn()
     * @used-by DbQueryBuilder::orWhereIn()
     * @used-by DbQueryBuilder::whereNotIn()
     * @used-by DbQueryBuilder::orWhereNotIn()
     *
     * @param   string  $key    Field name
     * @param   array   $values Values
     * @param   bool    $not    Whether to add NOT operator
     * @param   string  $type   Connection type: 'OR ' or 'AND '
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    protected function andOrWhereNotIn($key = null, $values = null, $not = false, $type = 'AND ', $escape = null)
    {
        if ($key === null || $values === null) {
            return $this;
        }

        is_array($values) || $values = array($values);
        is_bool($escape) || $escape = $this->protect_identifiers;

        $not = $not ? ' NOT' : '';

        $where_in = array();
        foreach ($values as $value) {
            $where_in[] = $this->escape($value);
        }

        $prefix = (count($this->qb_where) === 0) ? $this->groupGetType('') : $this->groupGetType($type);
        $where_in = array(
            'condition' => $prefix.$key.$not.' IN('.implode(', ', $where_in).')',
            'escape' => $escape
        );

        $this->qb_where[] = $where_in;
        if ($this->qb_caching) {
            $this->qb_cache_where[] = $where_in;
            $this->qb_cache_exists[] = 'where';
        }

        return $this;
    }

    /**
     * LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param   mixed   $field  Field name or array of field/match pairs
     * @param   string  $match  String to match against
     * @param   string  $side   Side for wildcards: 'both', 'none', 'before', 'after'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function like($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->andOrNotLike($field, $match, 'AND ', $side, '', $escape);
    }

    /**
     * NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param   mixed   $field  Field name or array of field/match pairs
     * @param   string  $match  String to match against
     * @param   string  $side   Side for wildcards: 'both', 'none', 'before', 'after'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function notLike($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->andOrNotLike($field, $match, 'AND ', $side, 'NOT', $escape);
    }

    /**
     * OR LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param   mixed   $field  Field name or array of field/match pairs
     * @param   string  $match  String to match against
     * @param   string  $side   Side for wildcards: 'both', 'none', 'before', 'after'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orLike($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->andOrNotLike($field, $match, 'OR ', $side, '', $escape);
    }

    /**
     * OR NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param   mixed   $field  Field name or array of field/match pairs
     * @param   string  $match  String to match against
     * @param   string  $side   Side for wildcards: 'both', 'none', 'before', 'after'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orNotLike($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->andOrNotLike($field, $match, 'OR ', $side, 'NOT', $escape);
    }

    /**
     * Internal LIKE
     *
     * @used-by DbQueryBuilder::like()
     * @used-by DbQueryBuilder::orLike()
     * @used-by DbQueryBuilder::notLike()
     * @used-by DbQueryBuilder::orNotLike()
     *
     * @param   mixed   $field  Field name or array of field/match pairs
     * @param   string  $match  String to match against
     * @param   string  $type   Connection type: 'OR ' or 'AND '
     * @param   string  $side   Side for wildcards: 'both', 'none', 'before', 'after'
     * @param   bool    $not    Whether to add NOT operator
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    protected function andOrNotLike($field, $match = '', $type = 'AND ', $side = 'both', $not = '', $escape = NULL)
    {
        is_array($field) || $field = array($field => $match);
        is_bool($escape) || $escape = $this->protect_identifiers;
        $side = strtolower($side);

        foreach ($field as $k => $v) {
            $prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0)
                ? $this->groupGetType('') : $this->groupGetType($type);

            $v = $this->escapeLikeStr($v);

            $like_statement = $prefix.' '.$k.' '.$not.' LIKE \'';
            $side === 'none' || $side === 'after' || $like_statement .= '%';
            $like_statement .= $v;
            $side === 'none' || $side === 'before' || $like_statement .= '%';
            $like_statement .= '\'';

            // some platforms require an escape sequence definition for LIKE wildcards
            if ($this->like_escape_str !== '') {
                $like_statement .= sprintf($this->like_escape_str, $this->like_escape_chr);
            }

            $this->qb_where[] = array('condition' => $like_statement, 'escape' => $escape);
            if ($this->qb_caching) {
                $this->qb_cache_where[] = array('condition' => $like_statement, 'escape' => $escape);
                $this->qb_cache_exists[] = 'where';
            }
        }

        return $this;
    }

    /**
     * Start a query group
     *
     * @used-by DbQueryBuilder::orGroupStart()
     * @used-by DbQueryBuilder::notGroupStart()
     * @used-by DbQueryBuilder::orNotGroupStart()
     *
     * @param   string  $not    NOT string to negate
     * @param   string  $type   Connection type: 'OR ' or 'AND '
     * @return  object  This object
     */
    public function groupStart($not = '', $type = 'AND ')
    {
        $type = $this->groupGetType($type);

        $this->qb_where_group_started = true;
        $prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0) ? '' : $type;
        $where = array(
            'condition' => $prefix.$not.str_repeat(' ', ++$this->qb_where_group_count).' (',
            'escape' => false
        );

        $this->qb_where[] = $where;
        if ($this->qb_caching) {
            $this->qb_cache_where[] = $where;
        }

        return $this;
    }

    /**
     * Starts a query group, but ORs the group
     *
     * @return  object  This object
     */
    public function orGroupStart()
    {
        return $this->groupStart('', 'OR ');
    }

    /**
     * Starts a query group, but NOTs the group
     *
     * @return  object  This object
     */
    public function notGroupStart()
    {
        return $this->groupStart('NOT ', 'AND ');
    }

    /**
     * Starts a query group, but OR NOTs the group
     *
     * @return  object  This object
     */
    public function orNotGroupStart()
    {
        return $this->groupStart('NOT ', 'OR ');
    }

    /**
     * Ends a query group
     *
     * @return  object  This object
     */
    public function groupEnd()
    {
        $this->qb_where_group_started = false;
        $where = array(
            'condition' => str_repeat(' ', $this->qb_where_group_count--).')',
            'escape' => false
        );

        $this->qb_where[] = $where;
        if ($this->qb_caching) {
            $this->qb_cache_where[] = $where;
        }

        return $this;
    }

    /**
     * Group Get Type
     *
     * @used-by DbQueryBuilder::andOrWhereHaving()
     * @used-by DbQueryBuilder::andOrNotLike()
     * @used-by DbQueryBuilder::andOrWhereNotIn()
     * @used-by DbQueryBuilder::groupStart()
     *
     * @param   string  $type   Connection type: 'OR ' or 'AND '
     * @return  string  Type or empty string if inside group
     */
    protected function groupGetType($type)
    {
        if ($this->qb_where_group_started) {
            $type = '';
            $this->qb_where_group_started = false;
        }

        return $type;
    }

    /**
     * GROUP BY
     *
     * @param   string  $by     Group field or fields
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function groupBy($by, $escape = null)
    {
        is_bool($escape) || $escape = $this->protect_identifiers;

        if (is_string($by)) {
            $by = $escape ? explode(',', $by) : array($by);
        }

        foreach ($by as $val) {
            $val = trim($val);

            if ($val !== '') {
                $val = array('field' => $val, 'escape' => $escape);

                $this->qb_groupby[] = $val;
                if ($this->qb_caching) {
                    $this->qb_cache_groupby[] = $val;
                    $this->qb_cache_exists[] = 'groupby';
                }
            }
        }

        return $this;
    }

    /**
     * HAVING
     *
     * Separates multiple calls with 'AND'.
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   mixed   $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function having($key, $value = null, $escape = null)
    {
        return $this->andOrWhereHaving('qb_having', $key, $value, 'AND ', $escape);
    }

    /**
     * OR HAVING
     *
     * Separates multiple calls with 'OR'.
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   mixed   $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orHaving($key, $value = null, $escape = null)
    {
        return $this->andOrWhereHaving('qb_having', $key, $value, 'OR ', $escape);
    }

    /**
     * ORDER BY
     *
     * @param   string  $orderby    Field name(s)
     * @param   string  $direction  Order direction: 'ASC', 'DESC' or 'RANDOM'
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function orderBy($orderby, $direction = '', $escape = null)
    {
        $direction = strtoupper(trim($direction));

        if ($direction === 'RANDOM') {
            $direction = '';

            // Do we have a seed value?
            $orderby = ctype_digit((string)$orderby)
                ? sprintf($this->random_keyword[1], $orderby)
                : $this->random_keyword[0];
        }
        elseif (empty($orderby)) {
            return $this;
        }
        elseif ($direction !== '') {
            $direction = in_array($direction, array('ASC', 'DESC'), TRUE) ? ' '.$direction : '';
        }

        is_bool($escape) || $escape = $this->protect_identifiers;

        if ($escape === false) {
            $qb_orderby[] = array('field' => $orderby, 'direction' => $direction, 'escape' => false);
        }
        else {
            $qb_orderby = array();
            foreach (explode(',', $orderby) as $field) {
                if ($direction === '' && preg_match('/\s+(ASC|DESC)$/i', rtrim($field), $match, PREG_OFFSET_CAPTURE)) {
                    $qb_orderby[] = array(
                        'field' => ltrim(substr($field, 0, $match[0][1])),
                        'direction' => ' '.$match[1][0],
                        'escape' => true
                    );
                }
                else {
                    $qb_orderby[] = array('field' => trim($field), 'direction' => $direction, 'escape' => true);
                }
            }
        }

        $this->qb_orderby = array_merge($this->qb_orderby, $qb_orderby);
        if ($this->qb_caching) {
            $this->qb_cache_orderby = array_merge($this->qb_cache_orderby, $qb_orderby);
            $this->qb_cache_exists[] = 'orderby';
        }

        return $this;
    }

    /**
     * LIMIT
     *
     * @param   int     $value  LIMIT value
     * @param   int     $offset OFFSET value
     * @return  object  This object
     */
    public function limit($value, $offset = false)
    {
        is_null($value) || $this->qb_limit = (int)$value;
        empty($offset) || $this->qb_offset = (int)$offset;
        return $this;
    }

    /**
     * OFFSET
     *
     * Sets the OFFSET value
     *
     * @param   int     $offset OFFSET value
     * @return  object  This object
     */
    public function offset($offset)
    {
        empty($offset) || $this->qb_offset = (int)$offset;
        return $this;
    }

    /**
     * Add LIMIT string
     *
     * Generates a platform-specific LIMIT clause.
     *
     * @used-by DbQueryBuilder::compileSelect()
     *
     * @param   string  $sql    Query string
     * @return  string  Query string with LIMIT clause
     */
    protected function dbLimit($sql)
    {
        return $sql.' LIMIT '.($this->qb_offset ? $this->qb_offset.', ' : '').$this->qb_limit;
    }

    /**
     * SET
     *
     * Allows key/value pairs to be set for inserting or updating
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   string  $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function set($key, $value = '', $escape = null)
    {
        $key = $this->objectToArray($key);
        is_array($key) || $key = array($key => $value);
        is_bool($escape) || $escape = $this->protect_identifiers;

        foreach ($key as $k => $v) {
            $this->qb_set[$this->protectIdentifiers($k, false, $escape)] = $escape ? $this->escape($v) : $v;
        }

        return $this;
    }

    /**
     * Get SELECT query string
     *
     * Compiles a SELECT query string and returns the sql.
     *
     * @param   string  $table  Table name
     * @param   bool    $reset  Whether to reset builder
     * @return  string  Compiled SELECT
     */
    public function getCompiledSelect($table = '', $reset = true)
    {
        if ($table !== '') {
            $this->trackAliases($table);
            $this->from($table);
        }
        $select = $this->compileSelect();
        $reset && $this->resetSelect();
        return $select;
    }

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     *
     * @param   string  $table  Table name
     * @param   string  $limit  LIMIT clause
     * @param   string  $offset OFFSET clause
     * @return  mixed   Result object on success, otherwise FALSE
     */
    public function get($table = '', $limit = null, $offset = null)
    {
        if ($table !== '') {
            $this->trackAliases($table);
            $this->from($table);
        }
        empty($limit) || $this->limit($limit, $offset);

        $result = $this->query($this->compileSelect());
        $this->resetSelect();
        return $result;
    }

    /**
     * "Count All Results" query
     *
     * Generates a platform-specific query string that counts all records
     * returned by an Query Builder query.
     *
     * @param   string  $table  Table name
     * @return  int     Result count
     */
    public function countAllResults($table = '')
    {
        if ($table !== '') {
            $this->trackAliases($table);
            $this->from($table);
        }

        $countnum = $this->count_string.$this->protectIdentifiers('numrows');
        $result = $this->qb_distinct ?
            $this->query($countnum."\nFROM (\n".$this->compileSelect()."\n) XY_count_all_results") :
            $this->query($this->compileSelect($countnum));
        $this->resetSelect();

        if ($result->numRows() === 0) {
            return 0;
        }

        $row = $result->row();
        return (int)$row->numrows;
    }

    /**
     * Get Where
     *
     * Allows the where clause, limit and offset to be added directly
     *
     * @param   string  $table  Table name
     * @param   string  $where  WHERE clause
     * @param   string  $limit  LIMIT clause
     * @param   string  $offset OFFSET clause
     * @return  object  Result object
     */
    public function getWhere($table = '', $where = null, $limit = null, $offset = null)
    {
        $table === '' || $this->from($table);
        $where === null || $this->where($where);
        empty($limit) || $this->limit($limit, $offset);

        $result = $this->query($this->compileSelect());
        $this->resetSelect();
        return $result;
    }

    /**
     * Insert Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @param   string  $table  Table name
     * @param   array   $set    SET values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  mixed   Number of rows inserted on success, otherwise FALSE
     */
    public function insertBatch($table = '', $set = null, $escape = null)
    {
        $set === null || $this->setInsertBatch($set, '', $escape);

        if (count($this->qb_set) === 0) {
            // No valid data array. Folds in cases where keys and values did not match up
            return $this->displayError('db_must_use_set');
        }

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }

        // Batch this baby
        $affected_rows = 0;
        for ($i = 0, $total = count($this->qb_set); $i < $total; $i += 100) {
            $this->query($this->dbInsert(
                $this->protectIdentifiers($table, true, $escape, false),
                $this->qb_keys,
                array_slice($this->qb_set, $i, 100)
            ));
            $affected_rows += $this->affectedRows();
        }

        $this->resetWrite();
        return $affected_rows;
    }

    /**
     * Set Insert Batch
     *
     * Allows key/value pairs to be set for batch inserts
     *
     * @used-by DbQueryBuilder::insertBatch()
     *
     * @param   mixed   $key    Field name or array of field/value pairs
     * @param   string  $value  Value
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function setInsertBatch($key, $value = '', $escape = null)
    {
        $key = $this->objectToArrayBatch($key);
        is_array($key) || $key = array($key => $value);
        is_bool($escape) || $escape = $this->protect_identifiers;

        $keys = array_keys($this->objectToArray(current($key)));
        sort($keys);

        foreach ($key as $row) {
            $row = $this->objectToArray($row);
            if (count(array_diff($keys, array_keys($row))) > 0 || count(array_diff(array_keys($row), $keys)) > 0) {
                // batch function above returns an error on an empty array
                $this->qb_set[] = array();
                return;
            }

            ksort($row); // puts $row in the same order as our keys

            if ($escape !== false) {
                $clean = array();
                foreach ($row as $value) {
                    $clean[] = $this->escape($value);
                }

                $row = $clean;
            }

            $this->qb_set[] = '('.implode(',', $row).')';
        }

        foreach ($keys as $k) {
            $this->qb_keys[] = $this->protectIdentifiers($k, false, $escape);
        }

        return $this;
    }

    /**
     * Get INSERT query string
     *
     * Compiles an insert query and returns the sql
     *
     * @param   string  $table  Table name
     * @param   bool    $reset  Whether to reset builder
     * @return  mixed   INSERT string on success, otherwise FALSE
     */
    public function getCompiledInsert($table = '', $reset = true)
    {
        if ($this->validateWrite($table) === false) {
            return false;
        }

        $sql = $this->dbInsert(
            $this->protect_identifiers($this->qb_from[0], true, null, false),
            array_keys($this->qb_set),
            array_values($this->qb_set)
        );
        $reset && $this->resetWrite();
        return $sql;
    }

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param   string  $table  Table name
     * @param   array   $set    SET values
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function insert($table = '', $set = null, $escape = null)
    {
        $set === NULL || $this->set($set, '', $escape);

        if ($this->validateWrite($table) === false) {
            return false;
        }

        $sql = $this->dbInsert(
            $this->protectIdentifiers($this->qb_from[0], true, $escape, false),
            array_keys($this->qb_set),
            array_values($this->qb_set)
        );

        $this->resetWrite();
        return $this->query($sql);
    }

    /**
     * Validate Insert
     *
     * Validates that the there data is actually being set and that table
     * has been chosen to be inserted into.
     *
     * @used-by DbQueryBuilder::insert()
     * @used-by DbQueryBuilder::update()
     * @used-by DbQueryBuilder::getCompiledInsert()
     * @used-by DbQueryBuilder::getCompiledUpdate()
     *
     * @param   string  $table  Table name
     * @return  bool    TRUE if valid, otherwise FALSE
     */
    protected function validateWrite($table = '')
    {
        if (count($this->qb_set) === 0) {
            return $this->displayError('db_must_use_set');
        }

        if ($table !== '') {
            $this->qb_from[0] = $table;
        }
        elseif (!isset($this->qb_from[0])) {
            return $this->displayError('db_must_set_table');
        }

        return true;
    }

    /**
     * Replace
     *
     * Compiles a replace into string and runs the query
     *
     * @param   string  $table  Table name
     * @param   array   $set    SET values
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function replace($table = '', $set = null)
    {
        $set === NULL || $this->set($set);

        if (count($this->qb_set) === 0) {
            return $this->displayError('db_must_use_set');
        }

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }

        $sql = $this->dbReplace(
            $this->protectIdentifiers($table, true, null, false),
            array_keys($this->qb_set),
            array_values($this->qb_set)
        );
        $this->resetWrite();
        return $this->query($sql);
    }

    /**
     * Replace statement
     *
     * Generates a platform-specific replace string from the supplied data
     *
     * @used-by DbQueryBuilder::replace()
     *
     * @param   string  $table  Table name
     * @param   array   $keys   Field names
     * @param   array   $values Values
     * @return  string  REPLACE string
     */
    protected function dbReplace($table, $keys, $values)
    {
        return 'REPLACE INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
    }

    /**
     * Get UPDATE query string
     *
     * Compiles an update query and returns the sql
     *
     * @param   string  $table  Table name
     * @param   bool    $reset  Whether to reset builder
     * @return  mixed   UPDATE string on success, otherwise FALSE
     */
    public function getCompiledUpdate($table = '', $reset = true)
    {
        // Combine any cached components with the current statements
        $this->mergeCache();

        if ($this->validateWrite($table) === false) {
            return false;
        }

        $sql = $this->dbUpdate($this->protectIdentifiers($this->qb_from[0], true, null, false), $this->qb_set);
        $reset && $this->resetWrite();
        return $sql;
    }

    /**
     * UPDATE
     *
     * Compiles an update string and runs the query.
     *
     * @param   string  $table  Table name
     * @param   array   $set    SET values
     * @param   string  $where  WHERE clause
     * @param   string  $limit  LIMIT clause
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function update($table = '', $set = null, $where = null, $limit = null)
    {
        // Combine any cached components with the current statements
        $this->mergeCache();

        $set === null && $this->set($set);

        if ($this->validateWrite($table) === false) {
            return false;
        }

        $where === null || $this->where($where);
        empty($limit) || $this->limit($limit);

        $sql = $this->dbUpdate($this->protectIdentifiers($this->qb_from[0], true, null, false), $this->qb_set);
        $this->resetWrite();
        return $this->query($sql);
    }

    /**
     * Update Batch
     *
     * Compiles an update string and runs the query
     *
     * @param   string  $table  Table name
     * @param   array   $set    SET values
     * @param   string  $index  WHERE key
     * @return  mixed   Number of rows affected on success, otherwise FALSE
     */
    public function updateBatch($table = '', $set = null, $index = null)
    {
        // Combine any cached components with the current statements
        $this->mergeCache();

        if ($index === null) {
            return $this->displayError('db_must_use_index');
        }

        $set === null || $this->setUpdateBatch($set, $index);

        if (count($this->qb_set) === 0) {
            return $this->displayError('db_must_use_set');
        }

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }

        // Batch this baby
        $affected_rows = 0;
        for ($i = 0, $total = count($this->qb_set); $i < $total; $i += 100) {
            $this->query($this->dbUpdateBatch(
                $this->protectIdentifiers($table, true, null, false),
                array_slice($this->qb_set, $i, 100),
                $this->protectIdentifiers($index)
            ));
            $affected_rows += $this->affected_rows();
            $this->qb_where = array();
        }

        $this->resetWrite();
        return $affected_rows;
    }

    /**
     * Update Batch statement
     *
     * Generates a platform-specific batch update string from the supplied data
     *
     * @used-by DbQueryBuilder::updateBatch()
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
                $field !== $index || $final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
            }
        }

        $cases = '';
        foreach ($final as $k => $v) {
            $cases .= $k." = CASE \n".implode("\n", $v)."\n".'ELSE '.$k.' END, ';
        }

        $this->where($index.' IN('.implode(',', $ids).')', null, false);

        return 'UPDATE '.$table.' SET '.substr($cases, 0, -2).$this->compileWhereHaving('qb_where');
    }

    /**
     * The "set_update_batch" function.  Allows key/value pairs to be set for batch updating
     *
     * @used-by DbQueryBuilder::updateBatch()
     *
     * @param   array   $key    
     * @param   string  $index
     * @param   bool    $escape Whether to escape values and identifiers
     * @return  object  This object
     */
    public function setUpdateBatch($key, $index = '', $escape = null)
    {
        $key = $this->objectToArrayBatch($key);
        if (!is_array($key)) {
            $this->showError('Invalid set key: '.$key);
            return $this;
        }

        is_bool($escape) || $escape = $this->protect_identifiers;

        foreach ($key as $k => $v) {
            $index_set = false;
            $clean = array();
            foreach ($v as $k2 => $v2) {
                $k2 === $index && $index_set = true;
                $k2 = $this->protectIdentifiers($k2, false, $escape);
                $clean[$k2] = $escape === false ? $v2 : $this->escape($v2);
            }

            if (!$index_set) {
                return $this->displayError('db_batch_missing_index');
            }

            $this->qb_set[] = $clean;
        }

        return $this;
    }

    /**
     * Empty Table
     *
     * Compiles a delete string and runs "DELETE FROM table"
     *
     * @param   string  $table  Table name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function emptyTable($table = '')
    {
        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }
        else {
            $table = $this->protectIdentifiers($table, true, null, false);
        }

        $sql = $this->dbDelete($table);
        $this->resetWrite();
        return $this->query($sql);
    }

    /**
     * TRUNCATE
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param   string  $table  Table name
     * @return  bool    TRUE on success, otherwise FALSE
     */
    public function truncate($table = '')
    {
        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }
        else {
            $table = $this->protectIdentifiers($table, true, null, false);
        }

        $sql = $this->dbTruncate($table);
        $this->resetWrite();
        return $this->query($sql);
    }

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the truncate() command,
     * then this method maps to 'DELETE FROM table'
     *
     * @used-by DbQueryBuilder::truncate()
     *
     * @param   string  $table  Table name
     * @return  string  TRUNCATE string
     */
    protected function dbTruncate($table)
    {
        return 'TRUNCATE '.$table;
    }

    /**
     * Get DELETE query string
     *
     * Compiles a delete query string and returns the sql
     *
     * @param   string  $table  Table name
     * @param   bool    $reset  Whether to reset builder
     * @return  string  DELETE string
     */
    public function getCompiledDelete($table = '', $reset = true)
    {
        $this->return_delete_sql = true;
        $sql = $this->delete($table, '', null, $reset);
        $this->return_delete_sql = false;
        return $sql;
    }

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @param   string  $table  Table name
     * @param   string  $where  WHERE clause
     * @param   string  $limit  LIMIT clause
     * @param   bool    $reset  Whether to reset builder
     * @return  mixed   DELETE string if requested, TRUE on success, otherwise FALSE
     */
    public function delete($table = '', $where = '', $limit = null, $reset = true)
    {
        // Combine any cached components with the current statements
        $this->mergeCache();

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                return $this->displayError('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }
        elseif (is_array($table)) {
            foreach ($table as $single_table) {
                $this->delete($single_table, $where, $limit, $reset);
            }
            return;
        }
        else {
            $table = $this->protectIdentifiers($table, true, null, false);
        }

        $where === '' || $this->where($where);
        empty($limit) || $this->limit($limit);

        if (count($this->qb_where) === 0) {
            return $this->displayError('db_del_must_use_where');
        }

        $sql = $this->dbDelete($table);
        $reset && $this->resetWrite();
        return $this->return_delete_sql ? $sql : $this->query($sql);
    }

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @used-by DbQueryBuilder::emptyTable()
     * @used-by DbQueryBuilder::delete()
     *
     * @param   string  $table  Table name
     * @return  string  DELETE string
     */
    protected function dbDelete($table)
    {
        return 'DELETE FROM '.$table.$this->compileWhereHaving('qb_where')
            .($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');
    }

    /**
     * DB Prefix
     *
     * Prepends a database prefix if one exists in configuration
     *
     * @param   string  $table  Table name
     * @return  string  Prefixed table name
     */
    public function dbprefix($table = '')
    {
        $table === '' && $this->displayError('db_table_name_required');
        return $this->dbprefix.$table;
    }

    /**
     * Set DB Prefix
     *
     * Set's the DB Prefix to something new without needing to reconnect
     *
     * @param   string  $prefix New prefix
     * @return  string  New prefix
     */
    public function setDbprefix($prefix = '')
    {
        return $this->dbprefix = $prefix;
    }

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @used-by DbQueryBuilder::from()
     * @used-by DbQueryBuilder::join()
     * @used-by DbQueryBuilder::getCompiledSelect()
     * @used-by DbQueryBuilder::get()
     * @used-by DbQueryBuilder::countAllResults()
     * @used-by DbQueryBuilder::mergeCache()
     *
     * @param   string  $table  Table name
     * @return  void
     */
    protected function trackAliases($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->trackAliases($t);
            }
            return;
        }

        // Does the string contain a comma? If so, we need to separate
        // the string into discreet statements
        if (strpos($table, ',') !== false) {
            return $this->trackAliases(explode(',', $table));
        }

        // If a table alias is used we can recognize it by a space
        if (strpos($table, ' ') !== false) {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace('/\s+AS\s+/i', ' ', $table);

            // Grab the alias
            $table = trim(strrchr($table, ' '));

            // Store the alias, if it doesn't already exist
            in_array($table, $this->qb_aliased_tables) || $this->qb_aliased_tables[] = $table;
        }
    }

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.
     *
     * @used-by DbQueryBuilder::getCompiledSelect()
     * @used-by DbQueryBuilder::get()
     * @used-by DbQueryBuilder::countAllResults()
     * @used-by DbQueryBuilder::getWhere()
     *
     * @param   mixed   $select_override    SELECT clause to use or FALSE
     * @return  string  SELECT string
     */
    protected function compileSelect($select_override = false)
    {
        // Combine any cached components with the current statements
        $this->mergeCache();

        // Write the "select" portion of the query
        if ($select_override !== false) {
            $sql = $select_override;
        }
        else {
            $sql = !$this->qb_distinct ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->qb_select) === 0) {
                $sql .= '*';
            }
            else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather then in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ($this->qb_select as $key => $val) {
                    $no_escape = isset($this->qb_no_escape[$key]) ? $this->qb_no_escape[$key] : null;
                    $this->qb_select[$key] = $this->protectIdentifiers($val, false, $no_escape);
                }

                $sql .= implode(', ', $this->qb_select);
            }
        }

        // Write the "FROM" portion of the query
        count($this->qb_from) > 0 && $sql .= "\nFROM ".$this->dbFrom();

        // Write the "JOIN" portion of the query
        count($this->qb_join) > 0 && $sql .= "\n".implode("\n", $this->qb_join);

        $sql .= $this->compileWhereHaving('qb_where').
            $this->compileGroupBy().
            $this->compileWhereHaving('qb_having').
            $this->compileOrderBy();

        // LIMIT
        if ($this->qb_limit) {
            return $this->dbLimit($sql."\n");
        }

        return $sql;
    }

    /**
     * FROM clause
     *
     * Groups tables in FROM clauses if needed, so there is no confusion
     * about operator precedence.
     *
     * Note: This is only used (and overriden) by MySQL and CUBRID.
     *
     * @return  string  FROM clause
     */
    protected function dbFrom()
    {
        return implode(', ', $this->qb_from);
    }

    /**
     * Compile WHERE, HAVING statements
     *
     * Escapes identifiers in WHERE and HAVING statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * where(), or_where(), having(), or_having are called prior to from(),
     * join() and dbprefix is added only if needed.
     *
     * @used-by DbQueryBuilder::dbUpdateBatch()
     * @used-by DbQueryBuilder::dbDelete()
     * @used-by DbQueryBuilder::compileSelect()
     *
     * @param   string  $qb_key 'qb_where' or 'qb_having'
     * @return  string  SQL statement
     */
    protected function compileWhereHaving($qb_key)
    {
        if (count($this->$qb_key) > 0) {
            for ($i = 0, $c = count($this->$qb_key); $i < $c; $i++) {
                // Is this condition already compiled?
                if (is_string($this->$qb_key[$i])) {
                    continue;
                }
                elseif ($this->$qb_key[$i]['escape'] === false) {
                    $this->$qb_key[$i] = $this->$qb_key[$i]['condition'];
                    continue;
                }

                // Split multiple conditions
                $conditions = preg_split('/(\s*AND\s+|\s*OR\s+)/i', $this->$qb_key[$i]['condition'], -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                for ($ci = 0, $cc = count($conditions); $ci < $cc; $ci++) {
                    if (($op = $this->getOperator($conditions[$ci])) === false ||
                    !preg_match('/^(\(?)(.*)('.preg_quote($op, '/').')\s*(.*(?<!\)))?(\)?)$/i', $conditions[$ci],
                    $matches)) {
                        continue;
                    }

                    // $matches = array(
                    //    0 => '(test <= foo)', // The whole thing
                    //    1 => '(',             // Optional
                    //    2 => 'test',          // The field name
                    //    3 => ' <= ',          // $op
                    //    4 => 'foo',           // Optional, if $op is e.g. 'IS NULL'
                    //    5 => ')'              // Optional
                    // );
                    if (!empty($matches[4])) {
                        $this->isLiteral($matches[4]) || $matches[4] = $this->protectIdentifiers(trim($matches[4]));
                        $matches[4] = ' '.$matches[4];
                    }

                    $conditions[$ci] = $matches[1].$this->protectIdentifiers(trim($matches[2])).' '.
                        trim($matches[3]).$matches[4].$matches[5];
                }

                $this->$qb_key[$i] = implode('', $conditions);
            }

            return ($qb_key === 'qb_having' ? "\nHAVING " : "\nWHERE ").implode("\n", $this->$qb_key);
        }

        return '';
    }

    /**
     * Compile GROUP BY
     *
     * Escapes identifiers in GROUP BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * group_by() is called prior to from(), join() and dbprefix is added
     * only if needed.
     *
     * @used-by DbQueryBuilder::compileSelect()
     *
     * @return  string  SQL statement
     */
    protected function compileGroupBy()
    {
        if (count($this->qb_groupby) > 0) {
            for ($i = 0, $c = count($this->qb_groupby); $i < $c; $i++) {
                // Is it already compiled?
                if (is_string($this->qb_groupby)) {
                    continue;
                }

                if ($this->qb_groupby[$i]['escape'] === false || $this->isLiteral($this->qb_groupby[$i]['field'])) {
                    $this->qb_groupby[$i] = $this->qb_groupby[$i]['field'];
                }
                else {
                    $this->qb_groupby[$i] = $this->protectIdentifiers($this->qb_groupby[$i]['field']);
                }
            }

            return "\nGROUP BY ".implode(', ', $this->qb_groupby);
        }

        return '';
    }

    /**
     * Compile ORDER BY
     *
     * Escapes identifiers in ORDER BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * order_by() is called prior to from(), join() and dbprefix is added
     * only if needed.
     *
     * @used-by DbQueryBuilder::compileSelect()
     *
     * @return  string  SQL statement
     */
    protected function compileOrderBy()
    {
        if (is_array($this->qb_orderby) && count($this->qb_orderby) > 0) {
            for ($i = 0, $c = count($this->qb_orderby); $i < $c; $i++) {
                if ($this->qb_orderby[$i]['escape'] !== false && ! $this->isLiteral($this->qb_orderby[$i]['field'])) {
                    $this->qb_orderby[$i]['field'] = $this->protectIdentifiers($this->qb_orderby[$i]['field']);
                }

                $this->qb_orderby[$i] = $this->qb_orderby[$i]['field'].$this->qb_orderby[$i]['direction'];
            }

            return $this->qb_orderby = "\nORDER BY ".implode(', ', $this->qb_orderby);
        }
        elseif (is_string($this->qb_orderby)) {
            return $this->qb_orderby;
        }

        return '';
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @used-by DbQueryBuilder::set()
     * @used-by DbQueryBuilder::setInsertBatch()
     *
     * @param   object  $object Object to convert
     * @return  array   Array of variable key/vals
     */
    protected function objectToArray($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            // There are some built in keys we need to ignore for this conversion
            if (!is_object($val) && !is_array($val) && $key !== '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @used-by DbQueryBuilder::setInsertBatch()
     * @used-by DbQueryBuilder::setUpdateBatch()
     *
     * @param   object  $object Object to convert
     * @return  array   Array of variable key/vals
     */
    protected function objectToArrayBatch($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        $out = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val) {
            // There are some built in keys we need to ignore for this conversion
            if ($val !== '_parent_name') {
                $i = 0;
                foreach ($out[$val] as $data) {
                    $array[$i++][$val] = $data;
                }
            }
        }

        return $array;
    }

    /**
     * Start Cache
     *
     * Starts QB caching
     *
     * @return  void
     */
    public function startCache()
    {
        $this->qb_caching = true;
    }

    /**
     * Stop Cache
     *
     * Stops QB caching
     *
     * @return  void
     */
    public function stopCache()
    {
        $this->qb_caching = false;
    }

    /**
     * Flush Cache
     *
     * Empties the QB cache
     *
     * @return  void
     */
    public function flushCache()
    {
        $this->resetRun(array(
            'qb_cache_select' => array(),
            'qb_cache_from' => array(),
            'qb_cache_join' => array(),
            'qb_cache_where' => array(),
            'qb_cache_groupby' => array(),
            'qb_cache_having' => array(),
            'qb_cache_orderby' => array(),
            'qb_cache_set' => array(),
            'qb_cache_exists' => array(),
            'qb_cache_no_escape' => array()
        ));
    }

    /**
     * Merge Cache
     *
     * When called, this function merges any cached QB arrays with
     * locally called ones.
     *
     * @used-by DbQueryBuilder::getCompiledUpdate()
     * @used-by DbQueryBuilder::update()
     * @used-by DbQueryBuilder::updateBatch()
     * @used-by DbQueryBuilder::delete()
     * @used-by DbQueryBuilder::compileSelect()
     *
     * @return  void
     */
    protected function mergeCache()
    {
        if (count($this->qb_cache_exists) === 0) {
            return;
        }
        elseif (in_array('select', $this->qb_cache_exists, true)) {
            $qb_no_escape = $this->qb_cache_no_escape;
        }

        foreach (array_unique($this->qb_cache_exists) as $val) {
            $qb_variable = 'qb_'.$val;
            $qb_cache_var = 'qb_cache_'.$val;
            $qb_new  = $this->$qb_cache_var;

            for ($i = 0, $c = count($this->$qb_variable); $i < $c; $i++) {
                if ( ! in_array($this->{$qb_variable}[$i], $qb_new, true)) {
                    $qb_new[] = $this->{$qb_variable}[$i];
                    if ($val === 'select') {
                        $qb_no_escape[] = $this->qb_no_escape[$i];
                    }
                }
            }

            $this->$qb_variable = $qb_new;
            if ($val === 'select') {
                $this->qb_no_escape = $qb_no_escape;
            }
        }

        // If we are "protecting identifiers" we need to examine the "from"
        // portion of the query to determine if there are any aliases
        if ($this->protect_identifiers && count($this->qb_cache_from) > 0) {
            $this->trackAliases($this->qb_from);
        }
    }

    /**
     * Is literal
     *
     * Determines if a string represents a literal value or a field name
     *
     * @used-by DbQueryBuilder::compileWhereHaving()
     * @used-by DbQueryBuilder::compileGroupBy()
     * @used-by DbQueryBuilder::compileOrderBy()
     *
     * @param   string  $str    String to check
     * @return  bool    TRUE if literal, otherwise FALSE
     */
    protected function isLiteral($str)
    {
        $str = trim($str);

        if (empty($str) || ctype_digit($str) || (string)(float)$str === $str ||
        in_array(strtoupper($str), array('TRUE', 'FALSE'), true)) {
            return true;
        }

        if (empty($this->qb_literals)) {
            $this->escape_char === '"' || $this->qb_literals[] = '"';
            $this->qb_literals[] = '\'';
        }

        return in_array($str[0], $this->qb_literals, true);
    }

    /**
     * Reset Query Builder values.
     *
     * Publicly-visible method to reset the QB values.
     *
     * @return  void
     */
    public function resetQuery()
    {
        $this->resetSelect();
        $this->resetWrite();
    }

    /**
     * Resets the query builder values. Called by the get() function
     *
     * @used-by DbQueryBuilder::flushCache()
     * @used-by DbQueryBuilder::resetSelect()
     * @used-by DbQueryBuilder::resetWrite()
     *
     * @param   array   $qb_reset_items An array of fields to reset
     * @return  void
     */
    protected function resetRun($qb_reset_items)
    {
        foreach ($qb_reset_items as $item => $default_value) {
            $this->$item = $default_value;
        }
    }

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @used-by DbQueryBuilder::getCompiledSelect()
     * @used-by DbQueryBuilder::get()
     * @used-by DbQueryBuilder::countAllResults()
     * @used-by DbQueryBuilder::getWhere()
     * @used-by DbQueryBuilder::resetQuery()
     *
     * @return  void
     */
    protected function resetSelect()
    {
        $this->resetRun(array(
            'qb_select' => array(),
            'qb_from' => array(),
            'qb_join' => array(),
            'qb_where' => array(),
            'qb_groupby' => array(),
            'qb_having' => array(),
            'qb_orderby' => array(),
            'qb_aliased_tables' => array(),
            'qb_no_escape' => array(),
            'qb_distinct' => false,
            'qb_limit' => false,
            'qb_offset' => false
        ));
    }

    /**
     * Resets the query builder "write" values.
     *
     * Called by the insert() update() insert_batch() update_batch() and delete() functions
     *
     * @used-by DbQueryBuilder::insertBatch()
     * @used-by DbQueryBuilder::getCompiledInsert()
     * @used-by DbQueryBuilder::insert()
     * @used-by DbQueryBuilder::replace()
     * @used-by DbQueryBuilder::getCompiledUpdate()
     * @used-by DbQueryBuilder::update()
     * @used-by DbQueryBuilder::updateBatch()
     * @used-by DbQueryBuilder::emptyTable()
     * @used-by DbQueryBuilder::truncate()
     * @used-by DbQueryBuilder::delete()
     * @used-by DbQueryBuilder::resetQuery()
     *
     * @return  void
     */
    protected function resetWrite()
    {
        $this->resetRun(array(
            'qb_set' => array(),
            'qb_from' => array(),
            'qb_join' => array(),
            'qb_where' => array(),
            'qb_orderby' => array(),
            'qb_keys' => array(),
            'qb_limit' => false
        ));
    }
}

