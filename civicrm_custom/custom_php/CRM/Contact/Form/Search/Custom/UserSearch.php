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

class CRM_Contact_Form_Search_Custom_UserSearch
   extends    CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

    static $_links = null;
    function __construct( &$formValues ) {
        parent::__construct( $formValues );


        $this->_columns = array( ts('Name') => 'donor_name'  ,
                                 ts('PAR ID') => 'external_identifier',
                                 ts('NSF') => 'nsf',
                                 ts('Status') => 'contribution_status_id',
                                 ts('Primary E-mail') => 'email',
                                 ts('PC Name') => 'pc_name',
                                 ts('Conference' )=> 'conf_name',
                                 ts('dont Care') => 'activated__48',) ;
    }
    static function &links() {
      if (!(self::$_links)) {
        self::$_links = array(
          CRM_Core_Action::VIEW => array(
            'name'=> ts('View'),
            'url' => 'civicrm/contact/view',
            'qs' => 'reset=1&cid=%%id%%',
            'title' => ts('View Contact Details'),
          ),
          CRM_Core_Action::UPDATE => array(
            'name' => ts('Edit'),
            'url' => 'civicrm/contact/add',
            'qs' => 'reset=1&action=update&cid=%%id%%',
            'title' => ts('Edit Contact Details'),
          ),
        );
      }
      return self::$_links;
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
                    'external_identifier',
                    ts( 'PAR ID' ),
                    true );
        $form->add('checkbox', 'show_deleted_contacts', ts('Show only soft deleted contacts'));
        
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Quick User Search');
         
        /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        $form->assign( 'elements', array( 'first_name', 'last_name','external_identifier'));
    }

    function all($offset = 0, $rowcount = 0, $sort = NULL,
      $includeContactIDs = FALSE) {
      $selectClause = "
Distinct(contact_a.id) as contact_id, '' as pc_name, '' as conf_name, contact_a.display_name as donor_name,
contact_a.external_identifier as external_identifier, email.email as email, activated__48, nsf, NULL as contribution_status_id ";
          
      if (CRM_Utils_Array::value('external_identifier', $this->_formValues)) {
        $selectClause .= ", cast(SUBSTRING_INDEX(REPLACE(contact_a.external_identifier, 'D-', ''),'-',1) as unsigned) as int_external_identifier";
        $sort = 'int_external_identifier, contact_id';
      }

      return $this->sql( 
        $selectClause,
        $offset, 
        $rowcount, 
        $sort,
        $includeContactIDs, 
        NULL 
      );
    }
    
    function from( ) {
        
        return "

FROM civicrm_contact AS contact_a

INNER JOIN custom_relatedContacts AS donor_rel ON ( contact_a.id = donor_rel.related_id )

LEFT JOIN civicrm_contact AS admin_cc ON ( admin_cc.id = donor_rel.contact_id )

LEFT JOIN civicrm_group_contact  AS supporter   ON ( contact_a.id = supporter.contact_id AND supporter.status = 'Added' )

LEFT JOIN civicrm_email  AS email  ON ( email.contact_id = contact_a.id AND email.is_primary = 1 )
LEFT JOIN civicrm_value_is_online_17 online ON online.entity_id = contact_a.id 
LEFT JOIN civicrm_log_par_donor clpd ON clpd.primary_contact_id = contact_a.id

 ";
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
        $isdeleted = 0;
        if (CRM_Utils_Array::value('show_deleted_contacts', $this->_formValues)) {
          $isdeleted = 1;          
        }
        if ( CRM_Contact_BAO_GroupContact::isContactInGroup( $loggedIn, SYS_ADMIN_GROUP_ID ) ) {
            $where  = " contact_a.contact_type ='Individual' AND contact_a.is_deleted = {$isdeleted} AND supporter.group_id = 3";// AND admin_cc.id = ".$loggedIn;
        } else if (  CRM_Contact_BAO_GroupContact::isContactInGroup( $loggedIn, DENOMINATION_ADMIN_GROUP_ID ) ) {
            $where  = " contact_a.contact_type ='Individual' AND contact_a.is_deleted = {$isdeleted} AND supporter.group_id = 3 AND admin_cc.id = ".$loggedIn;
        }
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
        $external_identifier = CRM_Utils_Array::value( 'external_identifier',
                                                       $this->_formValues );
        if ( $external_identifier != null ) {
            if ( strpos($external_identifier , '%' ) === false ) {
              $external_identifier = 'D-' . str_replace('d-', '', strtolower($external_identifier)) . '%';
            }
            $params[$count] = array( $external_identifier, 'String' );
            $clause[] = "contact_a.external_identifier LIKE %{$count}";
            $count++;
        }
        if ( ! empty( $clause ) ) {
            $where .= ' AND ' . implode( ' AND ', $clause );
        }
        return $this->whereClause( $where, $params );
    }

    function templateFile( ) {
      unset($this->_columns['dont Care']);
      $headers =& CRM_Core_Smarty::singleton()->get_template_vars('columnHeaders');
      unset($headers[5]);
      $rows =& CRM_Core_Smarty::singleton()->get_template_vars('rows');
      $permissions = array(CRM_Core_Permission::getPermission());
      $mask = CRM_Core_Action::mask($permissions);
      $formlinks = self::links();
      $formlinks[CRM_Core_Action::UPDATE]['url'] = 'civicrm/profile/edit';
      $formlinks[CRM_Core_Action::UPDATE]['qs'] = 'reset=1&gid=13&id=%%id%%';
      foreach ($rows as $key => $row) {
        $links = $formlinks;
        if (empty($row['activated__48'])) {
          unset($links[CRM_Core_Action::UPDATE]);
        }
        $rows[$key]['action'] = CRM_Core_Action::formLink( 
          $links,
          $mask ,
          array( 'id' => $row['contact_id']) 
        );
        unset($row['activated__48']);
      }
      return 'CRM/Contact/Form/Search/Custom/UserSearch.tpl';
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
        require_once 'api/api.php';
        require_once 'CRM/Contribute/PseudoConstant.php';
        $status = CRM_Contribute_PseudoConstant::contributionStatus();
        $params = array( 
                        'contact_id_a' => $row['contact_id'],
                        'relationship_type_id' => SUPPORTER_RELATION_TYPE_ID,
                        'is_active' => 1,
                        'version' => 3,
                         );
        $result = civicrm_api( 'relationship','get',$params );
        
        if ( !empty( $result['values'] ) ) {
            $includes = $result['values'][$result['id']]['contact_id_b'];
            $params = array( 
                            'id' => $includes,
                            'version' => 3,
                             );
            $result = civicrm_api( 'contact','get',$params );
            $contactSubType = $result['values'][$result['id']]['contact_sub_type'];
            $displayName = $result['values'][$result['id']]['display_name'];
            $organizations = getOrganizations( $includes, $contactSubType, $displayName);
            $row['pc_name'] = $organizations['Pastoral_Charge'];
            $row['conf_name'] = $organizations['Conference'];
        } else {
            $row['pc_name'] = null;
            $row['conf_name'] = null;
        }
        
        $contributionStatus = CRM_Core_DAO::singleValueQuery('SELECT contribution_status_id FROM civicrm_contribution_recur WHERE contact_id = ' . $row['contact_id'] . ' ORDER BY id DESC LIMIT 1');
        if ($contributionStatus) {
          $row['contribution_status_id'] = CRM_Utils_Array::value($contributionStatus, $status);
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
