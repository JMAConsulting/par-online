<?php

/* 
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
 
class WebTest_DirectDebit_ParServiceFee extends CiviSeleniumTestCase
{
  protected $captureScreenshotOnFailure = FALSE;
  protected $screenshotPath = '/home/mayur/public_html/drupal-7.7/';
  protected $screenshotUrl  = "http://192.168.2.68/~mayur/drupal-7.7";
  protected function setUp()
  {
      parent::setUp();
  }

  function testStructureCreate(){
      
      require_once 'CRM/Core/DAO.php';
      $this->open( $this->sboxPath );
      // Log in using webtestLogin() method
      $this->_webtestLogin($userName = "uccdrpl", $pass = "drpl@3250" );
      $this->click('crm-create-new-link');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      $this->waitForElementPresent( "css=.action-link" );
      $this->assertTrue( $this->isTextPresent( 'Service Charge Rates' ));
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      //edit parservice
      $this->click("xpath=//div[@class='form-item']/table/tbody/tr[5]/td[6]/span/a[1]");
      $this->waitForElementPresent( "_qf_ParServiceFees_next-botttom" );
      $this->assertTrue( $this->isTextPresent( 'Edit Par Service Fee' )); 
      $this->click("name");
      $this->type("name", 'ServiceFeeRateName');
      $this->click("transaction_fee");
      $this->type("transaction_fee", '0.70');
      $this->click("per_month_ceiling");
      $this->type("per_month_ceiling", '0.90');
      $this->click("_qf_ParServiceFees_next-botttom");
      $this->waitForElementPresent( "css=.action-link" );
      $this->assertTrue( $this->isTextPresent( 'Par Service Fee ServiceFeeRateName has been saved.'));
      //add parservice
      $this->click("xpath=//div[@class='action-link']/a");
      $this->waitForElementPresent( "_qf_ParServiceFees_next-botttom" );
      $this->assertTrue( $this->isTextPresent( 'New Par Service Fee' )); 
      $this->click("_qf_ParServiceFees_cancel-botttom");
      $this->waitForElementPresent( "css=.action-link" );
      $this->assertTrue( $this->isTextPresent( 'Service Charge Rates' ));
      $this->click("xpath=//div[@class='action-link']/a");
      $this->waitForElementPresent( "_qf_ParServiceFees_next-botttom" );
      $this->assertTrue( $this->isTextPresent( 'New Par Service Fee' )); 
      $this->click("name");
      $this->type("name", 'ServiceFeeName');
      $this->click("transaction_fee");
      $this->type("transaction_fee", '0.50');
      $this->click("per_month_ceiling");
      $this->type("per_month_ceiling", '0.75');
      $this->click("_qf_ParServiceFees_next-botttom");
      $this->waitForElementPresent( "_qf_ParServiceFees_next-botttom" );
      $this->waitForElementPresent( "css=.crm-error" );
      $this->assertTrue( $this->isTextPresent( 'Per month flat fee on direct debit transactions when number of transactions exceeds ceiling is a required field.'));
      $this->assertTrue( $this->isTextPresent( 'Credit card percentage fee is a required field.'));
      $this->click("flat_feet_ransactions_exceeds_ceiling");
      $this->type("flat_feet_ransactions_exceeds_ceiling", '0.50');
      $this->click("credit_card_percentage_fee");
      $this->type("credit_card_percentage_fee", '0.75');
      $this->click("_qf_ParServiceFees_next-botttom");
      $this->waitForElementPresent( "css=.action-link" );
      $this->assertTrue( $this->isTextPresent( 'Par Service Fee ServiceFeeName has been saved.'));
      //delete parservice
      $this->click("xpath=//div[@class='form-item']/table/tbody/tr[1]/td[6]/span/a[2]");
      $this->waitForElementPresent( "_qf_ParServiceFees_next-botttom" );
      $this->assertTrue( $this->isTextPresent( 'Delete Par Service Fees' )); 
      $this->click("_qf_ParServiceFees_next-botttom");
      $this->waitForElementPresent( "css=.action-link" );
      $this->assertTrue( $this->isTextPresent( 'Selected Par Service has been deleted successfully.')); 
      //paradmin
      $this->_webtestLogout();
      $this->_webtestLogin($userName = "brenda.bailie", $pass = "musu30in" );
      $this->_webtestCheckParservice();
      $this->_webtestLogout();
      //denomination admin
      $this->_webtestLogin($userName = "judith", $pass = "admin" );
      $this->_webtestCheckParservice();
      $this->_webtestLogout();
      //sys admin
      $this->_webtestLogin($userName = "uccdrpl", $pass = "drpl@3250" );
      $this->open( $this->sboxPath.'civicrm/profile/edit?reset=1&gid=15&id=7673');
      $this->waitForElementPresent( "crm-create-new-link" );
      $this->assertTrue( $this->isTextPresent( 'Pastoral Charge Information'));
      $this->_saveOrganizationInfo();
      $this->open( $this->sboxPath.'civicrm/profile/edit?reset=1&gid=14&id=13');
      $this->waitForElementPresent( "crm-create-new-link" );
      $this->assertTrue( $this->isTextPresent( 'Denomination Information'));
      $this->_saveOrganizationInfo();
  }
  function _saveOrganizationInfo(){
      $this->click('_qf_Edit_next');
      $this->waitForPageToLoad('30000');  
      $this->waitForElementPresent( "crm-create-new-link" );
      $this->assertTrue( $this->isTextPresent( 'Thank you. Your information has been saved.'));
  }
  function _webtestLogin( $userName, $pass ){
      $this->waitForElementPresent( "edit-name" );
      $this->click('edit-name');
      $this->type('edit-name', $userName );
      $this->click('edit-pass');
      $this->type('edit-pass', $pass );
      $this->click('edit-submit');
      $this->waitForPageToLoad('30000');  
      $this->open( $this->sboxPath . "civicrm" );
      $this->waitForPageToLoad('30000');  
      
  }
  function _webtestLogout(){
      $this->open( $this->sboxPath . "user/logout" );
      $this->waitForPageToLoad('30000');    
      $this->open( $this->sboxPath . "user" );
      $this->waitForPageToLoad('30000');
  }

  function _webtestCheckParservice() {
      $this->click('crm-create-new-link');
      $this->assertTrue( !$this->isTextPresent( 'Service Charge Rates' ));
  } 
}