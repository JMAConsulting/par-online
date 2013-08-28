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

class WebTest_DirectDebit_DonationTab extends CiviSeleniumTestCase
{
    protected $captureScreenshotOnFailure = FALSE;
    protected $screenshotPath = '/var/www/direct-debit/';
    protected $screenshotUrl  = "http://192.168.2.157/direct-debit/";
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
        $user = "uccdrpl";
        //To add Donation
        $this->_webtestLogin($user);
        $this->waitForElementPresent("_qf_Donation_save");
        $this->click('payment_instrument');
        $this->select( 'payment_instrument', 'value=6' );
        $this->type('bank', '123' );
        $this->type('branch', '786' );
        $this->type('account', '123456' );
        $this->type('price_1', '2' );
        $this->type('price_2', '2' );
        $this->type('price_3', '5' );
        $this->click('_qf_Donation_save');
        $this->open( $this->sboxPath . "direct_debit?reset=1" );
        $this->waitForPageToLoad('20000');
        $this->_webtestLogout();
        //For Edit
        $this->_webtestLogin($user);
        $this->waitForElementPresent("_qf_Donation_save");
        $this->select( 'payment_status','value=5');
        $this->select( 'payment_instrument', 'value=6' );
        $this->type('bank', '624' );
        $this->type('branch', '598' );
        $this->type('account', '654321' );
        $this->type('price_1', '4' );
        $this->type('price_2', '4' );
        $this->type('price_3', '6' );
        $this->click('_qf_Donation_save');
        
    }
    function _webtestLogout(){
        $this->open( $this->sboxPath . "user/logout" );
        $this->waitForPageToLoad('30000');    
        $this->open( $this->sboxPath . "user" );
        $this->waitForPageToLoad('30000');
    }
    
    function _webtestLogin( $userName, $pass = "drpl@3250" ){
        $this->waitForElementPresent( "edit-name" );
        $this->click('edit-name');
        $this->type('edit-name', $userName );
        $this->click('edit-pass');
        $this->type('edit-pass', $pass );
        $this->click('edit-submit');
        $this->waitForPageToLoad('30000');
        $this->open( $this->sboxPath . "civicrm/contact/view?reset=1&cid=8404&selectedChild=donation" );
        $this->waitForPageToLoad('30000');
       
    }
}