<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.0                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2011                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/
/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Contribute_DAO_ParServiceFees extends CRM_Core_DAO
{
    /**
     * static instance to hold the table name
     *
     * @var string
     * @static
     */
    static $_tableName = 'civicrm_custom_par_service_fees';
    /**
     * static instance to hold the field values
     *
     * @var array
     * @static
     */
    static $_fields = null;
    /**
     * static instance to hold the FK relationships
     *
     * @var string
     * @static
     */
    static $_links = null;
    /**
     * static instance to hold the values that can
     * be imported / apu
     *
     * @var array
     * @static
     */
    static $_import = null;
    /**
     * static instance to hold the values that can
     * be exported / apu
     *
     * @var array
     * @static
     */
    static $_export = null;
    /**
     * static value to see if we should log any modifications to
     * this table in the civicrm_log table
     *
     * @var boolean
     * @static
     */
    static $_log = true;
    /**
     * Par Service Fees ID
     *
     * @var int unsigned
     */
    public $id;
    /**
     * Par Service Rate Name.
     *
     * @var string
     */
    public $name;
    /**
     * Per direct debit transaction fee.
     *
     * @var float
     */
    public $transaction_fee;
    /**
     * Per month ceiling on direct debit transactions.
     *
     * @var int unsigned
     */
    public $per_month_ceiling;
    /**
     * Per month flat fee on direct debit transactions when number of transactions exceeds ceiling .
     *
     * @var float
     */
    public $flat_feet_ransactions_exceeds_ceiling;
    /**
     * Credit card percentage fee .
     *
     * @var float
     */
    public $credit_card_percentage_fee;
    /**
     * class constructor
     *
     * @access public
     * @return civicrm_custom_par_service_fees
     */
    function __construct()
    {
        parent::__construct();
    }
    /**
     * returns all the column names of this table
     *
     * @access public
     * @return array
     */
    function &fields()
    {
        if (!(self::$_fields)) {
            self::$_fields = array(
                'psf_id' => array(
                    'name' => 'id',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Par Service Fees ID') ,
                    'required' => true,
                ) ,
                'rate_name' => array(
                    'name' => 'name',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Rate name') ,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                    'import' => true,
                    'where' => 'civicrm_custom_par_service_fees.name',
                    'headerPattern' => '/(contrib(ution)?)?type/i',
                    'dataPattern' => '/donation|member|campaign/i',
                    'export' => true,
                ) ,
                'transaction_fee' => array(
                    'name' => 'transaction_fee',
                    'type' => CRM_Utils_Type::T_MONEY,
                    'title' => ts('Per direct debit transaction fee') ,
                    'import' => true,
                    'where' => 'civicrm_custom_par_service_fees.transaction_fee',
                    'headerPattern' => '/transaction?.?fee/i',
                    'dataPattern' => '/^\d+(\.\d{2})?$/',
                    'export' => true,
                ) ,
                'per_month_ceiling' => array(
                    'name' => 'per_month_ceiling',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Per month ceiling on direct debit transactions') ,
                    'import' => true,
                    'where' => 'civicrm_custom_par_service_fees.per_month_ceiling',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'flat_feet_ransactions_exceeds_ceiling' => array(
                    'name' => 'flat_feet_ransactions_exceeds_ceiling',
                    'type' => CRM_Utils_Type::T_MONEY,
                    'title' => ts('Per month flat fee on direct debit transactions when number of transactions exceeds ceiling') ,
                    'import' => true,
                    'where' => 'civicrm_custom_par_service_fees.flat_feet_ransactions_exceeds_ceiling',
                    'headerPattern' => '/flat?.?fee/i',
                    'dataPattern' => '/^\d+(\.\d{2})?$/',
                    'export' => true,
                ) ,
                'credit_card_percentage_fee' => array(
                    'name' => 'credit_card_percentage_fee',
                    'type' => CRM_Utils_Type::T_MONEY,
                    'title' => ts('Credit card percentage fee') ,
                    'import' => true,
                    'where' => 'civicrm_custom_par_service_fees.credit_card_percentage_fee',
                    'headerPattern' => '/percentage?.?fee/i',
                    'dataPattern' => '/^\d+(\.\d{8})?$/',
                    'export' => true,
                ) ,
            );
        }
        return self::$_fields;
    }
    /**
     * returns the names of this table
     *
     * @access public
     * @return string
     */
    function getTableName()
    {
        return self::$_tableName;
    }
    /**
     * returns if this table needs to be logged
     *
     * @access public
     * @return boolean
     */
    function getLog()
    {
        return self::$_log;
    }
    /**
     * returns the list of fields that can be imported
     *
     * @access public
     * return array
     */
    function &import($prefix = false)
    {
        if (!(self::$_import)) {
            self::$_import = array();
            $fields = & self::fields();
            foreach($fields as $name => $field) {
                if (CRM_Utils_Array::value('import', $field)) {
                    if ($prefix) {
                        self::$_import['custom_par_service_fees'] = & $fields[$name];
                    } else {
                        self::$_import[$name] = & $fields[$name];
                    }
                }
            }
        }
        return self::$_import;
    }
    /**
     * returns the list of fields that can be exported
     *
     * @access public
     * return array
     */
    function &export($prefix = false)
    {
        if (!(self::$_export)) {
            self::$_export = array();
            $fields = & self::fields();
            foreach($fields as $name => $field) {
                if (CRM_Utils_Array::value('export', $field)) {
                    if ($prefix) {
                        self::$_export['custom_par_service_fees'] = & $fields[$name];
                    } else {
                        self::$_export[$name] = & $fields[$name];
                    }
                }
            }
        }
        return self::$_export;
    }
}
