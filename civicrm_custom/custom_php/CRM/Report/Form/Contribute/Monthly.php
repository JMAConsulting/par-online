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

class CRM_Report_Form_Contribute_Monthly extends CRM_Report_Form {
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
                                        'required'  => true, ), ),
                          'filters' =>             
                          array('sort_name'    => 
                                array( 'title'      => ts( 'Contact Name' ),
                                       'operator'   => 'like' ),
                                'id'    => 
                                array( 'title'      => ts( 'Contact ID' ),
                                       'no_display' => true ), ),
                          'grouping'=> 'contact-fields',
                          ),
                   'civicrm_contribution_type' =>
                   array( 'dao'           => 'CRM_Contribute_DAO_ContributionType',
                          'fields'        =>
                          array( 'contribution_type'   => null, ), 
                          'grouping'      => 'contri-fields',
                          'group_bys'     =>
                          array( 'contribution_type'   => null, ), ),
                   'civicrm_value_account_details_2' =>
                   array( 'dao'           => 'CRM_Core_DAO_CustomField',
                          'fields'        =>
                          array( 'cc_type'   => array( 'name'  => 'cc_type_30',
                                                          'title' => 'CC Type',
                                                          'required'  => true,
                                                          'no_display' => true) ), 
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
                                                                  'no_display' => true,
                                                                ),
                                 'fee_amount'           => null,
                                 'net_amount'           => null,
                                 'total_amount'         => array( 'title'        => ts( 'Amount' ),
                                                                    'required'     => true,
                                                                    'statistics'   => 
                                                                          array('sum' => ts( 'Amount' )),
                                                                  ),
                                 'payment_instrument_id' => array( 'title'      => 'Instrument',
                                                                   'no_display' => true,
                                                                   'required'   => true,
                                                                   ),
                                 ),
                          'filters' =>             
                          array( 'receive_date'           => 
                                    array( 'operatorType' => CRM_Report_Form::OP_DATE ),
                                 'contribution_type_id'   =>
                                    array( 'title'        => ts( 'Contribution Type' ), 
                                           'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                           'options'      => CRM_Contribute_PseudoConstant::contributionType( )
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
                   );
        parent::__construct( );
    }

    function preProcess( ) {
        parent::preProcess( );
    }

    function select( ) {
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
                        }
                        
                        // only include statistics columns if set
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  = 
                                        $field['type'];
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'count':
                                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'avg':
                                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  =  
                                        $field['type'];
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
        $select[] = "cc_type_30 as cc_type";
        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }

    function from( ) {
        $this->_from = null;
        $this->_from = "
        FROM  civicrm_contact      {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0 
              INNER JOIN civicrm_value_account_details_2 {$this->_aliases['civicrm_value_account_details_2']} ON {$this->_aliases['civicrm_value_account_details_2']}.entity_id = {$this->_aliases['civicrm_contribution']}.id";
        
            $this->_from .= " LEFT JOIN civicrm_contribution_type {$this->_aliases['civicrm_contribution_type']} ON {$this->_aliases['civicrm_contribution_type']}.id = {$this->_aliases['civicrm_contribution']}.contribution_type_id";

    }


    function groupBy( ) {
        $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution_type']}.name, {$this->_aliases['civicrm_contribution']}.payment_instrument_id, civicrm_value_account_details_2_cc_type ";
    }

    function orderBy( ) {
        $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id ";
    }

    function statistics( &$rows ) {

    }

    function postProcess( ) {
        // get the acl clauses built before we assemble the query
        $this->buildACLClause( $this->_aliases['civicrm_contact'] );
        parent::postProcess( );
    }

    function alterDisplay( &$rows ) {
        $recordRows = array();
        $newHeader = array();
        $type = array();
        require_once 'CRM/Contact/Form/Donation.php';
        $contributionTypes = CRM_Contact_Form_Donation::getRelatedFundType( $_SESSION['CiviCRM']['userID'], PAR_ADMIN_RELATION_TYPE_ID );
        if( !$contributionTypes ){
            $contributionTypes = CRM_Contact_Form_Donation::getRelatedFundType( $_SESSION['CiviCRM']['userID'], DENOMINATION_ADMIN_RELATION_TYPE_ID );
        }

        $tempTypes = array_flip($contributionTypes);
        foreach( $tempTypes as $tempKey => $tempValue ){
            $tempTypes[ $tempKey ] = 0;
        }
        $newHeader[ 'civicrm_contact_sort_name' ] = $this->_columnHeaders[ 'civicrm_contact_sort_name' ];
        foreach( $contributionTypes as $typeValue ){
            $newHeader[ $typeValue ] = array( 'title' => $typeValue,
                                              'type' => 2 );
        }
        $newHeader[ 'total' ]  = array( 'title' => 'Total Deposit',
                                        'type' => 2 );
        $this->_columnHeaders = $newHeader;
        $instrumentCount      = array();
        $noOfContributors     = 0;
        foreach( $rows as $rowKey => $rowValue ){
            if( $rowValue[ 'civicrm_contribution_type_contribution_type' ] && array_key_exists( $rowValue[ 'civicrm_contribution_type_contribution_type' ], $tempTypes ) ){
                $recordRows[ $rowValue['civicrm_contact_id'] ][ 'civicrm_contact_sort_name' ] = $rowValue[ 'civicrm_contact_sort_name' ];
                $tempTypes[ $rowValue[ 'civicrm_contribution_type_contribution_type' ] ] += $rowValue['civicrm_contribution_total_amount_sum'];
                if( array_key_exists( $rowValue[ 'civicrm_contribution_type_contribution_type' ] ,$recordRows[ $rowValue['civicrm_contact_id'] ] ) ){
                    $recordRows[ $rowValue['civicrm_contact_id'] ][ $rowValue[ 'civicrm_contribution_type_contribution_type' ] ] += $rowValue[ 'civicrm_contribution_total_amount_sum' ];
                } else {
                    $recordRows[ $rowValue['civicrm_contact_id'] ][ $rowValue[ 'civicrm_contribution_type_contribution_type' ] ] = $rowValue[ 'civicrm_contribution_total_amount_sum' ];
                }

                if( !array_key_exists( $rowValue[ 'civicrm_contribution_payment_instrument_id' ], $instrumentCount ) && $rowValue[ 'civicrm_contribution_payment_instrument_id' ] == 1 ){
                    $instrumentCount[ $rowValue[ 'civicrm_contribution_payment_instrument_id' ] ][ 'visa' ] = 0;
                    $instrumentCount[ $rowValue[ 'civicrm_contribution_payment_instrument_id' ] ][ 'MasterCard' ] = 0;
                }

                if( $rowValue[ 'civicrm_contribution_payment_instrument_id' ] == 1 ){
                    $instrumentCount[ $rowValue[ 'civicrm_contribution_payment_instrument_id' ] ][ $rowValue['civicrm_value_account_details_2_cc_type'] ] += $rowValue['civicrm_contribution_total_amount_sum'];
                } 

                if( array_key_exists( 'total', $recordRows[ $rowValue['civicrm_contact_id'] ] ) ){
                    $recordRows[ $rowValue['civicrm_contact_id'] ][ 'total' ] += $rowValue[ 'civicrm_contribution_total_amount_sum' ];
                } else {
                    $recordRows[ $rowValue['civicrm_contact_id'] ][ 'total' ] = $rowValue[ 'civicrm_contribution_total_amount_sum' ];
                }
            }
        }

        require_once 'CRM/Utils/Money.php';
        array_push( $recordRows, $tempTypes );
        $grandTotal = 0;
        $this->assign( 'tempTypes', $tempTypes );
        foreach( $recordRows as $rowKey => $rowValue ){
            foreach( $rowValue as $typeKey => $value ){
                if( $typeKey != 'civicrm_contact_sort_name' ){
                    $recordRows[ $rowKey ][ $typeKey ] = CRM_Utils_Money::format( $value, null, '%c %a' );
                    $grandTotal += $value;
                }
            }
        }
        $this->assign( 'grandTotal', $grandTotal );
        $rowCount = count( $recordRows );

        $serviceCharge['VISA Service Charge'] = array( 'contributors' => (float)( $instrumentCount[ 1 ][ 'Visa' ] ),
                                                       'charge'       => CC_FEES,
                                                       'amount'       => (float)( $instrumentCount[ 1 ][ 'Visa' ] * CC_FEES / 100 ) );

        $serviceCharge['MASTERCARD Service Charge'] = array( 'contributors' => (float)( $instrumentCount[ 1 ][ 'MasterCard' ] ),
                                                             'charge'       => CC_FEES,
                                                             'amount'       => (float)( $instrumentCount[ 1 ][ 'MasterCard' ] * CC_FEES / 100 ) );
        
        $serviceCharge['Service Charge'] = array( 'contributors' => $rowCount - 1 . " Contributors ",
                                                  'charge'       => 0.50,
                                                  'amount'       => (float)( ($rowCount - 1) * 0.50 ) );

        $totalTransfered = $grandTotal - ((float)( ($rowCount - 1) * 0.50 ) + (float)( $instrumentCount[ 1 ][ 'visa' ] * CC_FEES / 100 ) + (float)( $instrumentCount[ 1 ][ 'MasterCard' ] * CC_FEES / 100 ) );
        $this->assign( 'serviceCharge', $serviceCharge );
        $this->assign( 'totalTransfered', $totalTransfered );
        $rows = $recordRows;
    }

}
