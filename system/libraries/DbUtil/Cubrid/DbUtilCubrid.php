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
namespace Xylophone\libraries\DbUtil\Cubrid;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CUBRID Database Utility Class
 *
 * @package     Xylophone
 * @subpackage  libraries/Database/Cubrid
 * @link        http://xylophone.io/user_guide/database/
 */
class DbUtilCubrid extends \Xylophone\libraries\DbUtil
{
    /**
     * List databases
     *
     * @return  array   Database list
     */
    public function listDatabases()
    {
        if (isset($this->db->data_cache['db_names'])) {
            return $this->db->data_cache['db_names'];
        }

        return $this->db->data_cache['db_names'] = cubrid_list_dbs($this->db->conn_id);
    }

    /**
     * Export
     *
     * @param   array   $params Parameters
     * @return  mixed   Backup file data on success, otherwise FALSE
     */
    protected function dbBackup($params)
    {
        // No SQL based support in CUBRID as of version 8.4.0. Database or
        // table backup can be performed using CUBRID Manager
        // database administration tool.
        return $this->db->displayError('db_unsupported_feature');
    }
}

