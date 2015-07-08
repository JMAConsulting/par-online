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
  public function buildQuickForm() {}
  
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
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {}
}