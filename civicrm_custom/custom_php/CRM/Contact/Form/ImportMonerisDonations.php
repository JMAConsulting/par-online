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
  }
}