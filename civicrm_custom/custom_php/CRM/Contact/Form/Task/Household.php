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

require_once 'CRM/Contact/Form/Task.php';

/**
 * This class helps to print the labels for contacts
 * 
 */
class CRM_Contact_Form_Task_Household extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->set('contactIds', $this->_contactIds);
    parent::preProcess();
  }

    
  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return void
   */
  public function processContacts($params) {
    $mainContactId = $params['contact_id'];
    
    $houseHoldId = CRM_Core_DAO::singleValueQuery("SELECT contact_id_b FROM civicrm_relationship WHERE contact_id_a = {$mainContactId} AND relationship_type_id = " . HEAD_OF_HOUSEHOLD);
    
    $query = "SELECT last_name, first_name FROM civicrm_contact WHERE id IN (" . implode(",", $this->_contactIds) . ")";
    $externalIdentifier = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $mainContactId, 'external_identifier');
    if (empty($houseHoldId)) {
      $houseHoldId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', str_replace('D-', 'H-', $externalIdentifier), 'id', 'external_identifier');
    }
    $houseHoldId = $this->createHouseHoldName($query, $houseHoldId, $externalIdentifier);
    // create relationship as head of household
    $this->createRelationship($mainContactId, $houseHoldId, HEAD_OF_HOUSEHOLD);
    // get external identifier of main contact
    $supporterId = CRM_Core_DAO::singleValueQuery("SELECT contact_id_b FROM civicrm_relationship WHERE relationship_type_id = " . SUPPORTER_RELATION_TYPE_ID . " AND contact_id_a = {$mainContactId} AND is_active = 1");
        
    require_once 'CRM/Contact/Form/Donation.php';
    foreach ($this->_contactIds as $contactID) { 
      $houseHoldCid = $this->changeRelatedHouseholds($contactID, $mainContactId, $contacts);
      if ($contactID != $mainContactId) {
        $_GET['cid'] = $contactID;
        
        // edge case if contribution status is on hold
        /* $_GET['mode'] = 'inprogress'; */
        /* CRM_Contact_Form_Donation::changeContriStatus(TRUE); */
       
        $_GET['mode'] = 'stop';
        CRM_Contact_Form_Donation::changeContriStatus(TRUE);
        unset($_GET['cid'], $_GET['mode']);
        $contacts[] = $contactID;
        $this->createRelationship($contactID, $houseHoldId, MEMBER_OF_HOUSEHOLD);
        $this->createRelationship($contactID, $supporterId, SUPPORTER_RELATION_TYPE_ID, TRUE); // UCCPAR - 393 Supporter Relationships
        $this->removeBankingDetails($contactID);
        $id = array_search($contactID, $params['otherHousehold']);
        if (in_array($contactID, $params['otherHousehold']) && CRM_Utils_Array::value($id, $params['household_member'])) {
          $addExtID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'external_identifier');
          $additionalParams = $params['household_member'][$id];
         
          CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET external_identifier = NULL WHERE id = {$contactID}");
          CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET external_identifier = '$addExtID' WHERE id = {$additionalParams['monthly_contact_id']}");
          CRM_Core_DAO::executeQuery("UPDATE civicrm_log_par_donor SET primary_contact_id = {$additionalParams['monthly_contact_id']}, log_time = now() WHERE external_identifier = '". str_replace('D-', '', $addExtID) . "'");
          $addContacts = $additionalParams['_contacts'];
          $resetId = array_search($additionalParams['monthly_contact_id'], $addContacts);
          unset($addContacts[$resetId]);
          $this->createRelationship($additionalParams['monthly_contact_id'], $houseHoldCid, HEAD_OF_HOUSEHOLD);
          if (CRM_Utils_Array::value('monthly_donation', $additionalParams) == 'previous') {
            $_GET['cid'] = $additionalParams['monthly_contact_id'];
            CRM_Contact_Form_Donation::saveContribution($additionalParams, TRUE);
            $_GET['cid'] = '';
          }
          // change external identifier
          if (!empty($addContacts)) {
            $c = 1;
            CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET external_identifier = NULL WHERE id IN (" . implode(',', $addContacts) . ")");
            foreach ($addContacts as $aCid) {
              CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET external_identifier = '" . $addExtID . '-' . $c . "' WHERE id = {$aCid}");
              $c++;
            }    
          }
        }
      }
    }
    if (empty($contacts)) {
      CRM_Core_Session::setStatus(ts('Supporters successfully merged.'));
      return;
    }
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET external_identifier = NULL WHERE id IN (" . implode(',', $contacts) . ")");
    $count = 1;
    $contacts = array_unique($contacts);
    foreach ($contacts as $contactID) {
      CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $contactID, 'external_identifier', $externalIdentifier . '-' . $count);
      $count++;
    }
    CRM_Core_Session::singleton()->set('numberOfContacts', '');
    CRM_Core_Session::setStatus(ts('Supporters successfully merged.'));
  } 
  
  public function getBankingContacts($inContactIds, $selectFields = array()) {
    $where = implode(',', $inContactIds);
    $selectFields += array(
      'ct.sort_name', 
      'ct.id', 
      'IFNULL(par_account_number_13, "-") par_account_number_13', 
      'IFNULL(bank_number_11, "-") bank_number_11', 
      'IFNULL(branch_number_12, "-") branch_number_12'
    );
    $select = implode(',', $selectFields);
    $query = "SELECT $select FROM `civicrm_value_par_account_details_6` cb
INNER JOIN civicrm_contact ct ON ct.id = cb.entity_id
WHERE ct.id IN ($where)
GROUP BY ct.id";
    return CRM_Core_DAO::executeQuery($query);
  }
 
  public function createRelationship($contactA, $contactB = NULL, $relType = NULL, $supporter = FALSE) {
    $sql = "SELECT id FROM civicrm_relationship WHERE contact_id_a = {$contactA} AND is_active = 1 AND relationship_type_id ";
    if ($supporter) {
      $sql .= " = {$relType}";
    }
    else {
      $sql .= " IN (" . HEAD_OF_HOUSEHOLD . "," . MEMBER_OF_HOUSEHOLD . ")";
    }
    $id = CRM_Core_DAO::singleValueQuery($sql);
    if ($id) {
      $params = array( 
        'version' => 3,
        'id' => $id,
        'is_active' => 0,
        'end_date' => date('Ymd'),
      );
      civicrm_api('relationship', 'create', $params);
    }
    if ($contactB) {
      $params = array( 
        'version' => 3,
        'contact_id_a' => $contactA,
        'relationship_type_id' => $relType,
        'is_active' => 1,
        'start_date' => date('Ymd'),
        'contact_id_b' => $contactB,
        'end_date' => NULL
      );
      $relationship = civicrm_api('relationship', 'create', $params);
      if ($relationship['is_error'] && $relationship['error_message'] == 'Relationship already exists') {
        $sql = 'SELECT id FROM civicrm_relationship WHERE contact_id_a = %1 AND contact_id_b = %2 AND relationship_type_id = %3';
        $queryParams = array(
          1 => array($contactA, 'Integer'),
          2 => array($contactB, 'Integer'),
          3 => array($relType, 'Integer'),
        );
        $params['id'] = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
        
        $relationship = civicrm_api('relationship', 'create', $params);
      }
    }
  }
  
  public function removeBankingDetails($contactID) {
    $query = "DELETE cv FROM civicrm_value_account_details_2 cv
INNER JOIN civicrm_contribution cc ON cc.id = cv.entity_id 
WHERE cc.contact_id = {$contactID}";
    CRM_Core_DAO::executeQuery($query);

    $query = "DELETE cv FROM `civicrm_value_par_account_details_6` cv
INNER JOIN civicrm_contact cc ON cc.id = cv.entity_id 
WHERE cc.id = {$contactID}";
    CRM_Core_DAO::executeQuery($query);
  }

  public function changeRelatedHouseholds($contactId, $cid, &$contacts) {
    $houseHoldCid = NULL;
    $query = "SELECT cc1.id contact_id, cc1.last_name, cc1.first_name FROM civicrm_contact cc
LEFT JOIN civicrm_contact cc1 on cc.external_identifier = SUBSTRING_INDEX(cc1.external_identifier, '-', 2)
WHERE cc.external_identifier IS NOT NULL AND cc.id = {$contactId} AND cc1.external_identifier IS NOT NULL AND cc1.id != {$contactId}";
    if ($contactId == $cid) {
      $dao = CRM_Core_DAO::executeQuery($query);
      while($dao->fetch()) {
        $contacts[] = $dao->contact_id;
      }
    }
    else {
      $houseHoldCid = CRM_Core_DAO::singleValueQuery("SELECT contact_id_b FROM civicrm_relationship WHERE contact_id_a = {$contactId} AND relationship_type_id = " . HEAD_OF_HOUSEHOLD);
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->N == 1) {
        $params = array(
          'contact_id' => $houseHoldCid,
          'id' => $houseHoldCid,
          'contact_type' => 'Household',
          'is_deleted' => 1,
          'version' => 3,
        );
        civicrm_api('contact', 'create', $params);
        //disable relationship to household
        CRM_Contact_Form_Task_Household::createRelationship($contactId);
        $houseHoldCid = NULL;
      }
      else {
        $houseHoldCid = $this->createHouseHoldName($query, $houseHoldCid);
      }
    }
    return $houseHoldCid;
  }

  public function createHouseHoldName($query, $contactID = NULL, $externalIdentifier = '') {
    if (empty($query)) {
      return FALSE;
    }
    
    $contactDAO = CRM_Core_DAO::executeQuery($query);
    if ($contactDAO->N < 2) {
      return NULL;
    }
    $name = NULL;
    $records = array();
    while ($contactDAO->fetch()) {
      $records[trim($contactDAO->last_name)][] = trim($contactDAO->first_name);
    }
    foreach ($records as $key => $value) {
      if (!empty($name)) {
        $name = $name." & ".$key.", ".implode(' & ' , $value);
      } 
      else {
        $name = $key.", ".implode(' & ' , $value);
      }
    } 
    if (!$name) {
      return false;
    }
    /*
    $houseHoldQuery = "SELECT household_name FROM civicrm_contact WHERE household_name LIKE %1";
    $qParams = array(1 => array($name, 'String', TRUE));
    $dao = CRM_Core_DAO::executeQuery($houseHoldQuery, $qParams);
    if ($dao->N) {
      $name = $name . "-" . $dao->N;
    }
    */
    if ($externalIdentifier) {
      $externalIdentifier = str_replace('D-', 'H-', $externalIdentifier);
    }
    elseif ($contactID) {
      $externalIdentifier = CRM_Core_DAO::singleValueQuery('SELECT external_identifier FROM civicrm_contact WHERE id = ' . $contactID);
    }
    $contactParams = array(
      'household_name' => $name,
      'sort_name' => $name,
      'display_name' => $name,
      'external_identifier' => $externalIdentifier,
      'version' => 3,
      'contact_type' => 'Household',
    );
    if ($contactID) {
      $contactParams['id'] = $contactID;
    }
    $result = civicrm_api('contact', 'create', $contactParams);
    if ($externalIdentifier) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_log_par_donor SET par_donor_name = %1, log_time = now() WHERE external_identifier = %2', array(1 => array($name, 'String'), 2 => array(str_replace('H-', '', $externalIdentifier), 'Integer')));
    }
    return $result['id'];
  }
}



