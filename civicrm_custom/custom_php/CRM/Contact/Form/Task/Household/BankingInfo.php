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

require_once 'CRM/Contact/Form/Task/Household.php';

/**
 * This class helps to print the labels for contacts
 * 
 */
class CRM_Contact_Form_Task_Household_BankingInfo extends CRM_Contact_Form_Task_Household {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $this->_params = $this->get('_params');
  }

  /**
   * Build the form 
   *    
   * @access public
   * @return void
   */
  function buildQuickForm() {
    $id = str_replace('houseHold_', '' , $this->_name);
    if ($id >= 0) {
      $contactId = $this->_params['otherHousehold'][$id];
    }
    $this->assign('mainContactId', $contactId);
    $contactName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'display_name');
    CRM_Utils_System::setTitle(ts('For ' . $contactName));
    
    $choice['monthly'] = $this->createElement('radio', NULL, '', 'No monthly donation', 'no_donation');
    $choice['previous'] = $this->createElement('radio', NULL, '', 'Defaulted to previous monthly donation of household', 'previous');
    $this->addGroup($choice, 'monthly_donation', 'Please select a monthly donation option for the household above:');
    $this->setDefaults(array( 'monthly_donation' => 'previous' ));
    $contactIds = array();
    $this->changeRelatedHouseholds($contactId, $contactId, $contactIds);
    if (!empty($contactIds)) {
      $query = "SELECT id, display_name FROM civicrm_contact WHERE id IN (" . implode(',', $contactIds) . ")";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $this->_contacts[] = $dao->id;
        $contact[] = $this->createElement('radio', NULL, '', $dao->display_name, $dao->id);
      }
      $this->addGroup($contact, 'monthly_contact_id', 'Please select the new head for the folowing household:');
    }
    $this->addDefaultButtons(ts('Save'));
    $this->addFormRule(array('CRM_Contact_Form_Task_Household_BankingInfo', 'formRule'), $this);
  }
  
  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($params, $files, $self) {
    $errors = array();
    return $errors;
  }
    
  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return void
   */
  public function postProcess() {
    $params = $this->_submitValues;
    $count = count($this->_params['otherHousehold']);
    $params['_contacts'] = $this->_contacts;
    $params['amount'] =NULL;
    foreach ($params as $key => $value) {
      if (strstr($key, 'price')) {
        $params['amount'] = $params['amount'] + $value;
      }
    }
    //switch the preimary contribution params to selected household - start
    $getParams = array( 
      'version' => 3,
      'contact_id_a' => $params['monthly_contact_id'],
      'relationship_type_id' => SUPPORTER_RELATION_TYPE_ID,
      'is_active' => 1,
    );
    require_once 'api/api.php';
    $supporterRelation = civicrm_api('relationship', 'get', $getParams);
    $supporterId = NULL;
    if (CRM_Utils_Array::value('id', $supporterRelation)) {
      $supporterId = $supporterRelation['values'][$supporterRelation['id']]['contact_id_b'];
    }
    $parent_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_type WHERE contact_id = ".$supporterId." AND parent_id IS NULL");
    if ($parent_id) {
      $params['contribution_type'] = $parent_id;
    }
    $paramsPrice['contribution_type_id'] = $parent_id;
    $DAO = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contribution_type WHERE contact_id = ".$supporterId." AND parent_id IS NOT NULL");
    if ($DAO->N) {
      while ($DAO->fetch()) {
        $DAOset = CRM_Core_DAO::executeQuery("SELECT id, price_set_id FROM civicrm_price_field WHERE contribution_type_id = ".$DAO->id);
        while ($DAOset->fetch()) {
          $params['pricesetid'] = $DAOset->price_set_id;
          $priceFields[] = 'price_'.$DAOset->id;
        }
      }
    } 
    $priceCount = 0;
    foreach ($params as $key => $value) {
      if (strstr($key, 'price_')) {
        $params[$priceFields[$priceCount]] = $value;
        unset($params[$key]);
        $priceCount++; 
      }
    }
    //switch the preimary contribution params to selected household - end
    $this->_params['household_member'][] = $params;    
    
    if (str_replace('houseHold_', '', $this->_name) == ($count - 1)) {
      // call function to create all
      CRM_Contact_Form_Task_Household::processContacts($this->_params);
      //CRM_Core_Session::singleton()->set('numberOfContacts', '');
    }
    $this->set('_params', $this->_params);
  }
}



