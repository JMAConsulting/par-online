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
  public static $_filterType = NULL;
  function __construct( &$formValues ) {
    parent::__construct( $formValues );
            
        
    $this->_columns = array( ts('Charge Name') => 'pc_name',
                             ts('Charge Number') => 'ms_number',
                             ts('Local Admin Name(s)') => 'admin_name',
                             ts('Denomination') => 'deno_name',
                             ts('Conference') => 'conf_name',
                             ts('City/Town') => 'city',
                             ts('dont Care') => 'contact_sub_type',
                             ) ;  
  }

  function buildForm( &$form ) {
    $form->add( 'text',
                'display_name',
                ts('Par Charge Name'),
                true );
    $form->add( 'text',
                'ms_number',
                ts('Charge Number'),
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
                'city',
                ts( 'City/Town' ),
                true ); 
    $denominations = array();
    $dao = CRM_Core_DAO::executeQuery('SELECT id, organization_name FROM civicrm_contact WHERE contact_sub_type = "Denomination"');
    while ($dao->fetch()) {
      $denominations[$dao->id] = $dao->organization_name;
    }
    $form->add('select', 'organization_name', ts('Denomination'), array('' => '-- Select Denomination --') + $denominations, null, array('class' => 'organization_name'));
    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Quick Charge Search');
         
    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign( 'elements', array( 'display_name', 'ms_number', 'first_name', 'last_name', 'organization_name', 'city' ) );
    $task =& CRM_Contact_Task::$_tasks;
    unset($task[8]);
  }

  function all( $offset = 0, $rowcount = 0, $sort = null,
                $includeContactIDs = false ) {      
    require_once 'CRM/Contact/BAO/Contact/Permission.php';
    list( $this->_aclFrom, $this->_aclWhere ) = CRM_Contact_BAO_Contact_Permission::cacheClause('contact_a');
        
    if (CRM_Utils_Array::value('display_name', $this->_formValues)) {
      $pastoralCharge = 'contact_id';
      $adminContact = 'admin_id';
      $denomination = 'deno_id';
    }
    elseif (CRM_Utils_Array::value('organization_name', $this->_formValues)) {
      $pastoralCharge = 'pc_id';
      $adminContact = 'admin_id';
      $denomination = 'contact_id';
    }
    elseif (CRM_Utils_Array::value('first_name', $this->_formValues) ||
            CRM_Utils_Array::value('last_name', $this->_formValues) ) {
      $pastoralCharge = 'pc_id';
      $adminContact = 'contact_id';
      $denomination = 'deno_id';
    }
    else {
      $pastoralCharge = 'contact_id';
      $adminContact = 'admin_id';
      $denomination = 'deno_id';
    }
        
    $selectClause = "
DISTINCT(contact_a.id) as {$pastoralCharge}, 
admin_cc.id as {$adminContact},
IFNULL(deno_rel.contact_id_b, non_uc_cc.id) as {$denomination},

conf_rel.contact_id_b as conf_id,contact_a.display_name as pc_name, city.city as city, 
contact_a.contact_sub_type as contact_sub_type, 
admin_cc.display_name as admin_name, 
pres_rel.contact_id_b as pres_id, pres_cc.display_name as pres_name, 
conf_cc.display_name as conf_name,
IFNULL(deno_cc.display_name, non_uc_cc.display_name) as deno_name,
ms_number.ms_number_16 ms_number
";

    return $this->sql( $selectClause,
                       $offset, $rowcount, $sort,
                       $includeContactIDs, null );
  }
    
  function from( ) {
    return "FROM civicrm_contact AS contact_a {$this->_aclFrom}

LEFT JOIN civicrm_relationship AS pc_cong_rel ON ( contact_a.id = 
CASE 
  WHEN  contact_a.contact_sub_type = 'Pastoral_Charge'
    THEN pc_cong_rel.contact_id_b 
  ELSE
    pc_cong_rel.contact_id_a
END
AND pc_cong_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )

LEFT JOIN civicrm_contact AS pc_cong_cc ON ( pc_cong_cc.id = pc_cong_rel.contact_id_b)

LEFT JOIN civicrm_address  AS city         ON ( city.contact_id = contact_a.id AND city.is_primary = 1 )

LEFT JOIN civicrm_relationship AS admin_rel ON (  admin_rel.relationship_type_id = ".PAR_ADMIN_RELATION_TYPE_ID." AND CASE 
  WHEN  contact_a.contact_sub_type = 'Pastoral_Charge'
    THEN pc_cong_rel.contact_id_a 
  ELSE
    contact_a.id
END = admin_rel.contact_id_b )

LEFT JOIN civicrm_contact AS admin_cc ON ( admin_cc.id = admin_rel.contact_id_a AND admin_rel.relationship_type_id = ".PAR_ADMIN_RELATION_TYPE_ID." )

LEFT JOIN civicrm_relationship AS pres_rel ON ( pc_cong_cc.id = pres_rel.contact_id_a AND pres_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS pres_cc ON ( pres_cc.id = pres_rel.contact_id_b AND pres_cc.contact_sub_type = 'Presbytery')

LEFT JOIN civicrm_relationship AS conf_rel ON ( conf_rel.contact_id_a = pres_rel.contact_id_b AND conf_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS conf_cc ON ( conf_cc.id = conf_rel.contact_id_b AND conf_cc.contact_sub_type = 'Conference')

LEFT JOIN civicrm_relationship AS deno_rel ON ( deno_rel.contact_id_a = conf_rel.contact_id_b AND deno_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS deno_cc ON ( deno_cc.id = deno_rel.contact_id_b AND deno_cc.contact_sub_type = 'Denomination')

LEFT JOIN civicrm_relationship AS non_uc_rel ON ( contact_a.id = non_uc_rel.contact_id_a AND non_uc_rel.relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." )
LEFT JOIN civicrm_contact AS non_uc_cc ON ( non_uc_cc.id = non_uc_rel.contact_id_b AND non_uc_cc.contact_sub_type = 'Denomination')
LEFT JOIN civicrm_value_other_details_7 ms_number ON ms_number.entity_id = contact_a.id
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
    if (!empty($denomination_name)) {
      $params[$count] = array($denomination_name, 'Integer');
      $clause[] = "((deno_cc.id = %{$count} OR non_uc_cc.id = %{$count}))";
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
        
    if (CRM_Utils_Array::value( 'ms_number', $this->_formValues)) {
      if (strpos($this->_formValues['ms_number'], '%' ) === false) {
        $city = "%{$this->_formValues['ms_number']}%";
      }
      $params[$count] = array($this->_formValues['ms_number'], 'String');
      $clause[] = "(ms_number.ms_number_16 LIKE %{$count} )";
      $count++;
    }
        
    $where  = "contact_a.contact_sub_type IN ('Pastoral_Charge', 'Congregation') ";
               
    if ( ! empty( $clause ) ) {
      $where .= ' AND ' . implode( ' AND ', $clause );
    }
        
    if ( $this->_aclWhere ) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
        
    return $this->whereClause( $where, $params );
  }
    
  function templateFile( ) {
    unset($this->_columns['dont Care']);
    $headers =& CRM_Core_Smarty::singleton()->get_template_vars('columnHeaders');
    unset($headers[6]);
    $rows =& CRM_Core_Smarty::singleton()->get_template_vars('rows');
    if (is_array($rows)) {
      $permissions = array(CRM_Core_Permission::getPermission());
      $mask = CRM_Core_Action::mask($permissions);
      require_once('CRM/Contact/Form/Search/Custom/UserSearch.php');
      $links = CRM_Contact_Form_Search_Custom_UserSearch::links();
      $links[CRM_Core_Action::UPDATE]['url'] = 'civicrm/profile/edit';
      $links[CRM_Core_Action::UPDATE]['qs'] = 'reset=1&gid=%%fid%%&id=%%id%%';
      $isFilter = FALSE;
      $orgProfile = array(
        'Congregation' => CO_PROFILE_ID,
        'Pastoral_Charge' => PC_PROFILE_ID,
      );
      if (empty($this->_formValues['display_name']) && (!empty($this->_formValues['organization_name']) 
        || !empty($this->_formValues['first_name'])
        || !empty($this->_formValues['last_name']))) {
        $isFilter = TRUE;
        if (!empty($this->_formValues['organization_name'])) {
          $profileId = DENOMINATION_PROFILE_ID;
        }
        else {
          $profileId = CONTACT_PROFILE_ID;
        }
      }
      foreach ($rows as $key => $row) {
        if (!$isFilter) {
          $profileId = $orgProfile[trim($row['contact_sub_type'],'')];
        }
        $rows[$key]['action'] = CRM_Core_Action::formLink( 
          $links,
          $mask ,
          array( 'id' => $row['contact_id'], 'fid' => $profileId)
        );
      } 
    }
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
