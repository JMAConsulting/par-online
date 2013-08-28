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

class CRM_Contact_Form_Search_Custom_ChargeSearch
   extends    CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

    function __construct( &$formValues ) {
        parent::__construct( $formValues );
            
        
        $this->_columns = array( ts('Charge Name')         => 'pc_name',
                                 ts('Congregation')        => 'pc_cong_name',
                                 ts('Local Admin Name(s)') => 'admin_name',
                                 ts('Denomination')        => 'deno_name',
                                 ts('Conference')          => 'conf_name',
                                 ts('City/Town')           => 'city',
                                 ) ;  
    }

    function buildForm( &$form ) {
        
        $form->add( 'text',
                    'display_name',
                    ts( 'Charge Name' ),
                    true );
        $form->add( 'text',
                    'sort_name',
                    ts( 'Congregation' ),
                    true );
        $form->add( 'text',
                    'first_name',
                    ts( 'First Name' ),
                    true );

        $form->add( 'text',
                    'last_name',
                    ts( 'Last Name' ),
                    true );
        $form->add( 'text',
                    'organization_name',
                    ts( 'Denomination' ),
                    true ); 
        $form->add( 'text',
                    'city',
                    ts( 'City/Town' ),
                    true ); 
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Quick Charge Search');
         
        /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        $form->assign( 'elements', array( 'display_name', 'sort_name', 'first_name', 'last_name', 'organization_name', 'city' ) );
        
        
    }

    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false ) {      
        require_once 'CRM/Contact/BAO/Contact/Permission.php';
        list( $this->_aclFrom, $this->_aclWhere ) = CRM_Contact_BAO_Contact_Permission::cacheClause( 'contact_a' );
        
        $selectClause = "
DISTINCT(contact_a.id) as contact_id, contact_a.display_name as pc_name, city.city as city, contact_a.contact_sub_type as contact_sub_type,
admin_cc.id as admin_id, admin_cc.display_name as admin_name,
pc_cong_rel.contact_id_a as pc_cong_id, pc_cong_cc.display_name as pc_cong_name,
pres_rel.contact_id_b as pres_id, pres_cc.display_name as pres_name,
conf_rel.contact_id_b as conf_id, conf_cc.display_name as conf_name,
deno_rel.contact_id_b as deno_id, deno_cc.display_name as deno_name,
non_uc_rel.contact_id_b as non_uc_id, non_uc_cc.display_name as non_uc_name
";

        return $this->sql( $selectClause,
                           $offset, $rowcount, $sort,
                           $includeContactIDs, null );
    }
    
    function from( ) {

        

        return "FROM civicrm_contact AS contact_a {$this->_aclFrom}

LEFT JOIN civicrm_relationship AS pc_cong_rel ON ( contact_a.id = pc_cong_rel.contact_id_b AND pc_cong_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )

LEFT JOIN civicrm_contact AS pc_cong_cc ON ( pc_cong_cc.id = pc_cong_rel.contact_id_a)

LEFT JOIN civicrm_address  AS city         ON ( city.contact_id = contact_a.id AND city.is_primary = 1 )

LEFT JOIN civicrm_relationship AS admin_rel ON (  admin_rel.relationship_type_id = ".PAR_ADMIN_RELATION_TYPE_ID." AND contact_a.id = admin_rel.contact_id_b )

LEFT JOIN civicrm_contact AS admin_cc ON ( admin_cc.id = admin_rel.contact_id_a AND admin_rel.relationship_type_id = ".PAR_ADMIN_RELATION_TYPE_ID." )

LEFT JOIN civicrm_relationship AS pres_rel ON ( contact_a.id = pres_rel.contact_id_a AND pres_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS pres_cc ON ( pres_cc.id = pres_rel.contact_id_b AND pres_cc.contact_sub_type = 'Presbytery')

LEFT JOIN civicrm_relationship AS conf_rel ON ( conf_rel.contact_id_a = pres_rel.contact_id_b AND conf_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS conf_cc ON ( conf_cc.id = conf_rel.contact_id_b AND conf_cc.contact_sub_type = 'Conference')

LEFT JOIN civicrm_relationship AS deno_rel ON ( deno_rel.contact_id_a = conf_rel.contact_id_b AND deno_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS deno_cc ON ( deno_cc.id = deno_rel.contact_id_b AND deno_cc.contact_sub_type = 'Denomination')

LEFT JOIN civicrm_relationship AS non_uc_rel ON ( contact_a.id = non_uc_rel.contact_id_a AND non_uc_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS non_uc_cc ON ( non_uc_cc.id = non_uc_rel.contact_id_b AND non_uc_cc.contact_sub_type = 'Denomination')
";       
    }
    
    
    function where( $includeContactIDs = false ) {
        $params = array( );
        $count  = 1;
        $clause = array( );
        $display_name   = CRM_Utils_Array::value( 'display_name',
                                                  $this->_formValues );
        if ( $display_name != null ) {
            if ( strpos( $display_name, '%' ) === false ) {
                $display_name = "%{$display_name}%";
            }
            $params[$count] = array( $display_name, 'String' );
            $clause[] = "contact_a.display_name LIKE %{$count}";
            $count++;
        }
        
        $first_name   = CRM_Utils_Array::value( 'first_name',
                                                $this->_formValues );
        if ( $first_name != null ) {
            if ( strpos( $first_name, '%' ) === false ) {
                $first_name = "%{$first_name}%";
            }
            $params[$count] = array( $first_name, 'String' );
            $clause[] = " ( admin_cc.first_name LIKE %{$count} AND admin_cc.first_name IS NOT NULL )";
            $count++;
        }
        $last_name   = CRM_Utils_Array::value( 'last_name',
                                               $this->_formValues );
        if ( $last_name != null ) {
            if ( strpos( $last_name, '%' ) === false ) {
                $last_name = "%{$last_name}%";
            }
            $params[$count] = array( $last_name, 'String' );
            $clause[] = "( admin_cc.last_name LIKE %{$count} AND admin_cc.last_name IS NOT NULL )";
            $count++;
        }
        
        $denomination_name   = CRM_Utils_Array::value( 'organization_name',
                                                       $this->_formValues );
        if ( $denomination_name != null ) {
            if ( strpos( $denomination_name, '%' ) === false ) {
                $denomination_name = "%{$denomination_name}%";
            }
            $params[$count] = array( $denomination_name, 'String' );
            $clause[] = "( ( deno_cc.display_name LIKE %{$count} OR non_uc_cc.display_name LIKE %{$count} ) )";
            $count++;
        }

        $city   = CRM_Utils_Array::value( 'city',
                                          $this->_formValues );
        if ( $city != null ) {
            if ( strpos( $city, '%' ) === false ) {
                $city = "%{$city}%";
            }
            $params[$count] = array( $city, 'String' );
            $clause[] = "( city.city LIKE %{$count} AND city.city IS NOT NULL )";
            $count++;
        }

        $congregation_name   = CRM_Utils_Array::value( 'sort_name',
                                                       $this->_formValues );
        if ( $congregation_name != null ) {
            if ( strpos( $congregation_name, '%' ) === false ) {
                $congregation_name = "%{$congregation_name}%";
            }
            $params[$count] = array( $congregation_name, 'String' );
            $clause[] = "pc_cong_cc.display_name LIKE %{$count} ";
            $count++;
        }
        $where  = "contact_a.contact_sub_type = 'Pastoral_Charge' ";
               
        if ( ! empty( $clause ) ) {
            $where .= ' AND ' . implode( ' AND ', $clause );
        }
        
        if ( $this->_aclWhere ) {
            $this->_where .= " AND {$this->_aclWhere} ";
        }
        
        return $this->whereClause( $where, $params );
    }
    
    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom/ChargeSearch.tpl';
    }
    
    function setDefaultValues( ) {
        return array( 'household_name'    => '', );
    }
    
    function count( ) {
        $dao = CRM_Core_DAO::executeQuery( CRM_Contact_Form_Search_Custom_ChargeSearch::all());
        return $dao->N;
        
    }
   
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Search'));
        }
    }
}
