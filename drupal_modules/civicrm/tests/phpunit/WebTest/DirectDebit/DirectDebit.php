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
 
class WebTest_DirectDebit_DirectDebit extends CiviSeleniumTestCase
{
  protected $captureScreenshotOnFailure = FALSE;
  protected $screenshotPath = '/home/darshan/public_html/direct-debit-server/';
  protected $screenshotUrl  = "http://192.168.2.102/~darshan/direct-debit-server";
  protected function setUp()
  {
      parent::setUp();
  }

  function testStructureCreate(){
      //Denominations-> webtestUCC, webtestnonUCC
      //UCC Conference-> webtestconf
      //UCC Presbytery-> webtestpresb
      //UCC Pastoral Charge-> webtestpc1, webtestpc2
      //UCC Congregation-> webtestcongregation1
      
      require_once 'CRM/Core/DAO.php';
      $this->open( $this->sboxPath );
      // Log in using webtestLogin() method
      $this->webtestLogin();

      //Create UCC structure
      //Create UCC Denomination
      $denoName = 'webtestUCC'.rand();
      $this->_webtestAddDenomination( $denoName, $denoName.'@webtest.com' );
      
      //Create UCC Conference
      $confName = 'webtestconf'.rand();
      $this->_webtestAddConf( $confName, $confName.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $denoName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      //Create UCC Presbytery
      $presbName = 'webtestpresb'.rand();
      $this->_webtestAddPresb( $presbName, $presbName.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $confName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      //Create UCC Pastoral Charge
      $pcName = 'webtestpc1'.rand();
      $this->_webtestAddPC( $pcName, $pcName.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $presbName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      //Create UCC Local Par Admin
      $pcAdmin1Name = 'paradmintest1'.rand();
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'M&S', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'General', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'Other', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
     
      
      //Create UCC Pastoral Charge
      $pcName2 = 'webtestpc2'.rand();
      $this->_webtestAddPC( $pcName2, $pcName2.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $presbName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'M&S', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'General', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'Other', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      
      //Create UCC Congregation
      $congName1 = 'webtestcongregation1'.rand();
      $this->_webtestAddCong( $congName1, $congName1.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $pcName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'M&S', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'General', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'Other', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      //Create UCC Congregation
      $congName2 = 'webtestcongregation2'.rand();
      $this->_webtestAddCong( $congName2, $congName2.'@webtest.com' );
      $this->waitForPageToLoad('30000');    
      $this->_addRel( '10_a_b', $pcName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'M&S', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'General', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      $query = "INSERT INTO `civicrm_contribution_type` (`id`, `name`, `accounting_code`, `description`, `is_deductible`, `is_reserved`, `is_active`, `contact_id`, `parent_id`) VALUES (NULL, 'Other', '55', (select display_name from civicrm_contact ORDER BY `id` DESC LIMIT 1), '1', NULL, '1', (select id from civicrm_contact ORDER BY `id` DESC LIMIT 1), NULL)";
      $dao = CRM_Core_DAO::executeQuery( $query );
      

      //Create Denomination Admin
      $denoAdminName = 'uccdenoadmin'.rand();
      $this->_webtestCreateCMSUser( $denoAdminName, $denoAdminName.'@admin.com', 'admin', $denoAdminName, 'admin'  );
      //Create denomination admin relationship
      $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      $this->waitForPageToLoad('30000');        
      $this->click('sort_name');
      $this->type('sort_name', $denoAdminName );
      $this->select( 'contact_type', 'value=Individual' );
      $this->click('_qf_Basic_refresh');
      $this->waitForPageToLoad('30000');    
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->_addRel( '12_a_b', $denoName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      //Create UCC Local Par Admin      
      $this->_webtestCreateCMSUser( $pcAdmin1Name, $pcAdmin1Name.'@admin.com', 'admin', $pcAdmin1Name, 'admin'  );
      $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      $this->waitForPageToLoad('30000');        
      $this->click('sort_name');
      $this->type('sort_name', $pcAdmin1Name );
      $this->select( 'contact_type', 'value=Individual' );
      $this->click('_qf_Basic_refresh');
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->_addRel( '11_a_b', $pcName );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );

      $pcAdmin2Name = 'paradmintest2'.rand();
      $this->_webtestCreateCMSUser( $pcAdmin2Name, $pcAdmin2Name.'@admin.com', 'admin', $pcAdmin2Name, 'admin'  );
      $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      $this->waitForPageToLoad('30000');
      $this->click('sort_name');
      $this->type('sort_name', $pcAdmin2Name );
      $this->select( 'contact_type', 'value=Individual' );
      $this->click('_qf_Basic_refresh');
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->_addRel( '11_a_b', $pcName );
      $this->assertTrue( $this->isTextPresent( "Pastoral Charge already has a Admin" ) );
      $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      $this->waitForPageToLoad('30000');
      $this->click('sort_name');
      $this->type('sort_name', $pcAdmin2Name );
      $this->select( 'contact_type', 'value=Individual' );
      $this->click('_qf_Basic_refresh');
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->_addRel( '11_a_b', $pcName2 );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );

      //Create UCC Local Par Admin for Congregation
      $pcAdmin3Name = 'paradmintest3'.rand();
      $this->_webtestCreateCMSUser( $pcAdmin3Name, $pcAdmin3Name.'@admin.com', 'admin', $pcAdmin3Name, 'admin'  );
      $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      $this->waitForPageToLoad('30000');
      $this->click('sort_name');
      $this->type('sort_name', $pcAdmin3Name );
      $this->select( 'contact_type', 'value=Individual' );
      $this->click('_qf_Basic_refresh');
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      $this->_addRel( '11_a_b', $congName1 );
      $this->assertTrue( $this->isTextPresent( "1 new relationship record created. " ) );

      //Create Non UCC structure
      // $nuccDenoName = 'webtestnonUCC'.rand();
      // $this->_webtestAddDenomination( $nuccDenoName, $nuccDenoName.'@webtest.com' );
      // $this->waitForPageToLoad('30000');    
      // $nonuccdenoAdminName = 'nonuccdenoadmin'.rand();
      // $this->_webtestCreateCMSUser( $nonuccdenoAdminName, $nonuccdenoAdminName.'@admin.com', 'admin', $nonuccdenoAdminName, 'admin'  );
      // $this->waitForPageToLoad('30000');    
      // $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      // $this->waitForPageToLoad('30000');        
      // $this->click('sort_name');
      // $this->type('sort_name', $nonuccdenoAdminName );
      // $this->select( 'contact_type', 'value=Individual' );
      // $this->click('_qf_Basic_refresh');
      // $this->waitForPageToLoad('30000');    
      // $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      // $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");      
      // $this->_addRel( '12_a_b', $nuccDenoName );
      // $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      // $nuccPCName = 'webtestnonucpc'.rand();;
      // $this->_webtestAddPC( $nuccPCName, $nuccPCName.'@webtest.com' );
      // $this->waitForPageToLoad('30000');    
      // $this->_addRel( '10_a_b', $nuccDenoName );
      // $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      // $nonuccParAdminName = 'nonuccparadmin'.rand();
      // $this->_webtestCreateCMSUser( $nonuccParAdminName, $nonuccParAdminName.'@admin.com', 'admin', $nonuccParAdminName, 'admin'  );
      // $this->open( $this->sboxPath . "civicrm/contact/search?reset=1" );
      // $this->waitForPageToLoad('30000');        
      // $this->click('sort_name');
      // $this->type('sort_name', $nonuccParAdminName );
      // $this->select( 'contact_type', 'value=Individual' );
      // $this->click('_qf_Basic_refresh');
      // $this->waitForPageToLoad('30000');    
      // $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
      // $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a");
 
      // $this->_addRel( '11_a_b', $nuccDenoName );
      // $this->waitForPageToLoad('30000');    
      // $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );
      // $nuccCongName = 'webtestnonucccongregation'.rand();;
      // $this->_webtestAddCong( $nuccCongName, $nuccCongName.'@webtest.com' );
      // $this->waitForPageToLoad('30000');    
      // $this->_addRel( '10_a_b', $nuccPCName );
      // $this->assertTrue( $this->isTextPresent( "1 new relationship record created." ) );

      //log out
      $this->_webtestLogout();
      //login by deno admin
      $this->_webtestLogin( $denoAdminName );
      $this->waitForElementPresent("css=#quick_charge_search .crm-submit-buttons #_qf_Custom_refresh-bottom");
      $this->click("css=#quick_charge_search .crm-submit-buttons #_qf_Custom_refresh-bottom");
      $this->waitForElementPresent("css=.crm-search-results");
      $this->assertTrue( $this->isTextPresent( $congName1 ));
      $this->assertTrue( $this->isTextPresent( $congName2 ));
      $this->click('css=#crm-create-new-link span');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->assertTrue( $this->isTextPresent( "Quick User Search" ));
      $this->click("css=#quick_user_search .crm-submit-buttons #_qf_Custom_refresh-bottom");
      $this->waitForPageToLoad('30000');
      $suppoter3 = 'webSupporter3'.rand();
      $donorInfo = array( 'fName'  => $suppoter3,
                          'lName'  => 'ucc');
      $this->_webtestAddDonor( $donorInfo, true );
      $this->assertTrue( $this->isTextPresent( "Thank you. Your information has been saved." ));
      $suppoter4 = 'webSupporter4'.rand();
      $donorInfo = array( 'fName'  => $suppoter4,
                          'lName'  => 'ucc');
      $this->_webtestAddDonor( $donorInfo, false, $congName1 );
      $this->assertTrue( $this->isTextPresent( "Thank you. Your information has been saved." ));
      $this->click('css=#crm-create-new-link span');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->click("css=#quick_user_search .crm-submit-buttons #_qf_Custom_refresh-bottom");
      $this->waitForElementPresent( "css=.crm-content-block .crm-search-results" );
      $this->assertTrue( $this->isTextPresent( $suppoter3 ));
      $this->assertTrue( $this->isTextPresent( $suppoter4 ));
      //log out
      $this->_webtestLogout();
      
      //login by local par admin
      $this->_webtestLogin( $pcAdmin1Name );
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->assertTrue( $this->isElementPresent('css=.widget-content #donation table'));
      $this->assertTrue( !$this->isElementPresent('css=.widget-content #donation table tbody tr') );
      $this->assertTrue( !$this->isTextPresent( "Quick Charge Search" ));
      $this->assertTrue( $this->isTextPresent( "Quick Search" ));

      //$this->click('_qf_Custom_refresh-bottom');
      //$this->waitForPageToLoad('30000');  
      //$this->assertTrue( $this->isTextPresent( "No matches found." ));


      //create Supporters
      $suppoter1 = 'webSupporter1'.rand();
      $donorInfo = array( 'fName'  => $suppoter1,
                          'lName'  => 'ucc');
      $this->_webtestAddDonor( $donorInfo );
      $this->waitForPageToLoad('30000');  
      $suppoter2 = 'webSupporter2'.rand();
      $donorInfo = array( 'fName'  => $suppoter2,
                          'lName'  => 'ucc');
      $this->_webtestAddDonor( $donorInfo );
      $this->click('css=#crm-create-new-link span');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->click('_qf_Custom_refresh-bottom');
      $this->waitForPageToLoad('30000');
      $this->waitForElementPresent("css=.crm-content-block .crm-results-block .crm-search-results");
      $this->assertTrue( $this->isTextPresent( $suppoter1 ));
      $this->assertTrue( $this->isTextPresent( $suppoter2 ));
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[7]/span/a[3]");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[7]/span/a[3]");
      $this->waitForElementPresent("addNewRow");
      $this->click("addNewRow");
      $this->waitForElementPresent("contribution_type-1");
      $this->select("contribution_type-1", 'label=Other');
      $this->click("amount-1");
      $this->type("amount-1", '23');
      $this->click("bank-1");
      $this->type("bank-1", '231');
      $this->click("branch-1");
      $this->type("branch-1", '21321');
      $this->click("account-1");
      $this->type("account-1", '213213213213');
      $this->click("save");
      sleep(2);
      //log out
      $this->_webtestLogout();
      //login by deno admin
      $this->_webtestLogin( $denoAdminName );
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->assertTrue( $this->isTextPresent( "Other" ));
      $this->assertTrue( $this->isTextPresent( "23.00" ));
      //log out
      $this->_webtestLogout();
      //login by deno admin
      $this->_webtestLogin( $pcAdmin1Name );
      $this->waitForElementPresent( "css=.widget-content #donation table" );
      $this->assertTrue( $this->isTextPresent( "Other" ));
      $this->assertTrue( $this->isTextPresent( "23.00" ));
      
      $this->click('_qf_Custom_refresh-bottom');
      $this->waitForPageToLoad('30000');
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[7]/span/a[3]");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr[3]/td[7]/span/a[3]");
      $this->waitForElementPresent("addNewRow");
      $this->click('css=#crm-contact-actions-link span');

      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[3]/a");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "Donor deleted successfully, only users with the relevant permission will be able to restore it." ));

      $this->click('_qf_Custom_refresh-bottom');
      $this->waitForPageToLoad('30000');
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr[1]/td[7]/span/a[3]");
      $this->waitForElementPresent("addNewRow");
      $this->click("addNewRow");
      $this->waitForElementPresent("contribution_type-1");
      $this->select("contribution_type-1", 'label=Other');
      $this->click("amount-1");
      $this->type("amount-1", '23');
      $this->click("bank-1");
      $this->type("bank-1", '231');
      $this->click("branch-1");
      $this->type("branch-1", '21321');
      $this->click("account-1");
      $this->type("account-1", '213213213213');
      $this->click("save");
      sleep(2);

      $this->click('css=#crm-contact-actions-link span');
      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[4]/a");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "All In progress recurring contributions set to On hold successfully" ));      
      $this->click('css=#crm-contact-actions-link span');

      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[5]/a");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "All On Hold recurring contributions set to In Progress successfully" )); 
      $this->click('css=#crm-contact-actions-link span');

      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[6]/a");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "All In progress recurring contributions set to Stopped successfully" )); 
      $this->click('css=#crm-contact-actions-link span');

      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[3]/a");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "Donor has the financial transactions" ));
      $this->click('css=#crm-create-new-link span');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[3]/a");
      $this->waitForElementPresent( "css=.widget-content #donation table" );                                                                              
      $this->click("_qf_Custom_refresh-bottom");
      $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[7]/span/a[3]");
      $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr[1]/td[7]/span/a[1]");
      $this->waitForPageToLoad('30000');
      $this->click('css=#crm-contact-actions-link span');
      $this->click("xpath=//div[@id='crm-contact-actions-list']/div/div/ul/li[1]/a");
      $this->waitForPageToLoad('30000');
      $this->waitForElementPresent( "_qf_Edit_next" );   
      $this->click("middle_name");
      $this->type("middle_name", "testmiddle");
      $this->click("_qf_Edit_next");
      $this->waitForPageToLoad('30000');
      $this->assertTrue( $this->isTextPresent( "Thank you. Your information has been saved." ));
      $this->waitForElementPresent("css=.individual-profile");      
      
      $this->assertTrue( $this->isTextPresent( "testmiddle" ));
      $this->waitForElementPresent( "css=li#tab_donation a" );
      $this->click("css=li#tab_donation a");
      $this->waitForElementPresent( "addNewRow" );
      $this->click( "addNewRow" );
      $this->click("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[3]/input");
      $this->type("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[3]/input", '15');     
      $this->click("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[1]/input");
      $this->type("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[1]/input", '456');     
      $this->click("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[2]/input");
      $this->type("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[2]/input", '34534');   
      $this->click("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[3]/input");
      $this->type("xpath=//table[@id='donation_summary']/tbody/tr[@class='odd-row new']/td[7]/table/tbody/tr[2]/td[3]/input", '456456456546');   
      $this->click('save');
      $this->waitForElementPresent( "css=input.select-delete" );
      $this->click("css=input.select-delete");
      $this->click('save');
      sleep(5);
      $this->waitForElementPresent( "addNewRow" );
      $this->waitForElementPresent( "donation_summary" );
      $this->assertTrue( $this->isTextPresent( "Total Amount $ 0.00 " ));
  }
  
  function _webtestLogout(){
      $this->open( $this->sboxPath . "user/logout" );
      $this->waitForPageToLoad('30000');    
      $this->open( $this->sboxPath . "user" );
      $this->waitForPageToLoad('30000');
  }
  
  function _webtestLogin( $userName, $pass = "admin" ){
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

  function _webtestAddDonor( $donorInfo, $isDeno = false, $supporterOf = null ){
      $this->waitForElementPresent( "css=#crm-create-new-link span" );
      $this->click('css=#crm-create-new-link span');
      $this->click("xpath=//div[@class='crm-create-new-list-inner']/ul/li[1]/a");
      $this->waitForElementPresent( "css=.crm-button_qf_Edit_next .form-submit" );
      $this->click('first_name');
      $this->type('first_name', $donorInfo[ 'fName' ] );
      $this->click('last_name');
      $this->type('last_name', $donorInfo[ 'lName' ] );
      $this->waitForElementPresent( "email-5" );
      $this->click('email-5');
      $this->type('email-5', $donorInfo[ 'fName' ].".".$donorInfo[ 'fName' ]."@webtest.com" );
      if( $supporterOf != null ){
          $this->type('supporter', $supporterOf);
          $this->click('supporter');
          $this->waitForElementPresent("css=div.ac_results-inner li");
          $this->click("css=div.ac_results-inner li");
      }
      $this->click('css=.crm-button_qf_Edit_next .form-submit');
      if( $supporterOf == null && $isDeno ){
          sleep(2);
          $this->assertTrue( (bool) $this->getConfirmation());
          $this->chooseOkOnNextConfirmation( );
      }      
      $this->waitForPageToLoad('30000');
  }
  function _webtestCreateCMSUser( $username, $email, $pass = 'admin', $fName, $lName  ){
      $this->open( $this->sboxPath . "admin/people/create" );
      $this->click('edit-name');
      $this->type('edit-name', $username );
      $this->click('edit-mail');
      $this->type('edit-mail', $email );
      $this->click('edit-pass-pass1');
      $this->type('edit-pass-pass1', $pass );
      $this->click('edit-pass-pass2');
      $this->type('edit-pass-pass2', $pass );
      $this->click('first_name');
      $this->type('first_name', $fName );
      $this->click('last_name');
      $this->type('last_name', $lName );      
      $this->click('edit-submit');
      $this->waitForPageToLoad('30000');
  }

  function _webtestAddDenomination( $organizationName = "webtestUCC", $email = null ) {
      $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
      $this->click('organization_name');
      $this->type('organization_name', $organizationName );
      $this->select( 'contact_sub_type', 'value=Denomination' );
      if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
      if ($email) $this->type('email_1_email', $email);
      
      $this->click('_qf_Contact_upload_view');
      $this->waitForPageToLoad('30000');        
      return $email;
  }
  
   function _webtestAddConf( $organizationName = "webtestconf", $email = null ) {
      $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
      $this->click('organization_name');
      $this->type('organization_name', $organizationName );
      $this->select( 'contact_sub_type', 'value=Conference' );
      if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
      if ($email) $this->type('email_1_email', $email);
      
      $this->click('_qf_Contact_upload_view');
      $this->waitForPageToLoad('30000');        
      return $email;
  }
  
   function _webtestAddPresb( $organizationName = "webtestpresb", $email = null ) {
      $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
      $this->click('organization_name');
      $this->type('organization_name', $organizationName );
      $this->select( 'contact_sub_type', 'value=Presbytery' );
      if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
      if ($email) $this->type('email_1_email', $email);
      
      $this->click('_qf_Contact_upload_view');
      $this->waitForPageToLoad('30000');        
      return $email;
  }
  
   function _webtestAddPC( $organizationName = "webtestpc", $email = null ) {
      $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
      $this->click('organization_name');
      $this->type('organization_name', $organizationName );
      $this->select( 'contact_sub_type', 'value=Pastoral_Charge' );
      if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
      if ($email) $this->type('email_1_email', $email);
      
      $this->click('_qf_Contact_upload_view');
      $this->waitForPageToLoad('30000');        
      return $email;
  }

   function _webtestAddCong( $organizationName = "webtestcong", $email = null ) {
       $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Organization');
       $this->click('organization_name');
       $this->type('organization_name', $organizationName );
       $this->select( 'contact_sub_type', 'value=Congregation' );
       if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
       if ($email) $this->type('email_1_email', $email);
      
       $this->click('_qf_Contact_upload_view');
       $this->waitForPageToLoad('30000');        
       return $email;
   }

   function _addRel( $rel, $otherContactName ){
       $this->waitForPageToLoad('30000'); 
       $this->waitForElementPresent( "css=li#tab_rel a" );
       $this->click("css=li#tab_rel a");
       $this->waitForElementPresent( "css=div.action-link a.button" );
       $this->click( 'css=div.action-link a.button' );
       $this->waitForElementPresent( "search-button" );
       $this->select( 'relationship_type_id', "value=$rel" );
       $this->_webtestRelationFillAutocomplete( $otherContactName );
       $this->waitForPageToLoad('30000');       
   }

   function _webtestRelationFillAutocomplete( $sortName ) {
       $this->type('rel_contact', $sortName);
       $this->click('rel_contact');
       $this->waitForElementPresent("css=div.ac_results-inner li");
       $this->click("css=div.ac_results-inner li");
       $this->assertContains($sortName, $this->getValue('rel_contact'), "autocomplete expected $sortName but didn’t find it in " . $this->getValue('rel_contact'));
       $this->click( 'details-save' );
    }

   function _webtestAddIndividual( $adminFName = "webtest", $adminLName = "denoAdmin" , $email = null ) {
       $this->open($this->sboxPath . 'civicrm/contact/add?reset=1&ct=Individual');
       $this->click('first_name');
       $this->type('first_name', $adminFName );
       $this->click('last_name');
       $this->type('last_name', $adminLName );
       
       if ($email === true) $email = substr(sha1(rand()), 0, 7) . '@example.org';
       if ($email) $this->type('email_1_email', $email);
      
       $this->click('_qf_Contact_upload_view');
       $this->waitForPageToLoad('30000');        
       return $email;
   }
  
}