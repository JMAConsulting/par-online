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

require_once 'CRM/Report/Form.php';
require_once 'CRM/Contribute/PseudoConstant.php';

class CRM_Report_Form_Contribute_RelatedDetail extends CRM_Report_Form {
    protected $_addressField = false;

    protected $_emailField   = false;

    protected $_summary      = null;

    protected $_customGroupExtends = array( 'Contribution' );

    function __construct( ) {
        $this->_columns = 
            array( 'civicrm_contact'      =>
                   array( 'dao'     => 'CRM_Contact_DAO_Contact',
                          'fields'  =>
                          array( 'sort_name' => 
                                 array( 'title' => ts( 'Contact Name' ),
                                        'required'  => true,
                                        'no_repeat' => true ),
                                 'id'           => 
                                 array( 'no_display' => true,
                                        'required'  => true, ),
                                 'display_name' => 
                                 array( 'title'    => 'Congregation',
                                        'required'  => true,
                                        'no_repeat'  => true),
                                 'organization_name' => 
                                 array( 'title'    => 'Pastoral Charge',
                                        'required'  => true,
                                        'no_repeat'  => true)),
                          'filters' =>             
                          array('sort_name'    => 
                                array( 'title'      => ts( 'Contact Name' ),
                                       'operator'   => 'like' ),
                                'id'    => 
                                array( 'title'      => ts( 'Contact ID' ),
                                       'no_display' => true ), ),
                          'grouping'=> 'contact-fields',
                          ),
 
                   'civicrm_email'   =>
                   array( 'dao'       => 'CRM_Core_DAO_Email',
                          'fields'    =>
                          array( 'email' => 
                                 array( 'title'      => ts( 'Email' ),
                                        'default'    => true,
                                        'no_repeat'  => true
                                       ),  ),
                          'grouping'      => 'contact-fields',
                          ),
                   'civicrm_line_item'   =>
                   array( 'dao'       => 'CRM_Price_DAO_LineItem',
                          'fields'    =>
                          array( 'label' => 
                                 array( 'title'      => ts( 'Price Field' ),
                                        'default'    => true,
                                        ),
                          'line_total' => 
                                 array( 'title'      => ts( 'Amount' ),
                                        'default'    => true,
                                        ),  ),
                          'grouping'      => 'price-fields',
                          ),
                   'civicrm_phone'   =>
                   array( 'dao'       => 'CRM_Core_DAO_Phone',
                          'fields'    =>
                          array( 'phone' => 
                                 array( 'title'      => ts( 'Phone' ),
                                        'default'    => true,
                                        'no_repeat'  => true
                                        ), ),
                          'grouping'      => 'contact-fields',
                          ),

                   'civicrm_contribution' =>
                   array( 'dao'     => 'CRM_Contribute_DAO_Contribution',
                          'fields'  =>
                          array(
                                 'contribution_id' => array( 
                                                            'name' => 'id',
                                                            'no_display' => true,
                                                            'required'   => true,
                                                ),
                                 'contribution_type_id' => array( 'title'   => ts('Contribution Type'),
                                                                  'default' => true,
                                                                ),
                                'payment_instrument_id' => array( 'title'   => ts('Payment Type'),
                                                                            ),
                                 'trxn_id'              => null,
                                 'receive_date'         => array( 'default' => true ),
                                 'receipt_date'         => null,
                                 'fee_amount'           => null,
                                 'net_amount'           => null,
                                 'total_amount'         => array( 'title'        => ts( 'Amount' ),
                                                                  'required'     => false,
                                                                  'no_display' => true,
                                                                  'statistics'   => 
                                                                  array('sum' => ts( 'Amount' )),
                                                                  ),
                                 ),
                          'filters' =>             
                          array( 'receive_date'           => 
                                    array('title'         => ts( 'Receive Date' ), 
                                          'operatorType'  => CRM_Report_Form::OP_DATE,
                                          ),
                                 'contribution_type_id'   =>
                                    array( 'title'        => ts( 'Contribution Type' ), 
                                           'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                           'options'      => CRM_Contribute_PseudoConstant::contributionType( )
                                         ),
                                 'payment_instrument_id'   =>
                                    array( 'title'        => ts( 'Payment Type' ), 
                                           'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                           'options'      => CRM_Contribute_PseudoConstant::paymentInstrument( )
                                         ),
                                'contribution_status_id' => 
                                    array( 'title'        => ts( 'Contribution Status' ), 
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options'      => CRM_Contribute_PseudoConstant::contributionStatus( ),
                                        'default'      => array( 1 ),
                                        ),
                                 'total_amount'           => 
                                    array( 'title'        => ts( 'Contribution Amount' ) ),
                                 ),
                          'grouping'=> 'contri-fields',
                          ),
                   
                   'civicrm_group' => 
                   array( 'dao'    => 'CRM_Contact_DAO_GroupContact',
                          'alias'  => 'cgroup',
                          'filters' =>             
                          array( 'gid' => 
                                 array( 'name'         => 'group_id',
                                        'title'        => ts( 'Group' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'group'        => true,
                                        'options'      => CRM_Core_PseudoConstant::group( ) ), ), ),
                   
                   'civicrm_contribution_ordinality' =>                    
                   array( 'dao'    => 'CRM_Contribute_DAO_Contribution',
                          'alias'  => 'cordinality',
                          'filters' =>             
                          array( 'ordinality' => 
                                 array( 'title'   => ts( 'Contribution Ordinality' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options'      => array( 0 => 'First by Contributor', 
                                                                 1 => 'Second or Later by Contributor') ), ), ),
                   ) + $this->addAddressFields(false);

        $this->_tagFilter = true;
        parent::__construct( );
    }

    function preProcess( ) {
        parent::preProcess( );
    }

    function select( ) {
        require_once 'CRM/Contact/BAO/Contact.php';
        $select = array( );
        
        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                        if ( $tableName == 'civicrm_address' ) {
                            $this->_addressField = true;
                        } else if ( $tableName == 'civicrm_email' ) {
                            $this->_emailField = true;
                        } else if ( $tableName == 'civicrm_line_item' ) {
                            $this->_lineItem = true;
                        }
                        
                        // only include statistics columns if set
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  = $field['type'];
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'count':
                                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'avg':
                                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  = $field['type'];
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                }
                            }   
                            
                        } else {
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                        }
                    }
                }
            }
        }
        $session     =  CRM_Core_SESSION::singleton( );
        $this->_isDenoAdmin = CRM_Contact_BAO_GroupContact::isContactInGroup( $session->get( 'userID' ), DENOMINATION_ADMIN_GROUP_ID );
        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }

    function from( ) {
        $this->_from = null;

        $this->_from = "
        FROM  civicrm_contact      {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0";
        $this->_from .= " LEFT JOIN custom_relatedContacts relContact ON  relContact.related_id = {$this->_aliases['civicrm_contribution']}.contact_id ";
        if ( !empty($this->_params['ordinality_value']) ) {
            $this->_from .= "
              INNER JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']} 
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id";
        }

        $this->_from .= "
               LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']} 
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                         {$this->_aliases['civicrm_phone']}.is_primary = 1)";
        
        if ( $this->_addressField OR ( !empty($this->_params['state_province_id_value']) OR !empty($this->_params['country_id_value']) ) ) { 
            $this->_from .= "
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                      {$this->_aliases['civicrm_address']}.is_primary = 1\n";
        }
        
        if ( $this->_emailField ) {
            $this->_from .= " 
            LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                      {$this->_aliases['civicrm_email']}.is_primary = 1\n";
        }

        if ( $this->_lineItem ) {
            $this->_from .= " 
            LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']} 
                   ON {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_line_item']}.entity_id\n";
            
        }
    }


    function groupBy( ) {
        $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.id, {$this->_aliases['civicrm_line_item']}.label";
    }

    function orderBy( ) {
        $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id ";
    }

    function statistics( &$rows ) {
        $statistics = parent::statistics( $rows );

        $select = "
        SELECT COUNT({$this->_aliases['civicrm_line_item']}.line_total ) as count,
               SUM( {$this->_aliases['civicrm_line_item']}.line_total ) as amount,
               ROUND(SUM({$this->_aliases['civicrm_line_item']}.line_total), 2) as avg, COUNT(DISTINCT(contribution_civireport.contact_id)) as contact_totals
        ";
       
        $sql = "{$select} {$this->_from} {$this->_where}";
        $dao = CRM_Core_DAO::executeQuery( $sql );

        if ( $dao->fetch( ) ) {
            if ($dao->contact_totals != 0 ) {
                $statistics['counts']['avg']       = array( 'value' => $dao->avg/$dao->contact_totals,
                                                            'title' => 'Average',
                                                            'type'  => CRM_Utils_Type::T_MONEY );
            } else {
                $statistics['counts']['avg']       = array( 'value' => $dao->avg,
                                                            'title' => 'Average',
                                                            'type'  => CRM_Utils_Type::T_MONEY );
            }
            $statistics['counts']['total']     = array( 'value' => $dao->avg,
                                                        'title' => 'Total',
                                                        'type'  => CRM_Utils_Type::T_MONEY );
        }
        $select = " SELECT line_item_civireport.label as name, SUM( line_item_civireport.line_total ) as amount ";
        $sql = "{$select} {$this->_from}" . " LEFT JOIN civicrm_contribution cct ON cct.id = line_item_civireport.entity_id ";
        $sql .= "{$this->_where} GROUP BY line_item_civireport.label";
        $dao = CRM_Core_DAO::executeQuery( $sql );
        while ( $dao->fetch( ) ) {
            $statistics['counts'][$dao->name]       = array( 'value' => $dao->amount,
                                                             'title' => $dao->name ,
                                                             'type'  => CRM_Utils_Type::T_MONEY );
        }
        return $statistics;
    }

    function postProcess( ) {
        // get the acl clauses built before we assemble the query
        $this->buildACLClause( $this->_aliases['civicrm_contact'] );
        parent::postProcess( );
    }

    function alterDisplay( &$rows ) {
        // custom code to alter rows
        $checkList  = array();
        if( !$this->_isDenoAdmin ){
            unset($this->_columnHeaders['civicrm_contact_display_name']);
            unset($this->_columnHeaders['civicrm_contact_organization_name']);
        }
        $entryFound = false;
        $display_flag = $prev_cid = $cid =  0;
        $contributionTypes = CRM_Contribute_PseudoConstant::contributionType( );
        $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument( );        
        foreach ( $rows as $rowNum => $row ) {
            if ( !empty($this->_noRepeats) && $this->_outputMode != 'csv' ) {
                // don't repeat contact details if its same as the previous row
                if ( array_key_exists('civicrm_contact_id', $row ) ) {
                    if ( $cid =  $row['civicrm_contact_id'] ) {
                        if ( $rowNum == 0 ) {
                            $prev_cid = $cid;
                        } else {
                            if( $prev_cid == $cid ) {
                                $display_flag = 1;
                                $prev_cid = $cid;
                            } else {
                                $display_flag = 0;
                                $prev_cid = $cid;
                            }
                        }
                        
                        if ( $display_flag ) {
                            foreach ( $row as $colName => $colVal ) {
                                if ( in_array($colName, $this->_noRepeats) ) {
                                    unset($rows[$rowNum][$colName]);
                                }
                            }
                        }
                        $entryFound = true;
                        
                    }
                }
            }
            


            // convert display name to links
            if ( array_key_exists('civicrm_contact_sort_name', $row) && 
                 CRM_Utils_Array::value( 'civicrm_contact_sort_name', $rows[$rowNum] ) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                $url = CRM_Utils_System::url( "civicrm/contact/view"  , 
                                              'reset=1&cid=' . $row['civicrm_contact_id'],
                                              $this->_absoluteUrl );
                $rows[$rowNum]['civicrm_contact_sort_name_link' ] = $url;
                $rows[$rowNum]['civicrm_contact_sort_name_hover'] =  
                    ts("View Contact Summary for this Contact.");
            }
            if ( $value = CRM_Utils_Array::value( 'civicrm_contribution_contribution_type_id', $row ) ) {
                $rows[$rowNum]['civicrm_contribution_contribution_type_id'] = $contributionTypes[$value];
                $entryFound = true;
            }
            if ( $value = CRM_Utils_Array::value( 'civicrm_contribution_payment_instrument_id', $row ) ) {
                $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
                $entryFound = true;
            }
            if ( ( $value = CRM_Utils_Array::value( 'civicrm_contribution_total_amount_sum', $row ) ) && 
                 CRM_Core_Permission::check( 'access CiviContribute' ) ) {
                $url = CRM_Utils_System::url( "civicrm/contact/view/contribution" , 
                                              "reset=1&id=".$row['civicrm_contribution_contribution_id']."&cid=".$row['civicrm_contact_id']."&action=view&context=contribution&selectedChild=contribute",
                                              $this->_absoluteUrl );
                $rows[$rowNum]['civicrm_contribution_total_amount_sum_link'] = $url;
                $rows[$rowNum]['civicrm_contribution_total_amount_sum_hover'] =  
                    ts("View Details of this Contribution.");
                $entryFound = true;
            }
            $entryFound =  $this->alterDisplayAddressFields($row,$rows,$rowNum,'contribute/detail','List all contribution(s) for this ')?true:$entryFound;
 
            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
            $lastKey = $rowNum;
            if( $this->_isDenoAdmin ){
                $supporterOf = getRelation( $row['civicrm_contact_id'], SUPPORTER_RELATION_TYPE_ID, true );
                if( key($supporterOf) == 'Pastoral_Charge' ){
                    if( array_key_exists('civicrm_contact_display_name', $rows[$rowNum] )) {
                        $rows[$rowNum][ 'civicrm_contact_display_name' ]      = "";
                        $rows[$rowNum][ 'civicrm_contact_organization_name' ] = current($supporterOf['Pastoral_Charge']['contacts'] );
                    }
                } else if( key($supporterOf) == 'Congregation' ) {
                    if( array_key_exists('civicrm_contact_display_name', $rows[$rowNum] )) {
                        $rows[$rowNum][ 'civicrm_contact_display_name' ]      = current($supporterOf['Congregation']['contacts'] );
                        $pcOfCongregation = getRelation( key($supporterOf['Congregation']['contacts'] ), IS_PART_OF_RELATION_TYPE_ID, true );
                        $rows[$rowNum][ 'civicrm_contact_organization_name' ]  = empty($pcOfCongregation[ 'Pastoral_Charge' ][ 'contacts' ]) ? NULL : current( $pcOfCongregation[ 'Pastoral_Charge' ][ 'contacts' ] );
                    }
                }
            }
        }
    }
    
    function where( ) {
        parent::where();
        $params = $this->getVar( '_params' );
        $this->_where .= " AND relContact.contact_id = {$_SESSION['CiviCRM']['userID']} ";
        if( $params['receive_date_relative'] == 'ucc.month' ){
            $todaysDay = date('d',time());
            if( $todaysDay >= 20 ){
                $startDate = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 20, date( 'Y', time() ) ) );
                $endDate   = date( 'Y-m-d',strtotime("$startDate -1 day +1 month"));
            } else {
                $endDate   = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 19, date( 'Y', time() ) ) );
                $startDate = date( 'Y-m-d',strtotime("$endDate +1 day -1 month"));
            }
            $this->_where .= " AND {$this->_aliases['civicrm_contribution']}.receive_date BETWEEN '$startDate' AND '$endDate' ";
        }
    }
}
