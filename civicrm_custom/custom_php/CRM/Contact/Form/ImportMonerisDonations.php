<?php
require_once 'CRM/Core/Form.php';

class CRM_Contact_Form_ImportMonerisDonations extends CRM_Core_Form {  
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {}
  
  public function setDefaultValues() {} 
  
  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $uploadFileSize = $this->formatUnitSize($config->maxFileSize . 'm', TRUE);
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
    $this->assign('uploadSize', $uploadSize);
    $this->add('File', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=255', TRUE);
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(
      1 => $uploadSize,
      2 => $uploadFileSize,
    )), 'maxfilesize', $uploadFileSize);
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'mimetype', array('text/csv', 'text/comma-separated-values'));
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');
    $this->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');

    $this->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers')); 
    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => ts('Import ...'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ),
    ));
  }
  
  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {}

  /**
   * Format size.
   *
   */
  public static function formatUnitSize($size, $checkForPostMax = FALSE) {
    if ($size) {
      $last = strtolower($size{strlen($size) - 1});
      switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0

        case 'g':
          $size *= 1024;
        case 'm':
          $size *= 1024;
        case 'k':
          $size *= 1024;
      }

      if ($checkForPostMax) {
        $maxImportFileSize = self::formatUnitSize(ini_get('upload_max_filesize'));
        $postMaxSize = self::formatUnitSize(ini_get('post_max_size'));
        if ($maxImportFileSize > $postMaxSize && $postMaxSize == $size) {
          CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
        }
        //respect php.ini upload_max_filesize
        if ($size > $maxImportFileSize && $size !== $postMaxSize) {
          $size = $maxImportFileSize;
          CRM_Core_Session::setStatus(ts("Note: Please verify your configuration for Maximum File Size (in MB) <a href='%1'>Administrator >> System Settings >> Misc</a>. It should support 'upload_max_size' as defined in PHP.ini.Please check with your system administrator.", array(1 => CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1'))), ts("Warning"), "alert");
        }
      }
      return $size;
    }
  }
  
  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = 
      $this->_params = $this->controller->exportValues($this->_name);
    $file = $params['uploadFile']['name'];
    $fd = fopen($file, 'r');
    if (CRM_Utils_Array::value('skipColumnHeader', $params)) {
      $firstrow = fgetcsv($fd, 0);
    }
    $error = array();
    require_once 'CRM/Contribute/PseudoConstant.php';
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contributionParams = array(
      2 => array(array_search('In Progress', $contributionStatus), 'Integer'),
    );
    $isImported = FALSE;
    while ($row = fgetcsv($fd, 0)) {
      $customerID = $row[11];
      $orderId = $row[4];
      if (!$customerID || !$orderId) {
        $row['error'] = ts('Customer id or order id not present');
        $error[] = $row;
        continue;
      }
      list($msNumber, $donorId) = explode('_', $customerID);
      if (!$msNumber || !$donorId) {
        $row['error'] = ts('Ms Number or Donor id is Invalid');
        $error[] = $row;
        continue;        
      }
      $sql = 'SELECT cc.id FROM civicrm_contact cc 
        INNER JOIN civicrm_value_other_details_7 cv ON cv.entity_id = cc.id 
        WHERE cc.external_identifier LIKE %1 AND cv.ms_number_16 = %2
      ';
      $sqlParams = array(
        1 => array('D-' . $donorId, 'String'),
        2 => array($msNumber, 'Integer'),
      );
      $contactId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
      if (!$contactId) {
        $row['error'] = ts('No Donor found in database for customer id');
        $error[] = $row;
        continue;          
      }
      $contributionParams[1] = array($contactId, 'Integer');
      $sql = 'SELECT id FROM civicrm_contribution_recur 
        WHERE contact_id = %1 AND contribution_status_id = %2 AND payment_instrument_id = 1
        ORDER BY id DESC LIMIT 1';
      $contributionRecurId = CRM_Core_DAO::singleValueQuery($sql, $contributionParams);
      if (!$contributionRecurId) {
        $row['error'] = ts('No In Progress Donation found in database for this customer id');
        $error[] = $row;
        continue;          
      }
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_recur SET invoice_id = '$orderId' WHERE id = {$contributionRecurId}");
      $isImported = TRUE;
    }
    if ($isImported) {
      require_once('CRM/Contact/BAO/Group.php');
      $params = array(
        'source_contact_id' => 1,
        'activity_type_id' => IMPORT_MONERIS_REPORT_ACTIVITY_TYPE_ID,
        'assignee_contact_id' => array_keys(CRM_Contact_BAO_Group::getGroupContacts(SYSTEM_ADMIN)),
        'subject' => 'Import Moneris report',
        'activity_date_time' => date('Y-m-d H:i:s'),
        'status_id' => 2,
        'priority_id' => 2,
        'version' => 3,
        'attachFile_1' => array(
          'uri' => $file,
          'type' => 'text/csv',
          'location' => $file,
          'upload_date' => date('YmdHis'),
        ),
      );
      $result = civicrm_api('activity', 'create', $params);
      if (CRM_Utils_Array::value('id', $result)) {
       $url = CRM_Utils_System::url('civicrm/contact/view/activity', "action=view&reset=1&cid=1&id={$result['id']}&atype=" . IMPORT_MONERIS_REPORT_ACTIVITY_TYPE_ID);
       CRM_Core_Session::singleton()->replaceUserContext($url);
      }
    }
  }
}