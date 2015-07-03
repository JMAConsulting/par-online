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
class CRM_Contact_Form_Task_Household_Merge extends CRM_Contact_Form_Task_Household {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form 
   *    
   * @access public
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Merge Contacts into Household'));
    $houseHold = array();
    $dao = self::getBankingContacts($this->_contactIds);
    if ($dao->N) {
      $rows = NULL;
      $ccType = array(VISA => ts('Visa'), MASTER_CARD => ts('MasterCard'));
      while ($dao->fetch()) {
        $this->addElement('radio', 'contact_id', '', '', $dao->id);
        $this->_bankContacts[] = $dao->id;
        $rows[$dao->id] = array(
          'contact_name' => $dao->sort_name,
          'account_number' => $dao->par_account_number_13,
          'cc_type' => (in_array($dao->bank_number_11, array(VISA, MASTER_CARD))) ? $ccType[$dao->bank_number_11] : ts('Direct Debit'),
          'bank_name' => $dao->bank_number_11,
          'branch_name' => $dao->branch_number_12,
        );
        $query = "SELECT cc1.id contact_id, cc1.last_name, cc1.first_name, cc1.external_identifier FROM civicrm_contact cc
LEFT JOIN civicrm_contact cc1 on cc.external_identifier = SUBSTRING_INDEX(cc1.external_identifier, '-', 2)
WHERE cc.external_identifier IS NOT NULL AND cc.id = {$dao->id} AND cc1.external_identifier IS NOT NULL AND cc1.id != {$dao->id}";
        
        $bankDAO = CRM_Core_DAO::executeQuery($query);
        if ($bankDAO->N) {
          $houseHold[] = $dao->id;
        } 
      }
      $this->assign('houseHold', implode(',', $houseHold));
      if ($dao->N > 1) {
        $text = ts('Select the person whose banking information should be used');
        $this->assign('rows', $rows);
      }
      else {
        $text = ts('All the contacts will be merged into "' . $dao->sort_name . '" Household');
      }
      $this->assign('text', $text);
      $this->addDefaultButtons(ts('Merge'));

    }
    $this->addFormRule(array('CRM_Contact_Form_Task_Household_Merge', 'formRule'), $this);
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
        $smarty =  CRM_Core_Smarty::singleton( );
    $errors = array();
   
    if (!CRM_Utils_Array::value('contact_id', $params)) {
      if (count($self->_bankContacts) > 1) {
        $errors['contact_id'] = ts('Please select atleast one contact whose banking information should be used');
      }
    }
    return $errors;
  }
    
  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();
    $params['otherHousehold'] = array();
    if (!CRM_Utils_Array::value('contact_id', $params)) {
      $params['contact_id'] = current($this->_bankContacts);
    }
    $id = array_search($params['contact_id'], $this->_bankContacts);
    unset($this->_bankContacts[$id]);
    if (!empty($this->_bankContacts)) {
      $sql = "SELECT cc.id contact_id FROM civicrm_contact cc
LEFT JOIN civicrm_contact cc1 on cc.external_identifier = SUBSTRING_INDEX(cc1.external_identifier, '-', 2)
WHERE cc.external_identifier IS NOT NULL AND cc.id IN (" . implode(',', $this->_bankContacts) . ') AND cc1.external_identifier IS NOT NULL AND cc1.id NOT IN (' . implode(',', $this->_bankContacts) . ') GROUP BY cc.id;';
      $dao = CRM_Core_DAO::executeQuery($sql);

      while ($dao->fetch()) {
        $params['otherHousehold'][] = $dao->contact_id;
      }
    }
       
    // set this if contact has households
    if (!count($params['otherHousehold'])) {
      CRM_Contact_Form_Task_Household::processContacts($params);
    }
    else {
      CRM_Core_Session::singleton()->set('numberOfContacts', count($params['otherHousehold']));
      $this->set('_params', $params);
    }
  } 
  function assignHousehold() {
    $contacts = array();
    if (!empty($_POST['contact'])) {
      $contacts = explode(',', $_POST['contact']);
    }
    $contacts = array_flip($contacts);
    if (array_key_exists($_POST['selectedId'], $contacts)) {
      unset($contacts[$_POST['selectedId']]);
    }
    CRM_Core_Session::singleton()->set('numberOfContacts', count($contacts));
  }
}



