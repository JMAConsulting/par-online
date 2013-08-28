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

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class CRM_Contact_Form_Search_Custom_LocalAdminUserSearch
   extends    CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

    function __construct( &$formValues ) {
        parent::__construct( $formValues );


        $this->_columns = array( ts('Name')             => 'donor_name'  ,
                                 ts('Envelope #')       => 'envelope_number' ,
                                 ts('Primary E-mail')   => 'donor_email',
                                 ts('Subscribed Funds') => 'funds',
                                 ts('Current Month')    => 'mtd_total',
                                 ts('Upcoming Month')   => 'upcoming',
                                 ts('Year To Date')     => 'total',) ;
    }

    function buildForm( &$form ) {

        $form->add( 'text',
                    'first_name',
                    ts( 'First Name' ),
                    true );

        $form->add( 'text',
                    'last_name',
                    ts( 'Last Name' ),
                    true );
        $form->add( 'text',
                    'email',
                    ts( 'Primary E-mail' ),
                    true );
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Quick Search');
         
        /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        $form->assign( 'elements', array( 'first_name', 'last_name','email') );
    }

    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false ) {
        $selectClause = "
contact_a.id as contact_id, contact_a.display_name as donor_name,
admin_cc.id as admin_id, admin_cc.display_name as admin_name,
email.email as donor_email, '' as funds, '' as mtd_total, '' as total, '' as upcoming, envelope.envelope_number_40 as envelope_number";
        return $this->sql( $selectClause,
                           $offset, $rowcount, $sort,
                           $includeContactIDs, null );
    }
    
    function from( ) {
        $from = date('Y').'-01-01 00:00:00';  
        $upTo = date('Y-m-d H:i:s');
        return "
FROM civicrm_contact AS contact_a

INNER JOIN custom_relatedContacts AS donor_rel ON ( contact_a.id = donor_rel.related_id )

LEFT JOIN civicrm_contact AS admin_cc ON ( admin_cc.id = donor_rel.contact_id )

LEFT JOIN civicrm_group_contact  AS supporter   ON ( contact_a.id = supporter.contact_id AND supporter.status = 'Added' )

LEFT JOIN civicrm_email  AS email  ON ( email.contact_id = contact_a.id AND email.is_primary = 1 )

LEFT JOIN civicrm_value_envelope_13  AS envelope  ON ( envelope.entity_id  = contact_a.id )";
    }
    
    function where( $includeContactIDs = false ) {

        global $user;
        require_once 'api/api.php';
        $ufMatchParams = array( 
                               'uf_id' => $user->uid,
                               'version' => 3,
                                );
        $ufResult = civicrm_api( 'uf_match','get',$ufMatchParams );
        $loggedIn = $ufResult['values'][$ufResult['id']]['contact_id'];
        $params = array( );
        $where  = "contact_a.is_deleted = 0 AND supporter.group_id = 3 AND admin_cc.id = ".$loggedIn;
        
        $count  = 1;
        $clause = array( );
        $first_name   = CRM_Utils_Array::value( 'first_name',
                                                $this->_formValues );
        if ( $first_name != null ) {
            if ( strpos( $first_name, '%' ) === false ) {
                $first_name = "%{$first_name}%";
            }
            $params[$count] = array( $first_name, 'String' );
            $clause[] = "contact_a.first_name LIKE %{$count}";
            $count++;
        }
        $last_name   = CRM_Utils_Array::value( 'last_name',
                                               $this->_formValues );
        if ( $last_name != null ) {
            if ( strpos( $last_name, '%' ) === false ) {
                $last_name = "%{$last_name}%";
            }
            $params[$count] = array( $last_name, 'String' );
            $clause[] = "contact_a.last_name LIKE %{$count}";
            $count++;
        }
        $email = CRM_Utils_Array::value( 'email',
                                         $this->_formValues );
        if ( $email != null ) {
            if ( strpos($email , '%' ) === false ) {
                $email = "%{$email}%";
            }
            $params[$count] = array( $email, 'String' );
            $clause[] = "email.email LIKE %{$count}";
            $count++;
        }

        if ( ! empty( $clause ) ) {
            $where .= ' AND ' . implode( ' AND ', $clause );
        }
        $where .= " GROUP BY contact_a.id ";
        return $this->whereClause( $where, $params );
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom/LocalAdminUserSearch.tpl';
    }

    function setDefaultValues( ) {
        return array( 'household_name'    => '', );
    }
    
    function count( ) {
        $dao = CRM_Core_DAO::executeQuery( $this->all() );
        return $dao->N;
    }

    function alterRow( &$row ) {
        require_once 'CRM/Core/DAO.php';
        require_once 'CRM/Utils/Money.php';
        $from = date('Y').'-'.date('m').'-01 00:00:00';  
        $upTo = date('Y-m-d H:i:s');
        $summary = getDonationSummary($row['contact_id']);

        if ( !empty( $summary['funds'] ) ) {
            $funds = implode(",", $summary['funds']);
            $row['funds'] = ltrim($funds, ",");
        }
        if ( $summary['upcoming'] ) {
            $row['upcoming'] = CRM_Utils_Money::format( $summary['upcoming']['amount'] );
        }
        if ( $summary['month'] ) {
            $row['mtd_total'] = CRM_Utils_Money::format( $summary['month']['amount'] );
        }
        if ( $summary['year'] ) {
            $row['total'] = CRM_Utils_Money::format( $summary['year']['amount'] );
        } 
        return $row;
    }

    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Search'));
        }
    }
}
